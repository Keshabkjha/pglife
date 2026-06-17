<?php
    session_start();
    require "includes/database_connect.php";

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
    $city_name = isset($_GET["city"]) ? trim($_GET["city"]) : '';

    if (empty($city_name)) {
        echo "City name is required.";
        return;
    }

    $sql_1 = "SELECT * FROM cities WHERE name = ?";
    $stmt_1 = mysqli_prepare($conn, $sql_1);
    if (!$stmt_1) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_1, "s", $city_name);
    mysqli_stmt_execute($stmt_1);
    $result_1 = mysqli_stmt_get_result($stmt_1);
    if (!$result_1) {
        echo "Something went wrong!";
        return;
    }

    $city = mysqli_fetch_assoc($result_1);
    if (!$city) {
        echo "Sorry! We do not have any PG listed in this city.";
        return;
    }

    $city_id = $city['id'];

    $sql_2 = "SELECT * FROM properties WHERE city_id = ?";
    $stmt_2 = mysqli_prepare($conn, $sql_2);
    if (!$stmt_2) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_2, "i", $city_id);
    mysqli_stmt_execute($stmt_2);
    $result_2 = mysqli_stmt_get_result($stmt_2);
    if (!$result_2) {
        echo "Something went wrong!";
        return;
    }

    $properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

    $sql_3 = "SELECT iup.* 
                FROM interested_users_properties iup
                INNER JOIN properties p ON iup.property_id = p.id
                WHERE p.city_id = ?";
    $stmt_3 = mysqli_prepare($conn, $sql_3);
    if (!$stmt_3) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_3, "i", $city_id);
    mysqli_stmt_execute($stmt_3);
    $result_3 = mysqli_stmt_get_result($stmt_3);
    if (!$result_3) {
        echo "Something went wrong!";
        return;
    }

    $interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Best PG's in <?= htmlspecialchars($city['name']); ?> | PG Life</title>

    <?php 
        include "includes/head_links.php";
    ?>

    <link href="css/property_list.css" rel="stylesheet" />
</head>

