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
            XHR.open ("GET", "api/toggle_interested.php?property_id=" + property_id + "&csrf_token=" + window.csrf_token);
            // initiate the request
            XHR.send();

            document.getElementById("loading").style.display = "block";
            event.preventDefault();
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

            document.getElementById("loading").style.display = "block";
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

        // vary mock coordinates slightly based on property_id
        if (property_id == 1) { initMap(28.6430, 77.2150); }
        else if (property_id == 2) { initMap(28.6425, 77.2120); }
        else if (property_id == 3) { initMap(19.1030, 72.8270); }
        else if (property_id == 4) { initMap(19.2300, 72.8340); }
        else if (property_id == 5) { initMap(19.2310, 72.8580); }
        else {
            // Geocode using OSM Nominatim for new properties
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

            document.getElementById("loading").style.display = "block";
            event.preventDefault();
        });
    }
});

var toggle_interested_success = function (event) {
    document.getElementById("loading").style.display = "none";

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
    } else if (!response.success && !response.is_logged_in) {
        window.$('#login-modal').modal("show");
    }
};

var book_property_success = function (event) {
    document.getElementById("loading").style.display = "none";

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
    document.getElementById("loading").style.display = "none";

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast(response.message || 'Review submitted successfully! Thank you.', 'success');
        setTimeout(function() { location.reload(); }, 1500);
    } else {
        showToast(response.message || 'Failed to submit review.', 'error');
    }
};

var on_error = function () {
    document.getElementById("loading").style.display = "none";
    showToast('A network error occurred. Please check your connection.', 'error');
};