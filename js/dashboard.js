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
            XHR.open("GET", "api/toggle_interested.php?property_id=" + property_id + "&csrf_token=" + window.csrf_token);

            // initiate the request
            XHR.send();

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

    // Update file input label when images are selected
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
                        setTimeout(function() { location.reload(); }, 1200);
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

                    div.innerHTML = "<div class='d-flex justify-content-between align-items-center mb-2'>" +
                                        "<strong style='font-size:14px;'>" + escapeHtml(review.user_name) + "</strong>" +
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
        setTimeout(function() { location.reload(); }, 1000);
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