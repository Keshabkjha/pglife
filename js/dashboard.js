window.addEventListener("load", function () {
    var is_interested_images = this.document.getElementsByClassName("is-interested-image");
    Array.from(is_interested_images).forEach(element => {
        element.addEventListener("click", function (event) {
            var XHR = new XMLHttpRequest();
            var property_id = event.target.getAttribute("property_id");
            // on success
            XHR.addEventListener("load", remove_interested_success);

            // on error
            XHR.addEventListener("error", on_error);

            // set up request
            XHR.open("POST", "api/toggle_interested.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            // initiate the request
            XHR.send("property_id=" + encodeURIComponent(property_id) + "&csrf_token=" + encodeURIComponent(window.csrf_token));

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    });

    var cancel_booking_btns = this.document.getElementsByClassName("cancel-booking-btn");
    Array.from(cancel_booking_btns).forEach(element => {
        element.addEventListener("click", function (event) {
            if (!confirm("Are you sure you want to cancel this booking?")) {
                return;
            }
            var XHR = new XMLHttpRequest();
            var property_id = event.target.getAttribute("property_id");
            
            // on success
            XHR.addEventListener("load", cancel_booking_success);

            // on error
            XHR.addEventListener("error", on_error);

            // set up request
            XHR.open("POST", "api/cancel_booking.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            // initiate the request
            XHR.send("property_id=" + property_id + "&csrf_token=" + window.csrf_token);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    });

    var edit_profile_form = document.getElementById("edit-profile-form");
    if (edit_profile_form) {
        edit_profile_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(edit_profile_form);

            XHR.addEventListener("load", edit_profile_success);
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/update_profile.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // Update profile pic file input label when image is selected
    var profile_pic = document.getElementById("profile_pic");
    if (profile_pic) {
        profile_pic.addEventListener("change", function (e) {
            var files = e.target.files;
            var label = document.getElementById("profile-pic-label-text");
            if (label) {
                if (files.length > 0) {
                    label.textContent = files[0].name;
                } else {
                    label.textContent = "Choose image...";
                }
            }
        });
    }

    // Update file input label and render previews when images are selected
    var prop_images = document.getElementById("prop-images");
    if (prop_images) {
        prop_images.addEventListener("change", function (e) {
            var files = e.target.files;
            var label = document.getElementById("file-label-text");
            if (label) {
                if (files.length > 0) {
                    if (files.length === 1) {
                        label.textContent = files[0].name;
                    } else {
                        label.textContent = files.length + " files selected";
                    }
                } else {
                    label.textContent = "Choose images...";
                }
            }

            // Render live previews
            var previewContainer = document.getElementById("add-image-previews-container");
            var primaryIndexInput = document.getElementById("add-primary-image-index");
            if (previewContainer) {
                previewContainer.innerHTML = "";
                if (files.length > 0) {
                    previewContainer.classList.remove("d-none");
                    primaryIndexInput.value = "0"; // Default primary is the first image

                    Array.from(files).forEach((file, index) => {
                        var reader = new FileReader();
                        reader.onload = function (event) {
                            var card = document.createElement("div");
                            card.className = "image-preview-card" + (index === 0 ? " is-primary" : "");
                            card.setAttribute("data-index", index);

                            var img = document.createElement("img");
                            img.src = event.target.result;
                            img.alt = file.name;
                            card.appendChild(img);

                            var controls = document.createElement("div");
                            controls.className = "card-controls";

                            var primaryBtn = document.createElement("button");
                            primaryBtn.type = "button";
                            primaryBtn.className = "control-btn btn-primary-img";
                            primaryBtn.title = "Make Primary";
                            primaryBtn.innerHTML = '<i class="fas fa-star"></i>';
                            primaryBtn.addEventListener("click", function () {
                                document.querySelectorAll("#add-image-previews-container .image-preview-card").forEach(c => {
                                    c.classList.remove("is-primary");
                                    var badge = c.querySelector(".primary-badge");
                                    if (badge) badge.remove();
                                });
                                card.classList.add("is-primary");
                                primaryIndexInput.value = index;
                                addPrimaryBadge(card);
                            });

                            controls.appendChild(primaryBtn);
                            card.appendChild(controls);

                            if (index === 0) {
                                addPrimaryBadge(card);
                            }

                            previewContainer.appendChild(card);
                        };
                        reader.readAsDataURL(file);
                    });
                } else {
                    previewContainer.classList.add("d-none");
                }
            }
        });
    }

    // Submit Add Property Form via AJAX
    var add_property_form = document.getElementById("add-property-form");
    if (add_property_form) {
        add_property_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(add_property_form);

            XHR.addEventListener("load", function(e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'Property listed successfully!', 'success');
                        setTimeout(function() {
                            var el = document.body;
                            el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-8px)';
                            setTimeout(function() { location.reload(); }, 250);
                        }, 950);
                    } else {
                        showToast(response.message || 'Failed to list property.', 'error');
                    }
                } catch(err) {
                    showToast('An error occurred while listing the property.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/add_property.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // Populate Bookings Modal Dynamically
    var view_bookings_btns = document.getElementsByClassName("view-bookings-btn");
    Array.from(view_bookings_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var propertyName = btn.getAttribute("data-property-name");
            var bookings = JSON.parse(btn.getAttribute("data-bookings") || "[]");

            document.querySelector(".booking-property-title").textContent = "Property: " + propertyName;
            var tbody = document.querySelector(".bookings-table-body");
            var noBookingsMsg = document.querySelector(".no-bookings-message");
            var tableEl = tbody.closest(".table-responsive");

            tbody.innerHTML = "";
            if (bookings.length === 0) {
                noBookingsMsg.classList.remove("d-none");
                tableEl.classList.add("d-none");
            } else {
                noBookingsMsg.classList.add("d-none");
                tableEl.classList.remove("d-none");
                bookings.forEach(booking => {
                    var tr = document.createElement("tr");
                    var formattedDate = new Date(booking.booking_date).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    tr.innerHTML = "<td>" + escapeHtml(booking.seeker_name) + "</td>" +
                                   "<td>" + escapeHtml(booking.seeker_email) + "</td>" +
                                   "<td>" + escapeHtml(booking.seeker_phone) + "</td>" +
                                   "<td>" + formattedDate + "</td>";
                    tbody.appendChild(tr);
                });
            }
            window.$("#view-bookings-modal").modal("show");
        });
    });

    // Populate Reviews Modal Dynamically
    var view_reviews_btns = document.getElementsByClassName("view-reviews-btn");
    Array.from(view_reviews_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var propertyName = btn.getAttribute("data-property-name");
            var reviews = JSON.parse(btn.getAttribute("data-reviews") || "[]");

            document.querySelector(".review-property-title").textContent = "Property: " + propertyName;
            var container = document.querySelector(".reviews-list-container");
            var noReviewsMsg = document.querySelector(".no-reviews-message");

            container.innerHTML = "";
            if (reviews.length === 0) {
                noReviewsMsg.classList.remove("d-none");
                container.classList.add("d-none");
            } else {
                noReviewsMsg.classList.add("d-none");
                container.classList.remove("d-none");
                reviews.forEach(review => {
                    var div = document.createElement("div");
                    div.className = "review-item mb-3 p-3 border rounded bg-light text-dark";
                    
                    var stars = "";
                    for(var i = 0; i < 5; i++) {
                        stars += i < review.rating ? '<i class="fas fa-star text-warning mr-1"></i>' : '<i class="far fa-star text-muted mr-1"></i>';
                    }

                    var formattedDate = new Date(review.created_at).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });

                    var verifiedBadge = "";
                    if (review.is_verified === 2 || parseInt(review.is_verified) === 2) {
                        verifiedBadge = ' <i class="fas fa-check-circle text-success ml-1" title="KYC Verified" style="color: #28a745 !important;"></i>';
                    }

                    div.innerHTML = "<div class='d-flex justify-content-between align-items-center mb-2'>" +
                                        "<strong style='font-size:14px;'>" + escapeHtml(review.user_name) + verifiedBadge + "</strong>" +
                                        "<div style='font-size:10px;'>" + stars + "</div>" +
                                    "</div>" +
                                    "<p class='mb-1 text-secondary' style='font-size:13px;'>" + escapeHtml(review.content) + "</p>" +
                                    "<small class='text-muted' style='font-size:11px;'><i class='far fa-clock mr-1'></i>" + formattedDate + "</small>";
                    container.appendChild(div);
                });
            }
            window.$("#view-reviews-modal").modal("show");
        });
    });

    // --- Edit Property Button Click ---
    var edit_property_btns = document.getElementsByClassName("edit-property-btn");
    Array.from(edit_property_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var property = JSON.parse(btn.getAttribute("data-property"));
            var amenityIds = JSON.parse(btn.getAttribute("data-amenities-ids") || "[]");

            document.getElementById("edit-prop-id").value = property.id;
            document.getElementById("edit-prop-name").value = property.name;
            document.getElementById("edit-prop-city").value = property.city_id;
            document.getElementById("edit-prop-address").value = property.address;
            document.getElementById("edit-prop-desc").value = property.description;
            document.getElementById("edit-prop-gender").value = property.gender;
            document.getElementById("edit-prop-rent").value = property.rent;

            // Reset and check amenities checkboxes
            var checkboxes = document.querySelectorAll(".edit-amenity-chk");
            checkboxes.forEach(chk => {
                var amenityId = parseInt(chk.value);
                chk.checked = amenityIds.includes(amenityId);
            });

            // Reset file input label
            var editLabel = document.getElementById("edit-file-label-text");
            if (editLabel) editLabel.textContent = "Choose images...";

            // Populate current images manager dynamically
            var currentImagesContainer = document.getElementById("edit-current-images-container");
            var deletedImagesInputsContainer = document.getElementById("deleted-images-inputs-container");
            var primaryImageVal = document.getElementById("edit-primary-image-val");
            var currentPrimary = btn.getAttribute("data-primary-image") || "";
            var imageFilenames = JSON.parse(btn.getAttribute("data-images") || "[]");

            if (currentImagesContainer) {
                currentImagesContainer.innerHTML = "";
                deletedImagesInputsContainer.innerHTML = "";
                primaryImageVal.value = currentPrimary;

                // Clear new images inputs
                var editPropImgInput = document.getElementById("edit-prop-images");
                if (editPropImgInput) editPropImgInput.value = "";
                var editImagePreviews = document.getElementById("edit-image-previews-container");
                if (editImagePreviews) {
                    editImagePreviews.innerHTML = "";
                    editImagePreviews.classList.add("d-none");
                }
                document.getElementById("edit-new-primary-image-index").value = "";

                if (imageFilenames.length > 0) {
                    imageFilenames.forEach(filename => {
                        var card = document.createElement("div");
                        var isPrimary = (filename === currentPrimary);
                        card.className = "image-preview-card" + (isPrimary ? " is-primary" : "");
                        
                        var img = document.createElement("img");
                        img.src = "img/properties/" + property.id + "/" + filename;
                        img.alt = filename;
                        card.appendChild(img);

                        var controls = document.createElement("div");
                        controls.className = "card-controls";

                        // Set Primary Button
                        var primaryBtn = document.createElement("button");
                        primaryBtn.type = "button";
                        primaryBtn.className = "control-btn btn-primary-img";
                        primaryBtn.title = "Make Primary";
                        primaryBtn.innerHTML = '<i class="fas fa-star"></i>';
                        primaryBtn.addEventListener("click", function () {
                            // Clear primary from other current images
                            document.querySelectorAll("#edit-current-images-container .image-preview-card").forEach(c => {
                                c.classList.remove("is-primary");
                                var badge = c.querySelector(".primary-badge");
                                if (badge) badge.remove();
                            });
                            // Clear primary from new images
                            document.querySelectorAll("#edit-image-previews-container .image-preview-card").forEach(c => {
                                c.classList.remove("is-primary");
                                var badge = c.querySelector(".primary-badge");
                                if (badge) badge.remove();
                            });
                            document.getElementById("edit-new-primary-image-index").value = "";

                            card.classList.add("is-primary");
                            primaryImageVal.value = filename;
                            addPrimaryBadge(card);
                        });
                        controls.appendChild(primaryBtn);

                        // Delete Button
                        var deleteBtn = document.createElement("button");
                        deleteBtn.type = "button";
                        deleteBtn.className = "control-btn btn-delete-img";
                        deleteBtn.title = "Delete Image";
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteBtn.addEventListener("click", function () {
                            if (confirm("Are you sure you want to delete this image?")) {
                                card.classList.add("marked-deleted");
                                
                                var delLabel = document.createElement("span");
                                delLabel.className = "delete-badge";
                                delLabel.innerText = "Deleted";
                                card.appendChild(delLabel);

                                // Append deleted input
                                var hiddenInput = document.createElement("input");
                                hiddenInput.type = "hidden";
                                hiddenInput.name = "deleted_images[]";
                                hiddenInput.value = filename;
                                deletedImagesInputsContainer.appendChild(hiddenInput);

                                // If we deleted the primary image, select another image as primary automatically
                                if (primaryImageVal.value === filename) {
                                    primaryImageVal.value = "";
                                    card.classList.remove("is-primary");
                                    var badge = card.querySelector(".primary-badge");
                                    if (badge) badge.remove();

                                    // Try to set another non-deleted image as primary
                                    var activeCards = Array.from(document.querySelectorAll("#edit-current-images-container .image-preview-card:not(.marked-deleted)"));
                                    if (activeCards.length > 0) {
                                        var newPrimaryCard = activeCards[0];
                                        newPrimaryCard.classList.add("is-primary");
                                        var newFilename = newPrimaryCard.querySelector("img").alt;
                                        primaryImageVal.value = newFilename;
                                        addPrimaryBadge(newPrimaryCard);
                                    }
                                }
                            }
                        });
                        controls.appendChild(deleteBtn);

                        card.appendChild(controls);

                        if (isPrimary) {
                            addPrimaryBadge(card);
                        }

                        currentImagesContainer.appendChild(card);
                    });
                }
            }

            window.$("#edit-property-modal").modal("show");
        });
    });

    // Update edit file input label and previews when images are selected
    var edit_prop_images = document.getElementById("edit-prop-images");
    if (edit_prop_images) {
        edit_prop_images.addEventListener("change", function (e) {
            var files = e.target.files;
            var label = document.getElementById("edit-file-label-text");
            if (label) {
                if (files.length > 0) {
                    if (files.length === 1) {
                        label.textContent = files[0].name;
                    } else {
                        label.textContent = files.length + " files selected";
                    }
                } else {
                    label.textContent = "Choose images...";
                }
            }

            // Render live previews for new uploads
            var previewContainer = document.getElementById("edit-image-previews-container");
            var newPrimaryIndexInput = document.getElementById("edit-new-primary-image-index");
            if (previewContainer) {
                previewContainer.innerHTML = "";
                if (files.length > 0) {
                    previewContainer.classList.remove("d-none");
                    newPrimaryIndexInput.value = ""; // Let owner choose if they want to override

                    Array.from(files).forEach((file, index) => {
                        var reader = new FileReader();
                        reader.onload = function (event) {
                            var card = document.createElement("div");
                            card.className = "image-preview-card";
                            card.setAttribute("data-index", index);

                            var img = document.createElement("img");
                            img.src = event.target.result;
                            img.alt = file.name;
                            card.appendChild(img);

                            var controls = document.createElement("div");
                            controls.className = "card-controls";

                            var primaryBtn = document.createElement("button");
                            primaryBtn.type = "button";
                            primaryBtn.className = "control-btn btn-primary-img";
                            primaryBtn.title = "Make Primary";
                            primaryBtn.innerHTML = '<i class="fas fa-star"></i>';
                            primaryBtn.addEventListener("click", function () {
                                // Clear primary from existing images
                                document.querySelectorAll("#edit-current-images-container .image-preview-card").forEach(c => {
                                    c.classList.remove("is-primary");
                                    var badge = c.querySelector(".primary-badge");
                                    if (badge) badge.remove();
                                });
                                document.getElementById("edit-primary-image-val").value = "";

                                // Clear primary from other new images
                                document.querySelectorAll("#edit-image-previews-container .image-preview-card").forEach(c => {
                                    c.classList.remove("is-primary");
                                    var badge = c.querySelector(".primary-badge");
                                    if (badge) badge.remove();
                                });

                                card.classList.add("is-primary");
                                newPrimaryIndexInput.value = index;
                                addPrimaryBadge(card);
                            });

                            controls.appendChild(primaryBtn);
                            card.appendChild(controls);
                            previewContainer.appendChild(card);
                        };
                        reader.readAsDataURL(file);
                    });
                } else {
                    previewContainer.classList.add("d-none");
                }
            }
        });
    }

    // --- Submit Edit Property Form via AJAX ---
    var edit_property_form = document.getElementById("edit-property-form");
    if (edit_property_form) {
        edit_property_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(edit_property_form);

            XHR.addEventListener("load", function(e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'Property updated successfully!', 'success');
                        setTimeout(function() {
                            var el = document.body;
                            el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-8px)';
                            setTimeout(function() { location.reload(); }, 250);
                        }, 950);
                    } else {
                        showToast(response.message || 'Failed to update property.', 'error');
                    }
                } catch(err) {
                    showToast('An error occurred while updating the property.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/edit_property.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // --- Delete Property Button Click ---
    var delete_property_btns = document.getElementsByClassName("delete-property-btn");
    Array.from(delete_property_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var property_id = btn.getAttribute("property_id");
            var name = btn.getAttribute("property_name");

            if (!confirm("Are you sure you want to delete the listing '" + name + "'?\n\nThis will permanently remove the property and all associated bookings, reviews, and images. This action cannot be undone!")) {
                return;
            }

            var XHR = new XMLHttpRequest();
            XHR.addEventListener("load", function(e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'Property deleted successfully.', 'success');
                        setTimeout(function() {
                            var el = document.body;
                            el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-8px)';
                            setTimeout(function() { location.reload(); }, 250);
                        }, 950);
                    } else {
                        showToast(response.message || 'Failed to delete property.', 'error');
                    }
                } catch(err) {
                    showToast('An error occurred while deleting the property.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/delete_property.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            XHR.send("property_id=" + property_id + "&csrf_token=" + window.csrf_token);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    });

    // --- Seeker: Report Issue Modal Trigger ---
    var report_issue_btns = document.getElementsByClassName("seeker-report-issue-btn");
    Array.from(report_issue_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var propertyId = btn.getAttribute("data-property-id");
            var propertyName = btn.getAttribute("data-property-name");

            document.getElementById("ticket-property-id").value = propertyId;
            document.getElementById("ticket-property-name-display").textContent = propertyName;
            document.getElementById("ticket-title").value = "";
            document.getElementById("ticket-desc").value = "";

            window.$("#create-ticket-modal").modal("show");
        });
    });

    // --- Seeker: Submit Ticket via AJAX ---
    var create_ticket_form = document.getElementById("create-ticket-form");
    if (create_ticket_form) {
        create_ticket_form.addEventListener("submit", function(event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(create_ticket_form);

            XHR.addEventListener("load", function(e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message, 'success');
                        window.$("#create-ticket-modal").modal("hide");
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(response.message || 'Failed to submit ticket.', 'error');
                    }
                } catch(err) {
                    showToast('An error occurred.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/create_ticket.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // --- Seeker: View Tickets Modal ---
    var seeker_view_tickets_btns = document.getElementsByClassName("seeker-view-tickets-btn");
    Array.from(seeker_view_tickets_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var propertyName = btn.getAttribute("data-property-name");
            var tickets = JSON.parse(btn.getAttribute("data-tickets") || "[]");

            document.querySelector(".seeker-ticket-property-title").textContent = "Property: " + propertyName;
            var container = document.querySelector(".seeker-tickets-list-container");
            var noTicketsMsg = document.querySelector(".no-seeker-tickets-message");

            container.innerHTML = "";
            if (tickets.length === 0) {
                noTicketsMsg.classList.remove("d-none");
                container.classList.add("d-none");
            } else {
                noTicketsMsg.classList.add("d-none");
                container.classList.remove("d-none");
                tickets.forEach(ticket => {
                    var div = document.createElement("div");
                    div.className = "review-item mb-3 p-3 border rounded bg-light text-dark";
                    
                    var badgeClass = ticket.status === 'resolved' ? 'badge-success' : 'badge-warning';
                    var statusText = ticket.status === 'resolved' ? 'Resolved' : 'Open / Pending';
                    var statusBadge = "<span class='badge " + badgeClass + " px-2 py-1 text-uppercase'>" + statusText + "</span>";

                    var formattedDate = new Date(ticket.created_at).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    div.innerHTML = "<div class='d-flex justify-content-between align-items-center mb-2'>" +
                                        "<strong style='font-size:14px;'>" + escapeHtml(ticket.title) + "</strong>" +
                                        "<div>" + statusBadge + "</div>" +
                                    "</div>" +
                                    "<p class='mb-1 text-secondary' style='font-size:13px;'>" + escapeHtml(ticket.description) + "</p>" +
                                    "<small class='text-muted' style='font-size:11px;'><i class='far fa-clock mr-1'></i>" + formattedDate + "</small>";
                    container.appendChild(div);
                });
            }
            window.$("#seeker-view-tickets-modal").modal("show");
        });
    });

    // --- Owner: View Tickets Modal ---
    var view_owner_tickets_btns = document.getElementsByClassName("view-tickets-btn");
    Array.from(view_owner_tickets_btns).forEach(element => {
        element.addEventListener("click", function(event) {
            var btn = event.currentTarget;
            var propertyName = btn.getAttribute("data-property-name");
            var tickets = JSON.parse(btn.getAttribute("data-tickets") || "[]");

            document.querySelector(".owner-ticket-property-title").textContent = "Property: " + propertyName;
            var container = document.querySelector(".owner-tickets-list-container");
            var noTicketsMsg = document.querySelector(".no-owner-tickets-message");

            container.innerHTML = "";
            if (tickets.length === 0) {
                noTicketsMsg.classList.remove("d-none");
                container.classList.add("d-none");
            } else {
                noTicketsMsg.classList.add("d-none");
                container.classList.remove("d-none");
                tickets.forEach(ticket => {
                    var div = document.createElement("div");
                    div.className = "review-item mb-3 p-3 border rounded bg-light text-dark";
                    div.setAttribute("id", "owner-ticket-item-" + ticket.id);

                    var badgeClass = ticket.status === 'resolved' ? 'badge-success' : 'badge-warning';
                    var statusText = ticket.status === 'resolved' ? 'Resolved' : 'Open / Pending';
                    var statusBadge = "<span class='badge " + badgeClass + " px-2 py-1 text-uppercase ticket-status-badge'>" + statusText + "</span>";

                    var formattedDate = new Date(ticket.created_at).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    var actionButton = "";
                    if (ticket.status === 'open') {
                        actionButton = "<button type='button' class='btn btn-success btn-sm owner-resolve-ticket-btn ml-3 font-weight-bold' data-ticket-id='" + ticket.id + "'>Mark as Resolved</button>";
                    }

                    div.innerHTML = "<div class='d-flex justify-content-between align-items-center mb-2'>" +
                                        "<strong style='font-size:14px;'>" + escapeHtml(ticket.title) + "</strong>" +
                                        "<div class='d-flex align-items-center'>" + statusBadge + actionButton + "</div>" +
                                    "</div>" +
                                    "<p class='mb-2 text-secondary' style='font-size:13px;'>" + escapeHtml(ticket.description) + "</p>" +
                                    "<div class='d-flex justify-content-between align-items-center border-top pt-2 mt-2'>" +
                                        "<small class='text-muted' style='font-size:11px;'><i class='far fa-clock mr-1'></i>" + formattedDate + "</small>" +
                                        "<small class='text-secondary' style='font-size:11px;'><i class='fas fa-user mr-1'></i>Reported by: " + escapeHtml(ticket.seeker_name) + " (" + escapeHtml(ticket.seeker_phone) + ")</small>" +
                                    "</div>";
                    container.appendChild(div);
                });

                // Bind resolve ticket buttons click event
                var resolveBtns = container.getElementsByClassName("owner-resolve-ticket-btn");
                Array.from(resolveBtns).forEach(rBtn => {
                    rBtn.addEventListener("click", function(evt) {
                        var tId = evt.target.getAttribute("data-ticket-id");
                        
                        var XHR = new XMLHttpRequest();
                        XHR.addEventListener("load", function(resp) {
                            document.getElementById("loading").style.display = 'none';
                            try {
                                var res = JSON.parse(resp.target.responseText);
                                if (res.success) {
                                    showToast(res.message, 'success');
                                    
                                    // Update Badge in modal UI
                                    var item = document.getElementById("owner-ticket-item-" + tId);
                                    if (item) {
                                        var badge = item.querySelector(".ticket-status-badge");
                                        if (badge) {
                                            badge.className = "badge badge-success px-2 py-1 text-uppercase ticket-status-badge";
                                            badge.innerText = "Resolved";
                                        }
                                        var btnToHide = item.querySelector(".owner-resolve-ticket-btn");
                                        if (btnToHide) btnToHide.remove();
                                    }
                                    
                                    // Reload page shortly to sync parent view counters
                                    setTimeout(function() { location.reload(); }, 1200);
                                } else {
                                    showToast(res.message || 'Failed to update ticket status.', 'error');
                                }
                            } catch(err) {
                                showToast('An error occurred.', 'error');
                            }
                        });
                        XHR.addEventListener("error", on_error);

                        XHR.open("POST", "api/update_ticket_status.php");
                        XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        XHR.send("ticket_id=" + tId + "&status=resolved&csrf_token=" + window.csrf_token);

                        document.getElementById("loading").style.display = 'block';
                    });
                });
            }
            window.$("#view-owner-tickets-modal").modal("show");
        });
    });

    // Handle KYC document input label update
    var kyc_doc = document.getElementById("kyc_doc");
    if (kyc_doc) {
        kyc_doc.addEventListener("change", function (e) {
            var files = e.target.files;
            var label = e.target.nextElementSibling;
            if (label && files.length > 0) {
                label.textContent = files[0].name;
            }
        });
    }

    // Submit KYC upload form via AJAX
    var kyc_upload_form = document.getElementById("kyc-upload-form");
    if (kyc_upload_form) {
        kyc_upload_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(kyc_upload_form);

            XHR.addEventListener("load", function (e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'Document uploaded successfully!', 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(response.message || 'Failed to upload document.', 'error');
                    }
                } catch (err) {
                    showToast('An error occurred during upload.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/upload_kyc.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // Handle Mock Admin Verification Simulation click
    var btn_simulate_kyc = document.getElementById("btn-simulate-kyc-approval");
    if (btn_simulate_kyc) {
        btn_simulate_kyc.addEventListener("click", function (event) {
            var XHR = new XMLHttpRequest();
            XHR.addEventListener("load", function (e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(response.message || 'Failed to simulate verification.', 'error');
                    }
                } catch (err) {
                    showToast('An error occurred during simulation.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/approve_kyc_mock.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            XHR.send("csrf_token=" + encodeURIComponent(window.csrf_token));

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // Update Pay Rent Screenshot input label when a file is selected
    var pay_screenshot = document.getElementById("pay-screenshot");
    if (pay_screenshot) {
        pay_screenshot.addEventListener("change", function (e) {
            var files = e.target.files;
            var label = document.getElementById("pay-screenshot-label");
            if (label && files.length > 0) {
                label.textContent = files[0].name;
            } else if (label) {
                label.textContent = "Choose receipt file...";
            }
        });
    }

    // Handle "Pay Rent" button click to initialize QR Code and modal
    var pay_rent_btns = document.getElementsByClassName("seeker-pay-rent-btn");
    Array.from(pay_rent_btns).forEach(btn => {
        btn.addEventListener("click", function (event) {
            var bookingId = btn.getAttribute("data-booking-id");
            var propertyName = btn.getAttribute("data-property-name");
            var rent = btn.getAttribute("data-rent");
            var ownerName = btn.getAttribute("data-owner-name");
            var ownerUpi = btn.getAttribute("data-owner-upi");

            // Populate text details
            document.getElementById("pay-booking-id").value = bookingId;
            document.getElementById("pay-property-name").textContent = propertyName;
            document.getElementById("pay-owner-name").textContent = ownerName;
            document.getElementById("pay-rent-amount").textContent = Number(rent).toLocaleString('en-IN');
            document.getElementById("pay-owner-upi").textContent = ownerUpi;

            // Generate UPI pay URL
            var upiUrl = "upi://pay?pa=" + encodeURIComponent(ownerUpi) + 
                         "&pn=" + encodeURIComponent(ownerName) + 
                         "&am=" + encodeURIComponent(rent) + 
                         "&cu=INR&tn=" + encodeURIComponent("PGLife Rent BookingRef " + bookingId);

            // Populate mobile deep link
            var mobileLink = document.getElementById("pay-upi-deep-link");
            if (mobileLink) {
                mobileLink.setAttribute("href", upiUrl);
            }

            // Draw QR Code
            var qrContainer = document.getElementById("pay-qrcode-container");
            if (qrContainer) {
                qrContainer.innerHTML = "";
                if (window.QRCode) {
                    new window.QRCode(qrContainer, {
                        text: upiUrl,
                        width: 150,
                        height: 150,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : window.QRCode.CorrectLevel.M
                    });
                } else {
                    qrContainer.innerHTML = "<p class='text-danger font-weight-bold my-4' style='font-size:12px;'>QR Code library failing to load. Deep link option remains active.</p>";
                }
            }

            // Reset inputs
            document.getElementById("pay-utr").value = "";
            var screenshotInput = document.getElementById("pay-screenshot");
            if (screenshotInput) screenshotInput.value = "";
            var screenshotLabel = document.getElementById("pay-screenshot-label");
            if (screenshotLabel) screenshotLabel.textContent = "Choose receipt file...";

            window.$("#pay-rent-modal").modal("show");
            event.preventDefault();
        });
    });

    // Handle AJAX submission of seeker payment proof
    var pay_rent_form = document.getElementById("pay-rent-form");
    if (pay_rent_form) {
        pay_rent_form.addEventListener("submit", function (event) {
            var XHR = new XMLHttpRequest();
            var form_data = new FormData(pay_rent_form);

            XHR.addEventListener("load", function (e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message || 'Payment proof submitted successfully!', 'success');
                        window.$("#pay-rent-modal").modal("hide");
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        showToast(response.message || 'Failed to submit payment proof.', 'error');
                    }
                } catch (err) {
                    showToast('An error occurred during submission.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/submit_payment.php");
            XHR.send(form_data);

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    }

    // Handle Owner Rent Ledger Verification / Rejection buttons
    var owner_verify_btns = document.getElementsByClassName("owner-verify-payment-btn");
    Array.from(owner_verify_btns).forEach(btn => {
        btn.addEventListener("click", function (event) {
            var paymentId = btn.getAttribute("data-payment-id");
            var action = btn.getAttribute("data-action");
            var confirmMsg = action === 'approve' ? 'Are you sure you want to verify this payment?' : 'Are you sure you want to reject this payment?';

            if (!confirm(confirmMsg)) {
                return;
            }

            var XHR = new XMLHttpRequest();
            XHR.addEventListener("load", function (e) {
                document.getElementById("loading").style.display = 'none';
                try {
                    var response = JSON.parse(e.target.responseText);
                    if (response.success) {
                        showToast(response.message, 'success');
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        showToast(response.message || 'Failed to update payment status.', 'error');
                    }
                } catch (err) {
                    showToast('An error occurred.', 'error');
                }
            });
            XHR.addEventListener("error", on_error);

            XHR.open("POST", "api/verify_payment.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            XHR.send("payment_id=" + paymentId + "&action=" + action + "&csrf_token=" + encodeURIComponent(window.csrf_token));

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    });
});

var remove_card_smoothly = function (card) {
    if (!card) return;
    card.classList.add("fade-out");
    card.addEventListener("transitionend", function () {
        card.style.display = 'none';
    }, { once: true });
    // Fallback if transitionend doesn't trigger
    setTimeout(function () {
        card.style.display = 'none';
    }, 550);
};

var remove_interested_success = function (event) {
    document.getElementById("loading").style.display = 'none';

    var response = JSON.parse(event.target.responseText);
    if(response.success) {
        var property_id = response.property_id;
        var card = document.querySelector(".interested-properties .property-id-" + property_id);
        if (card) {
            remove_card_smoothly(card);
        } else {
            var fallback = document.getElementsByClassName("property-id-" + property_id)[0];
            if (fallback) remove_card_smoothly(fallback);
        }
    } else {
        showToast(response.message || 'Failed to remove from interested properties.', 'error');
    }
};

var cancel_booking_success = function (event) {
    document.getElementById("loading").style.display = 'none';

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        var property_id = response.property_id;
        var card = document.querySelector(".booked-properties .property-id-" + property_id);
        if (card) {
            remove_card_smoothly(card);
        } else {
            var fallback = document.getElementsByClassName("property-id-" + property_id)[0];
            if (fallback) remove_card_smoothly(fallback);
        }
    } else {
        showToast(response.message || 'Failed to cancel booking.', 'error');
    }
};

var edit_profile_success = function (event) {
    document.getElementById("loading").style.display = 'none';

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        showToast(response.message || 'Profile updated successfully!', 'success');
        setTimeout(function() {
            var el = document.body;
            el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(function() { location.reload(); }, 250);
        }, 750);
    } else {
        showToast(response.message || 'Failed to update profile.', 'error');
    }
};

function escapeHtml(text) {
    if (!text) return "";
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

function addPrimaryBadge(card) {
    if (!card) return;
    var existingBadge = card.querySelector(".primary-badge");
    if (existingBadge) return;
    var badge = document.createElement("span");
    badge.className = "primary-badge";
    badge.innerText = "Primary";
    card.appendChild(badge);
}

// ═══════════════════════════════════════════════════════════════
// ROOM TYPES MANAGEMENT (Feature 5: Smart Availability)
// ═══════════════════════════════════════════════════════════════

var roomsDeletedIds = [];

// Room type options
var ROOM_TYPES = [
    { value: 'single',    label: 'Single Sharing' },
    { value: 'double',    label: 'Double Sharing' },
    { value: 'triple',    label: 'Triple Sharing' },
    { value: 'dormitory', label: 'Dormitory' },
    { value: 'private',   label: 'Private Room' }
];

// Open the Room Management Modal
$(document).on('click', '.manage-rooms-btn', function () {
    var propId   = $(this).data('property-id');
    var propName = $(this).data('property-name');
    var rooms    = $(this).data('rooms') || [];

    $('#rooms-modal-property-id').val(propId);
    $('#rooms-modal-property-name').text(propName);
    roomsDeletedIds = [];
    renderRoomRows(rooms);
    $('#rooms-save-feedback').hide();
    $('#manage-rooms-modal').modal('show');
});

// Add a new empty room row
$('#add-room-row-btn').click(function () {
    addRoomRow({});
});

// Render all existing rooms
function renderRoomRows(rooms) {
    $('#rooms-modal-list').empty();
    if (!rooms || rooms.length === 0) {
        addRoomRow({});
    } else {
        rooms.forEach(function(room) { addRoomRow(room); });
    }
}

// Build and append one room row
function addRoomRow(room) {
    var id = room.id || '';
    var typeOptions = ROOM_TYPES.map(function(t) {
        var sel = (room.room_type === t.value) ? ' selected' : '';
        return '<option value="' + t.value + '"' + sel + '>' + t.label + '</option>';
    }).join('');

    var row = $(
        '<div class="room-manager-row" data-room-id="' + escapeHtml(String(id)) + '">' +
            '<select class="rm-type" title="Room Type">' + typeOptions + '</select>' +
            '<input type="text" class="rm-label flex-grow-1" placeholder="Label (e.g. AC Single)" value="' + escapeHtml(room.label || '') + '" style="flex:1; min-width:120px;">' +
            '<input type="number" class="rm-price" placeholder="₹/mo" value="' + (room.price_per_month || '') + '" style="width:90px;" min="0">' +
            '<input type="number" class="rm-total" placeholder="Total beds" value="' + (room.total_beds || 1) + '" style="width:80px;" min="1">' +
            '<input type="number" class="rm-occupied" placeholder="Occupied" value="' + (room.occupied_beds || 0) + '" style="width:80px;" min="0">' +
            '<input type="text" class="rm-amenities" placeholder="Amenities (comma-sep)" value="' + escapeHtml(room.amenities || '') + '" style="min-width:120px; flex:1;">' +
            '<button type="button" class="btn btn-sm btn-outline-danger rm-delete-btn" title="Remove"><i class="fas fa-trash-alt"></i></button>' +
        '</div>'
    );
    $('#rooms-modal-list').append(row);
}

// Delete a room row
$(document).on('click', '.rm-delete-btn', function () {
    var row   = $(this).closest('.room-manager-row');
    var rowId = parseInt(row.data('room-id'));
    if (rowId > 0) roomsDeletedIds.push(rowId);
    row.remove();
});

// Save room types
$('#save-rooms-btn').click(function () {
    var propId = $('#rooms-modal-property-id').val();
    var roomsData = [];
    var valid = true;

    $('#rooms-modal-list .room-manager-row').each(function () {
        var label   = $(this).find('.rm-label').val().trim();
        var price   = parseFloat($(this).find('.rm-price').val());
        var total   = parseInt($(this).find('.rm-total').val());
        var occupied = parseInt($(this).find('.rm-occupied').val());
        var id      = parseInt($(this).data('room-id')) || 0;

        if (!label || isNaN(price) || price <= 0) { valid = false; return; }
        if (isNaN(total) || total < 1) { valid = false; return; }

        roomsData.push({
            id:             id,
            room_type:      $(this).find('.rm-type').val(),
            label:          label,
            price_per_month: price,
            total_beds:     total,
            occupied_beds:  Math.min(occupied || 0, total),
            amenities:      $(this).find('.rm-amenities').val().trim(),
            is_active:      1
        });
    });

    if (!valid) {
        alert('Please fill all required fields (Label and Price) for each room type.');
        return;
    }

    $('#save-rooms-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving...');

    $.ajax({
        url: 'api/update_room_availability.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'save_room_types',
            property_id: propId,
            room_types_data: JSON.stringify(roomsData),
            deleted_ids: JSON.stringify(roomsDeletedIds),
            csrf_token: window.csrfToken
        },
        success: function (res) {
            $('#save-rooms-btn').prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Save Room Types');
            if (res.success) {
                $('#rooms-save-feedback').text('✓ ' + res.message).show();
                setTimeout(function () {
                    $('#manage-rooms-modal').modal('hide');
                    location.reload();
                }, 1200);
            } else {
                alert(res.message || 'Could not save room types.');
            }
        },
        error: function () {
            $('#save-rooms-btn').prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Save Room Types');
            alert('Server error. Please try again.');
        }
    });
});