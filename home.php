<?php
    session_start();
    require_once "includes/database_connect.php";
    require_once "includes/seo_helper.php";
?>

<!DOCTYPE html>
<html lang="en-IN">

<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    seo_head([
        'title'       => 'PG Life — Find Paying Guest Accommodation in India | Delhi, Mumbai, Bengaluru & More',
        'description' => 'Find verified PG accommodations across 11 major Indian cities. Search, filter by amenities & rent, view live map, and book directly. Free for seekers & owners.',
        'canonical'   => SITE_URL . '/home',
        'og_title'    => 'PG Life — India\'s Trusted PG Finder',
        'og_desc'     => 'Discover and book Paying Guest (PG) rooms in Delhi, Mumbai, Bengaluru, Hyderabad & 7 more cities. Filter by WiFi, AC, meals and more.',
        'og_image'    => SITE_URL . '/img/bg.jpg',
        'keywords'    => 'PG accommodation India, paying guest, PG near me, PG in Delhi, PG in Mumbai, PG in Bengaluru, hostel rooms India, co-living India',
        'breadcrumbs' => [['name' => 'Home', 'url' => SITE_URL . '/home']],
        'schema'      => [schema_software_app(), schema_home_faq(), schema_howto_find_pg()],
    ]);
    ?>

    <?php include "includes/head_links.php"; ?>
    <link href="css/home.css?v=2" rel="stylesheet">
</head>

