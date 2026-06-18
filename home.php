<?php
    session_start();
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

    <link href="css/home.css" rel="stylesheet">
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
            <form id="search-form" action="property_list.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="text" name="city" id="search-city" placeholder="Enter your city to search for PGs" />
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
                <a href="property_list.php?city=Delhi">
                    <div class="city">
                        <img src="img/delhi.png" alt="Delhi" />
                    </div>
                    <div class="city-label">Delhi</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Mumbai">
                    <div class="city">
                        <img src="img/mumbai.png" alt="Mumbai" />
                    </div>
                    <div class="city-label">Mumbai</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Bengaluru">
                    <div class="city">
                        <img src="img/bangalore.png" alt="Bengaluru" />
                    </div>
                    <div class="city-label">Bengaluru</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Hyderabad">
                    <div class="city">
                        <img src="img/hyderabad.png" alt="Hyderabad" />
                    </div>
                    <div class="city-label">Hyderabad</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Kolkata">
                    <div class="city">
                        <img src="img/kolkata.png" alt="Kolkata" />
                    </div>
                    <div class="city-label">Kolkata</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Chennai">
                    <div class="city">
                        <img src="img/chennai.png" alt="Chennai" />
                    </div>
                    <div class="city-label">Chennai</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Pune">
                    <div class="city">
                        <img src="img/pune.png" alt="Pune" />
                    </div>
                    <div class="city-label">Pune</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Ahmedabad">
                    <div class="city">
                        <img src="img/ahmedabad.png" alt="Ahmedabad" />
                    </div>
                    <div class="city-label">Ahmedabad</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Jaipur">
                    <div class="city">
                        <img src="img/jaipur.png" alt="Jaipur" />
                    </div>
                    <div class="city-label">Jaipur</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Noida">
                    <div class="city">
                        <img src="img/noida.png" alt="Noida" />
                    </div>
                    <div class="city-label">Noida</div>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2 city-container">
                <a href="property_list.php?city=Gurgaon">
                    <div class="city">
                        <img src="img/gurgaon.png" alt="Gurgaon" />
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
</body>

</html>