window.addEventListener ("load", function () {
    const search = window.location.search;
    const params = new URLSearchParams(search);
    const property_id = params.get('property_id');

    var is_interested_image = document.getElementsByClassName('is-interested-image')[0];
    if (is_interested_image) {
        is_interested_image.addEventListener ("click", function (event) {
            var XHR = new XMLHttpRequest();
            // on success
            XHR.addEventListener("load", toggle_interested_success);
            // on error
            XHR.addEventListener("error", on_error);
            // set up request
            XHR.open("POST", "api/toggle_interested.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            // initiate the request
            XHR.send("property_id=" + encodeURIComponent(property_id) + "&csrf_token=" + encodeURIComponent(window.csrf_token));

            showLoading();
            event.preventDefault();
        });

        // Keyboard accessibility for heart icon
        is_interested_image.addEventListener("keydown", function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.click();
            }
        });
    }

    var book_now_btn = document.getElementById('book-now-btn');
    if (book_now_btn) {
        book_now_btn.addEventListener("click", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData();
            form_data.append("property_id", property_id);
            form_data.append("csrf_token", window.csrf_token);

            XHR.addEventListener("load", book_property_success);
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/book_property.php");
            XHR.send(form_data);

            showLoading();
            event.preventDefault();
        });
    }

    // Initialize Leaflet Map
    var map_el = document.getElementById("map");
    if (map_el) {
        var lat = 28.643, lng = 77.215; // default Delhi
        var propName = document.querySelector(".property-name") ? document.querySelector(".property-name").textContent.trim() : "PG Location";
        var propAddr = document.querySelector(".property-address") ? document.querySelector(".property-address").textContent.trim() : "";

        function initMap(mapLat, mapLng) {
            var map = L.map('map').setView([mapLat, mapLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            L.marker([mapLat, mapLng]).addTo(map)
                .bindPopup('<strong>' + propName + '</strong><br>' + propAddr)
                .openPopup();
        }

        var latAttr = map_el.getAttribute("data-lat");
        var lngAttr = map_el.getAttribute("data-lng");

        if (latAttr && lngAttr) {
            initMap(parseFloat(latAttr), parseFloat(lngAttr));
        } else {
            // Geocode using OSM Nominatim for legacy properties
            var query = encodeURIComponent(propAddr);
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + query + '&countrycodes=in&limit=1')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data && data.length > 0) {
                        initMap(parseFloat(data[0].lat), parseFloat(data[0].lon));
                    } else {
                        // Fallback 1: split address and search for city, state, or postal code
                        var parts = propAddr.split(',');
                        if (parts.length > 1) {
                            var fallbackQuery = encodeURIComponent(parts.slice(-2).join(',').trim());
                            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + fallbackQuery + '&countrycodes=in&limit=1')
                                .then(function(r) { return r.json(); })
                                .then(function(d) {
                                    if (d && d.length > 0) {
                                        initMap(parseFloat(d[0].lat), parseFloat(d[0].lon));
                                    } else {
                                        initMap(lat, lng);
                                    }
                                })
                                .catch(function() { initMap(lat, lng); });
                        } else {
                            initMap(lat, lng);
                        }
                    }
                })
                .catch(function(err) {
                    initMap(lat, lng);
                });
        }
    }

    // Submit Review Form Submission
    var add_review_form = document.getElementById("add-review-form");
    if (add_review_form) {
        add_review_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(add_review_form);

            XHR.addEventListener("load", add_review_success);
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/add_review.php");
            XHR.send(form_data);

            showLoading();
            event.preventDefault();
        });
    }
    // ── Image Lightbox ──
    initLightbox();
});