<body>
    <?php include "includes/header.php"; ?>

    <main id="main-content">
    <div class="landing-image" role="banner">
        <div class="search-box">
            <h1 class="white text-center" style="font-size:1.8rem;">
                Find Your Perfect PG Accommodation in India
            </h1>
            <p class="white text-center mb-3" style="font-size:1rem;opacity:.9;">Search across 11 cities &mdash; Filter by amenities, rent &amp; gender</p>
            <form id="search-form" onsubmit="handleSearch(event)">
                <div class="input-group">
                    <input class="form-control" type="text" name="city" id="search-city" placeholder="Enter your city to search for PGs" list="city-suggestions" autocomplete="off" />
                    <datalist id="city-suggestions">
                        <option value="Delhi">
                        <option value="Mumbai">
                        <option value="Bengaluru">
                        <option value="Hyderabad">
                        <option value="Kolkata">
                        <option value="Chennai">
                        <option value="Pune">
                        <option value="Ahmedabad">
                        <option value="Jaipur">
                        <option value="Noida">
                        <option value="Gurgaon">
                    </datalist>
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <section class="page-container" aria-label="Browse PG accommodations by city">
        <h2 class="text-center">
            Explore PGs in Major Indian Cities
        </h2>
        <div class="row justify-content-center">
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Delhi">
                    <div class="city">
                        <img src="img/delhi.png?v=2" alt="PG accommodations in Delhi" />
                    </div>
                    <div class="city-label">Delhi</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Mumbai">
                    <div class="city">
                        <img src="img/mumbai.png?v=2" alt="PG accommodations in Mumbai" />
                    </div>
                    <div class="city-label">Mumbai</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Bengaluru">
                    <div class="city">
                        <img src="img/bangalore.png?v=2" alt="PG accommodations in Bengaluru" />
                    </div>
                    <div class="city-label">Bengaluru</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Hyderabad">
                    <div class="city">
                        <img src="img/hyderabad.png?v=2" alt="PG accommodations in Hyderabad" />
                    </div>
                    <div class="city-label">Hyderabad</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Kolkata">
                    <div class="city">
                        <img src="img/kolkata.png?v=2" alt="PG accommodations in Kolkata" />
                    </div>
                    <div class="city-label">Kolkata</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Chennai">
                    <div class="city">
                        <img src="img/chennai.png?v=2" alt="PG accommodations in Chennai" />
                    </div>
                    <div class="city-label">Chennai</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Pune">
                    <div class="city">
                        <img src="img/pune.png?v=2" alt="PG accommodations in Pune" />
                    </div>
                    <div class="city-label">Pune</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Ahmedabad">
                    <div class="city">
                        <img src="img/ahmedabad.png?v=2" alt="PG accommodations in Ahmedabad" />
                    </div>
                    <div class="city-label">Ahmedabad</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Jaipur">
                    <div class="city">
                        <img src="img/jaipur.png?v=2" alt="PG accommodations in Jaipur" />
                    </div>
                    <div class="city-label">Jaipur</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Noida">
                    <div class="city">
                        <img src="img/noida.png?v=2" alt="PG accommodations in Noida" />
                    </div>
                    <div class="city-label">Noida</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="/properties/Gurgaon">
                    <div class="city">
                        <img src="img/gurgaon.png?v=2" alt="PG accommodations in Gurgaon" />
                    </div>
                    <div class="city-label">Gurgaon</div>
                </a>
            </div>
        </div>
    </section>

    <section class="page-container" aria-label="How PG Life works">
        <h2 class="text-center">How PG Life Works</h2>
        <div class="row justify-content-center mt-4">
            <div class="col-md-3 col-6 mb-4 text-center">
                <div class="how-it-works-icon"><i class="fas fa-search"></i></div>
                <h5 class="mt-3 font-weight-bold">Search City</h5>
                <p class="text-muted">Enter your city and browse verified PG listings with photos, rent, andamenities.</p>
            </div>
            <div class="col-md-3 col-6 mb-4 text-center">
                <div class="how-it-works-icon"><i class="fas fa-sliders-h"></i></div>
                <h5 class="mt-3 font-weight-bold">Filter & Compare</h5>
                <p class="text-muted">Filter by gender, max rent, WiFi, AC, meals, parking and 13 more amenities.</p>
            </div>
            <div class="col-md-3 col-6 mb-4 text-center">
                <div class="how-it-works-icon"><i class="fas fa-comments"></i></div>
                <h5 class="mt-3 font-weight-bold">Chat & Negotiate</h5>
                <p class="text-muted">Message owners directly, negotiate rent, and ask questions before booking.</p>
            </div>
            <div class="col-md-3 col-6 mb-4 text-center">
                <div class="how-it-works-icon"><i class="fas fa-calendar-check"></i></div>
                <h5 class="mt-3 font-weight-bold">Book & Move In</h5>
                <p class="text-muted">Book your preferred room, submit payment proof, and move into your new PG.</p>
            </div>
        </div>
    </section>

    <section class="page-container" aria-label="Popular PG searches">
        <h2 class="text-center">Popular PG Searches</h2>
        <div class="row justify-content-center mt-3">
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Delhi">PG in Delhi</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Mumbai">PG in Mumbai</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Bengaluru">PG in Bengaluru</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Hyderabad">PG in Hyderabad</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Kolkata">PG in Kolkata</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Chennai">PG in Chennai</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Pune">PG in Pune</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Ahmedabad">PG in Ahmedabad</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Jaipur">PG in Jaipur</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Noida">PG in Noida</a></div>
            <div class="col-md-2 col-4 mb-3 text-center"><a class="popular-search-link" href="/properties/Gurgaon">PG in Gurgaon</a></div>
        </div>
    </section>

    <section class="page-container" aria-label="Why choose PG Life">
        <h2 class="text-center">Why Choose PG Life for PG Accommodation?</h2>
        <div class="row justify-content-center mt-4">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <h5 class="font-weight-bold"><i class="fas fa-check-circle text-success mr-2"></i>100% Verified Listings</h5>
                    <p class="text-muted">Every property owner on PG Life goes through email verification. Optional KYC documents ensure maximum trust and safety for seekers.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <h5 class="font-weight-bold"><i class="fas fa-rupee-sign text-success mr-2"></i>Zero Platform Fees</h5>
                    <p class="text-muted">PG Life is completely free for both seekers and owners. No hidden charges, no commissions, no brokerage — just connect and book directly.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <h5 class="font-weight-bold"><i class="fas fa-map-marker-alt text-success mr-2"></i>Live Location Maps</h5>
                    <p class="text-muted">View exact property locations on interactive maps. Know the neighborhood, nearby metro stations, and commute routes before you book.</p>
                </div>
            </div>
        </div>
    </section>
    </main><!-- /#main-content -->

    <?php
        include "includes/signup_modal.php";
        include "includes/login_modal.php";
        include "includes/footer.php";
    ?>
    
    <script type="text/javascript" src="js/home.js"></script>
    <script>
        function handleSearch(e) {
            e.preventDefault();
            var city = document.getElementById('search-city').value.trim();
            if (city) navigateTo('/properties/' + encodeURIComponent(city));
        }
    </script>
</body>

</html>