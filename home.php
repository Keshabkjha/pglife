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
            Major Cities
        </h1>
        <div class="row">
            <div class="col-md-3 city-container">
                <a href="property_list.php?city=Delhi" >
                    <div class="city">
                        <img src="img/delhi.png" />
                    </div>
                </a>
            </div>
            <div class="col-md-3 city-container">
                <a href="property_list.php?city=Mumbai" >
                    <div class="city">
                        <img  src="img/mumbai.png" />
                    </div>
                </a>
            </div>
            <div class="col-md-3 city-container">
                <a href="property_list.php?city=Bengaluru" >
                    <div class="city">
                        <img src="img/bangalore.png" />
                    </div>
                </a>
            </div>
            <div class="col-md-3 city-container">
                <a href="property_list.php?city=Hyderabad" >
                    <div class="city">
                        <img src="img/hyderabad.png" />
                    </div>
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