<body>
    <?php
        include "includes/header.php";
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2">
            <li class="breadcrumb-item">
                <a href="home.php">Home</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($city_name); ?>
            </li>
        </ol>
    </nav>

    <div class="page-container">
        <div class="filter-bar row justify-content-around">
            <div class="col-auto" data-toggle="modal" data-target="#filter-modal" style="cursor: pointer;">
                <img src="img/filter.png" alt="filter" />
                <span>Filter</span>
            </div>
            <div class="col-auto" id="sort-desc" style="cursor: pointer;">
                <img src="img/desc.png" alt="sort-desc" />
                <span>Highest rent first</span>
            </div>
            <div class="col-auto" id="sort-asc" style="cursor: pointer;">
                <img src="img/asc.png" alt="sort-asc" />
                <span>Lowest rent first</span>
            </div>
        </div>

        <!-- Advanced Search and Filter Panel -->
        <div class="card my-3 p-3 bg-light border-0 shadow-sm rounded-lg" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);">
            <div class="row align-items-center">
                <!-- Keyword search -->
                <div class="col-md-5 mb-3 mb-md-0">
                    <div class="input-group">
                        <input type="text" id="search-keyword" class="form-control" placeholder="Search by PG name or address..." style="border-radius: 4px 0 0 4px;" />
                        <div class="input-group-append">
                            <span class="input-group-text bg-white border-left-0" style="border-radius: 0 4px 4px 0;"><i class="fa fa-search text-muted"></i></span>
                        </div>
                    </div>
                </div>
                <!-- Rent filter -->
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-3" style="font-size: 13px; white-space: nowrap;">Max Rent:</span>
                        <input type="range" id="rent-range" class="custom-range" min="0" max="15000" step="500" value="15000" style="accent-color: #343a40;" />
                        <span class="ml-3 font-weight-bold text-dark" id="rent-range-val" style="font-size: 14px; white-space: nowrap;">₹15,000</span>
                    </div>
                </div>
                <!-- Amenities dropdown filter -->
                <div class="col-md-3 text-md-right">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-dark dropdown-toggle w-100" type="button" id="amenitiesDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 4px; padding: 6px 12px;">
                            <i class="fas fa-filter mr-1"></i>Amenities
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="amenitiesDropdown" style="width: 250px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-wifi" data-amenity="Wifi">
                                <label class="custom-control-label" for="chk-wifi">Wifi</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-ac" data-amenity="Air Conditioner">
                                <label class="custom-control-label" for="chk-ac">Air Conditioner</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-power" data-amenity="Power Backup">
                                <label class="custom-control-label" for="chk-power">Power Backup</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-parking" data-amenity="Parking">
                                <label class="custom-control-label" for="chk-parking">Parking</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-washing" data-amenity="Washing Machine">
                                <label class="custom-control-label" for="chk-washing">Washing Machine</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="properties-container">
        <?php
            foreach ($properties as $property) {
                $property_images = glob("img/properties/" . $property['id'] . "/*");
                
                // Fetch amenities for this property to set data-amenities attribute
                $prop_id = $property['id'];
                $sql_amenities = "SELECT a.name FROM amenities a
                                  INNER JOIN properties_amenities pa ON a.id = pa.amenity_id
                                  WHERE pa.property_id = ?";
                $stmt_amenities = mysqli_prepare($conn, $sql_amenities);
                $prop_amenities_list = [];
                if ($stmt_amenities) {
                    mysqli_stmt_bind_param($stmt_amenities, "i", $prop_id);
                    mysqli_stmt_execute($stmt_amenities);
                    $res_amenities = mysqli_stmt_get_result($stmt_amenities);
                    if ($res_amenities) {
                        while ($row_a = mysqli_fetch_assoc($res_amenities)) {
                            $prop_amenities_list[] = $row_a['name'];
                        }
                    }
                }
                $prop_amenities_str = implode(",", $prop_amenities_list);
        ?>
            <div class="property-card property-id-<?= $property['id'] ?> row" data-rent="<?= $property['rent'] ?>" data-gender="<?= $property['gender'] ?>" data-amenities="<?= htmlspecialchars($prop_amenities_str) ?>">
                <div class="image-container col-md-4">
                    <img src="<?= $property_images[0] ?>" alt="<?= htmlspecialchars($property['name']) ?>" />
                </div>
                <div class="content-container col-md-8">
                    <div class="row no-gutters justify-content-between">
                        <?php
                            $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                            $total_rating = round($total_rating, 1);
                        ?>
                        <div class="star-container" title="<?= $total_rating ?>">
                            <?php
                                $rating = $total_rating;
                                for ($i = 0; $i < 5; $i++) {
                                    if ($rating >= $i + 0.8) {
                            ?>
                                    <i class="fas fa-star"></i>
                            <?php
                                } elseif ($rating >= $i + 0.3) {
                            ?>
                                    <i class="fas fa-star-half-alt"></i>
                            <?php
                                } else {
                            ?>
                                    <i class="far fa-star"></i>
                            <?php
                                    }
                                }
                            ?>
                        </div>
                        <div class="interested-container">
                            <?php
                                $interested_users_count = 0;
                                $is_interested = false;
                                foreach ($interested_users_properties as $interested_user_property) {
                                    if ($interested_user_property['property_id'] == $property['id']) {
                                        $interested_users_count++;

                                        if ($interested_user_property['user_id'] == $user_id) {
                                            $is_interested = true;
                                        }
                                    }
                                }

                                if ($is_interested) {
                            ?>
                                <i class="is-interested-image property-id-<?= $property['id'] ?> fas fa-heart" property_id="<?= $property['id'] ?>"></i>
                            <?php
                                } else {
                            ?>
                                <i class="is-interested-image property-id-<?= $property['id'] ?> far fa-heart" property_id="<?= $property['id'] ?>"></i>
                            <?php
                                }
                            ?>
                            <div class="interested-text">
                                <span class="interested-user-count property-id-<?= $property['id'] ?> "><?= $interested_users_count ?></span> interested
                            </div>
                        </div>
                    </div>
                    <div class="detail-container">
                        <div class="property-name"><?= htmlspecialchars($property['name']) ?></div>
                        <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
                        <div class="property-gender">
                            <?php
                                if ($property['gender'] == "male") {
                            ?>
                                <img src="img/male.png" alt="Male Only" />
                            <?php
                                } elseif ($property['gender'] == "female") {
                            ?>
                                <img src="img/female.png" alt="Female Only" />
                            <?php
                                } else {
                            ?>
                                <img src="img/unisex.png" alt="Unisex" />
                            <?php
                                }
                            ?>
                        </div>
                    </div>
                    <div class="row no-gutters">
                        <div class="rent-container col-6">
                            <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                            <div class="rent-unit">per month</div>
                        </div>
                        <div class="button-container col-6">
                            <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            }
        ?>
        </div>
        <?php
            if (count($properties) == 0) {
        ?>
            <div class="no-property-container">
                <p>No PG to list</p>
            </div>
        <?php
            }
        ?>
    </div>

    <div class="modal fade" id="filter-modal" tabindex="-1" role="dialog" aria-labelledby="filter-heading" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="filter-heading">Filters</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <h5>Gender</h5>
                    <hr />
                    <div id="gender-filter-group">
                        <button class="btn btn-outline-dark filter-btn btn-active" data-gender="all">
                            No Filter
                        </button>
                        <button class="btn btn-outline-dark filter-btn" data-gender="unisex">
                            <i class="fas fa-venus-mars"></i>Unisex
                        </button>
                        <button class="btn btn-outline-dark filter-btn" data-gender="male">
                            <i class="fas fa-mars"></i>Male
                        </button>
                        <button class="btn btn-outline-dark filter-btn" data-gender="female">
                            <i class="fas fa-venus"></i>Female
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button data-dismiss="modal" class="btn btn-success" id="filter-okay-btn">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <?php
        include "includes/signup_modal.php";
        include "includes/login_modal.php";
        include "includes/footer.php";
    ?>

    <script type="text/javascript" src="js/property_list.js"></script>
</body>

</html>