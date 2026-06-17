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
        var propName = document.querySelector(".property-name") ? document.querySelector(".property-name").textContent : "PG Location";
        var propAddr = document.querySelector(".property-address") ? document.querySelector(".property-address").textContent : "";

        // vary mock coordinates slightly based on property_id
        if (property_id == 1) { lat = 28.6430; lng = 77.2150; }
        else if (property_id == 2) { lat = 28.6425; lng = 77.2120; }
        else if (property_id == 3) { lat = 19.1030; lng = 72.8270; }
        else if (property_id == 4) { lat = 19.2300; lng = 72.8340; }
        else if (property_id == 5) { lat = 19.2310; lng = 72.8580; }

        var map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map)
            .bindPopup('<strong>' + propName + '</strong><br>' + propAddr)
            .openPopup();
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
        alert(response.message);
        var btn_container = document.getElementById('book-now-btn').parentElement;
        btn_container.innerHTML = '<button class="btn btn-success btn-block" disabled>Booked</button>';
    } else if (!response.success && response.is_logged_in === false) {
        window.$('#login-modal').modal("show");
    } else {
        alert(response.message);
    }
};

var add_review_success = function (event) {
    document.getElementById("loading").style.display = "none";

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        alert(response.message);
        location.reload();
    } else {
        alert(response.message);
    }
};