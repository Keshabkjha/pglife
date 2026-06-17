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
        alert(response.message || 'Failed to remove from interested properties.');
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
        alert(response.message || 'Failed to cancel booking.');
    }
};

var edit_profile_success = function (event) {
    document.getElementById("loading").style.display = 'none';

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        alert(response.message);
        location.reload();
    } else {
        alert(response.message);
    }
};