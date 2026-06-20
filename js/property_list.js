window.addEventListener("load", function () {
    var is_interested_images = document.getElementsByClassName("is-interested-image");
    Array.from(is_interested_images).forEach(element => {
        element.addEventListener("click", function (event) {
            var XHR = new XMLHttpRequest();
            var property_id = event.target.getAttribute("property_id");

            // On success
            XHR.addEventListener("load", toggle_interested_success);

            // On error
            XHR.addEventListener("error", on_error);

            // Set up request
            XHR.open("POST", "api/toggle_interested.php");
            XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            // Initiate the request
            XHR.send("property_id=" + encodeURIComponent(property_id) + "&csrf_token=" + encodeURIComponent(window.csrf_token));

            document.getElementById("loading").style.display = 'block';
            event.preventDefault();
        });
    });

    // Client-side Filtering Variables
    var selected_gender = "all";
    var keyword_input = document.getElementById("search-keyword");
    var rent_slider = document.getElementById("rent-range");
    var rent_val_lbl = document.getElementById("rent-range-val");

    // Gender Filter Clicks (Modal)
    var filter_buttons = document.querySelectorAll("#gender-filter-group .filter-btn");
    filter_buttons.forEach(btn => {
        btn.addEventListener("click", function (event) {
            filter_buttons.forEach(b => b.classList.remove("btn-active"));
            this.classList.add("btn-active");
            selected_gender = this.getAttribute("data-gender");
            event.preventDefault();
        });
    });

    // Apply Filters Function
    function filterProperties() {
        var keyword = keyword_input ? keyword_input.value.toLowerCase().trim() : "";
        var max_rent = rent_slider ? parseInt(rent_slider.value) : 50000;
        
        var checked_amenities = [];
        var chks = document.querySelectorAll(".amenity-filter-chk:checked");
        chks.forEach(chk => {
            checked_amenities.push(chk.getAttribute("data-amenity"));
        });

        var property_cards = document.querySelectorAll("#properties-container .property-card");
        var visible_count = 0;

        property_cards.forEach(card => {
            var card_name = card.querySelector(".property-name").textContent.toLowerCase();
            var card_address = card.querySelector(".property-address").textContent.toLowerCase();
            var card_rent = parseInt(card.getAttribute("data-rent"));
            var card_gender = card.getAttribute("data-gender");
            var card_amenities = card.getAttribute("data-amenities").split(",");

            // Check Keyword
            var keyword_matches = (keyword === "" || card_name.includes(keyword) || card_address.includes(keyword));
            // Check Rent
            var rent_matches = (card_rent <= max_rent);
            // Check Gender
            var gender_matches = (selected_gender === "all" || card_gender === selected_gender);
            // Check Amenities
            var amenities_match = true;
            checked_amenities.forEach(amenity => {
                if (!card_amenities.includes(amenity)) {
                    amenities_match = false;
                }
            });

            if (keyword_matches && rent_matches && gender_matches && amenities_match) {
                card.style.setProperty("display", "flex", "important");
                visible_count++;
            } else {
                card.style.setProperty("display", "none", "important");
            }
        });

        var no_property = document.querySelector(".no-property-container");
        if (no_property) {
            no_property.style.display = (visible_count === 0) ? "block" : "none";
        }
    }

    // Bind Filter Events
    if (keyword_input) {
        keyword_input.addEventListener("input", filterProperties);
    }
    if (rent_slider) {
        rent_slider.addEventListener("input", function () {
            if (rent_val_lbl) {
                rent_val_lbl.textContent = "₹" + parseInt(this.value).toLocaleString('en-IN');
            }
            filterProperties();
        });
    }
    
    var filter_okay_btn = document.getElementById("filter-okay-btn");
    if (filter_okay_btn) {
        filter_okay_btn.addEventListener("click", filterProperties);
    }

    var chk_boxes = document.querySelectorAll(".amenity-filter-chk");
    chk_boxes.forEach(chk => {
        chk.addEventListener("change", filterProperties);
    });

    // Prevent dropdown from closing when clicking inside it
    var dropdown_menu = document.querySelector(".dropdown-menu");
    if (dropdown_menu) {
        dropdown_menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    }

    // Client-side Sorting
    var sort_desc_btn = document.getElementById("sort-desc");
    if (sort_desc_btn) {
        sort_desc_btn.addEventListener("click", function () {
            sortProperties(true);
        });
    }

    var sort_asc_btn = document.getElementById("sort-asc");
    if (sort_asc_btn) {
        sort_asc_btn.addEventListener("click", function () {
            sortProperties(false);
        });
    }
});

function sortProperties(descending) {
    var container = document.getElementById("properties-container");
    if (!container) return;

    var cards = Array.from(container.getElementsByClassName("property-card"));
    cards.sort(function (a, b) {
        var rentA = parseInt(a.getAttribute("data-rent"));
        var rentB = parseInt(b.getAttribute("data-rent"));
        return descending ? (rentB - rentA) : (rentA - rentB);
    });

    cards.forEach(card => {
        container.appendChild(card);
    });
}

var toggle_interested_success = function (event) {
    document.getElementById("loading").style.display = 'none';

    var response = JSON.parse(event.target.responseText);
    if (response.success) {
        var property_id = response.property_id;

        var is_interested_image = document.getElementsByClassName("property-id-" + property_id + " is-interested-image")[0];
        var interested_user_count = document.getElementsByClassName("property-id-" + property_id + " interested-user-count")[0];

        if (response.is_interested) {
            is_interested_image.classList.add("fas");
            is_interested_image.classList.remove("far");
            if (interested_user_count) {
                interested_user_count.innerHTML = parseFloat(interested_user_count.innerHTML) + 1;
            }
        } else {
            is_interested_image.classList.add("far");
            is_interested_image.classList.remove("fas");
            if (interested_user_count) {
                interested_user_count.innerHTML = parseFloat(interested_user_count.innerHTML) - 1;
            }
        }
    } else if (!response.success && !response.is_logged_in) {
        window.$("#login-modal").modal("show");
    }
};
