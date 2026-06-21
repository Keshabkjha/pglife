<?php
    require_once "includes/database_connect.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | PG Life</title>

    <?php 
        include "includes/head_links.php";
    ?>

    <link href="css/home.css?v=2" rel="stylesheet">
</head>

<body>
    <?php
        include "includes/header.php";
    ?>

    <div class="landing-image">
        <div class="search-box">
            <h2 class="white text-center">
                Happiness per Square Foot
            </h2>
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

    <div class="page-container">
        <h1 class="text-center">
            Explore PGs in Major Cities
        </h1>
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
    </div>

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