// ── Lightbox Module ──
function initLightbox() {
    var carouselImages = document.querySelectorAll('#property-images .carousel-item img');
    if (carouselImages.length === 0) return;

    var lightbox        = document.getElementById('image-lightbox');
    var lightboxImg     = document.getElementById('lightbox-img');
    var lightboxCounter = document.getElementById('lightbox-counter');
    if (!lightbox) return;

    var images = [];
    var alts   = [];
    var currentIndex = 0;

    carouselImages.forEach(function(img, idx) {
        images.push(img.src);
        alts.push(img.alt || 'Property image ' + (idx + 1));
        img.addEventListener('click', function() {
            currentIndex = idx;
            openLightbox();
        });
    });

    // Update carousel overlay counter badge
    var overlayBadge = document.getElementById('carousel-photo-count');
    if (overlayBadge) {
        overlayBadge.textContent = images.length + ' photo' + (images.length !== 1 ? 's' : '');
    }

    // Sync lightbox when Bootstrap carousel slides
    var bsCarousel = document.getElementById('property-images');
    if (bsCarousel) {
        bsCarousel.addEventListener('slide.bs.carousel', function(e) {
            currentIndex = e.to;
        });
        // Bootstrap 3/4 uses slid.bs.carousel
        bsCarousel.addEventListener('slid.bs.carousel', function(e) {
            if (e.to !== undefined) currentIndex = e.to;
        });
    }

    function getFileExtension(url) {
        if (!url) return 'jpg';
        if (url.startsWith('data:')) {
            var mimeMatch = url.match(/data:([^;]+);/);
            if (mimeMatch && mimeMatch[1]) {
                var ext = mimeMatch[1].split('/')[1];
                if (ext) {
                    ext = ext.toLowerCase();
                    if (ext.indexOf('svg') !== -1) return 'svg';
                    if (ext === 'jpeg') return 'jpg';
                    return ext;
                }
            }
            return 'jpg';
        }
        var pathWithoutQuery = url.split(/[?#]/)[0];
        var parts = pathWithoutQuery.split('.');
        if (parts.length > 1) {
            var detectedExt = parts.pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(detectedExt) !== -1) {
                return detectedExt === 'jpeg' ? 'jpg' : detectedExt;
            }
        }
        return 'jpg';
    }

    function getCleanFilename(alt) {
        var clean = (alt || '')
            .replace(/[^a-zA-Z0-9\s-_]/g, '')
            .trim();
        return clean || 'property_image';
    }

    function updateLightbox() {
        lightboxImg.src = images[currentIndex];
        lightboxImg.alt = alts[currentIndex];
        if (lightboxCounter) {
            lightboxCounter.textContent = (currentIndex + 1) + ' / ' + images.length + ' photos';
        }

        // Update download button href and download filename attribute
        var dlBtn = document.getElementById('lightbox-download-btn');
        if (dlBtn) {
            var imgUrl = images[currentIndex];
            dlBtn.href = imgUrl;
            dlBtn.download = getCleanFilename(alts[currentIndex]) + '.' + getFileExtension(imgUrl);
        }
    }

    function openLightbox() {
        updateLightbox();
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
        lightboxImg.focus();
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function navigate(dir) {
        currentIndex = (currentIndex + dir + images.length) % images.length;
        updateLightbox();
    }

    // Close button
    var closeBtn = document.getElementById('lightbox-close');
    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);

    // Click backdrop to close
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });

    // Navigation buttons
    var prevBtn = document.getElementById('lightbox-prev');
    var nextBtn = document.getElementById('lightbox-next');
    if (prevBtn) prevBtn.addEventListener('click', function(e) { e.stopPropagation(); navigate(-1); });
    if (nextBtn) nextBtn.addEventListener('click', function(e) { e.stopPropagation(); navigate(1); });

    // Download button event listener to handle CORS / dynamic extensions
    var dlBtn = document.getElementById('lightbox-download-btn');
    if (dlBtn) {
        dlBtn.addEventListener('click', function(e) {
            var imgUrl = images[currentIndex];
            if (!imgUrl || imgUrl.startsWith('data:')) {
                // Let the browser handle standard data URI download
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            showToast('Downloading image...', 'info');

            fetch(imgUrl)
                .then(function(response) {
                    if (!response.ok) throw new Error('Response status ' + response.status);
                    return response.blob();
                })
                .then(function(blob) {
                    var objectUrl = URL.createObjectURL(blob);
                    
                    var ext = getFileExtension(imgUrl);
                    if (blob.type) {
                        var mimeParts = blob.type.split('/');
                        if (mimeParts.length > 1) {
                            var mimeExt = mimeParts[1].toLowerCase();
                            if (mimeExt.indexOf('svg') !== -1) ext = 'svg';
                            else if (mimeExt === 'jpeg') ext = 'jpg';
                            else if (['jpg', 'png', 'gif', 'webp', 'svg'].indexOf(mimeExt) !== -1) {
                                ext = mimeExt;
                            }
                        }
                    }
                    
                    var filename = getCleanFilename(alts[currentIndex]) + '.' + ext;
                    
                    var tempLink = document.createElement('a');
                    tempLink.href = objectUrl;
                    tempLink.download = filename;
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                    
                    setTimeout(function() {
                        URL.revokeObjectURL(objectUrl);
                    }, 100);
                })
                .catch(function(err) {
                    console.error('Fetch download failed, falling back:', err);
                    // Fallback to opening in new window / tab
                    var tempLink = document.createElement('a');
                    tempLink.href = imgUrl;
                    tempLink.target = '_blank';
                    tempLink.download = getCleanFilename(alts[currentIndex]) + '.' + getFileExtension(imgUrl);
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                });
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  navigate(-1);
        if (e.key === 'ArrowRight') navigate(1);
    });

    // Share button inside lightbox
    var shareBtn = document.getElementById('lightbox-share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var imgUrl = images[currentIndex];
            var propName = document.querySelector('.property-name');
            var title = propName ? propName.textContent.trim() : 'Property Image';
            
            var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (navigator.share && isMobile) {
                navigator.share({ title: title, url: imgUrl }).catch(function() {});
            } else {
                // Clipboard fallback
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(imgUrl).then(function() {
                        showToast('Image link copied to clipboard!', 'success');
                    });
                } else {
                    showToast('Copy this URL: ' + imgUrl, 'info');
                }
            }
        });
    }
}

var toggle_interested_success = function (event) {
    hideLoading();

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        var is_interested_image = document.getElementsByClassName('is-interested-image')[0];
        var interested_user_count = document.getElementsByClassName('interested-user-count')[0];

        if(response.is_interested) {
            is_interested_image.classList.add('fas');
            is_interested_image.classList.remove('far');
            if (interested_user_count) {
                interested_user_count.innerHTML = parseFloat(interested_user_count.innerHTML) + 1;
            }
        } else {            
            is_interested_image.classList.add('far');
            is_interested_image.classList.remove('fas');
            if (interested_user_count) {
                interested_user_count.innerHTML = parseFloat(interested_user_count.innerHTML) - 1;
            }
        }
    } else if (!response.success && response.is_logged_in === false) {
        window.$('#login-modal').modal("show");
    } else {
        showToast(response.message || 'You are not allowed to perform this action.', 'warning');
    }
};

var book_property_success = function (event) {
    hideLoading();

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast(response.message || 'Property booked successfully!', 'success');
        var btn = document.getElementById('book-now-btn');
        if (btn) {
            var btn_container = btn.parentElement;
            btn_container.innerHTML = '<button class="btn btn-success btn-block" disabled><i class="fas fa-check-circle mr-2"></i>Booked!</button>';
        }
    } else if (!response.success && response.is_logged_in === false) {
        window.$('#login-modal').modal("show");
    } else {
        showToast(response.message || 'Booking failed. Please try again.', 'error');
    }
};

var add_review_success = function (event) {
    hideLoading();

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast(response.message || 'Review submitted successfully! Thank you.', 'success');
        setTimeout(function() {
            var el = document.body;
            el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function() { location.reload(); }, 250);
        }, 1250);
    } else {
        showToast(response.message || 'Failed to submit review.', 'error');
    }
};

var on_error = function () {
    hideLoading();
    showToast('A network error occurred. Please check your connection.', 'error');
};