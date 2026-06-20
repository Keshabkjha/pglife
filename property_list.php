<?php
    session_start();
    require "includes/database_connect.php";

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
    $city_name = isset($_GET["city"]) ? trim($_GET["city"]) : '';

    if (empty($city_name)) {
        header("Location: /home");
        exit;
    }

    $sql_1 = "SELECT * FROM cities WHERE name = ?";
    $stmt_1 = mysqli_prepare($conn, $sql_1);
    if (!$stmt_1) {
        header("Location: /home");
        exit;
    }
    mysqli_stmt_bind_param($stmt_1, "s", $city_name);
    mysqli_stmt_execute($stmt_1);
    $result_1 = mysqli_stmt_get_result($stmt_1);
    if (!$result_1) {
        mysqli_stmt_close($stmt_1);
        header("Location: /home");
        exit;
    }

    $city = mysqli_fetch_assoc($result_1);
    mysqli_stmt_close($stmt_1);
    if (!$city) {
        header("Location: /home");
        exit;
    }

    $city_id = $city['id'];

    $sql_2 = "SELECT p.*, GROUP_CONCAT(a.name) AS amenities_list 
              FROM properties p 
              LEFT JOIN properties_amenities pa ON p.id = pa.property_id 
              LEFT JOIN amenities a ON pa.amenity_id = a.id 
              WHERE p.city_id = ? 
              GROUP BY p.id";
    $stmt_2 = mysqli_prepare($conn, $sql_2);
    if (!$stmt_2) {
        header("Location: /home");
        exit;
    }
    mysqli_stmt_bind_param($stmt_2, "i", $city_id);
    mysqli_stmt_execute($stmt_2);
    $result_2 = mysqli_stmt_get_result($stmt_2);
    if (!$result_2) {
        mysqli_stmt_close($stmt_2);
        header("Location: /home");
        exit;
    }

    $properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_2);

    $sql_3 = "SELECT iup.* 
                FROM interested_users_properties iup
                INNER JOIN properties p ON iup.property_id = p.id
                WHERE p.city_id = ?";
    $stmt_3 = mysqli_prepare($conn, $sql_3);
    if (!$stmt_3) {
        header("Location: /home");
        exit;
    }
    mysqli_stmt_bind_param($stmt_3, "i", $city_id);
    mysqli_stmt_execute($stmt_3);
    $result_3 = mysqli_stmt_get_result($stmt_3);
    if (!$result_3) {
        mysqli_stmt_close($stmt_3);
        header("Location: /home");
        exit;
    }

    $interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_3);

    // Pre-index interested users data for O(1) lookups during rendering
    $interested_map = [];
    foreach ($interested_users_properties as $iup) {
        $pid = (int)$iup['property_id'];
        $uid = (int)$iup['user_id'];
        if (!isset($interested_map[$pid])) {
            $interested_map[$pid] = [
                'count' => 0,
                'user_ids' => []
            ];
        }
        $interested_map[$pid]['count']++;
        $interested_map[$pid]['user_ids'][] = $uid;
    }

    // Fetch room availability summary per property in this city
    $sql_rooms = "SELECT rt.property_id,
                         SUM(rt.total_beds) AS total_beds,
                         SUM(rt.occupied_beds) AS occupied_beds,
                         SUM(rt.total_beds - rt.occupied_beds) AS available_beds,
                         COUNT(*) AS room_type_count
                   FROM room_types rt
                   INNER JOIN properties p ON rt.property_id = p.id
                   WHERE p.city_id = ? AND rt.is_active = 1
                   GROUP BY rt.property_id";
    $stmt_rooms = mysqli_prepare($conn, $sql_rooms);
    $room_avail_map = [];
    if ($stmt_rooms) {
        mysqli_stmt_bind_param($stmt_rooms, 'i', $city_id);
        mysqli_stmt_execute($stmt_rooms);
        $res_rooms = mysqli_stmt_get_result($stmt_rooms);
        if ($res_rooms) {
            while ($ra = mysqli_fetch_assoc($res_rooms)) {
                $room_avail_map[(int)$ra['property_id']] = $ra;
            }
        }
        mysqli_stmt_close($stmt_rooms);
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Best PG's in <?= htmlspecialchars($city['name']); ?> | PG Life</title>
    <meta name="description" content="Discover and book the best Paying Guest (PG) accommodations in <?= htmlspecialchars($city['name']); ?>. Filter by gender, rent, and amenities like WiFi, AC, laundry, and meals.">
    <meta name="keywords" content="PG in <?= htmlspecialchars($city['name']); ?>, Paying Guest <?= htmlspecialchars($city['name']); ?>, hostel <?= htmlspecialchars($city['name']); ?>, student accommodation, premium co-living <?= htmlspecialchars($city['name']); ?>">

    <?php 
        include "includes/head_links.php";
    ?>

    <link href="css/property_list.css?v=2" rel="stylesheet" />
</head>

<body>
    <?php
        include "includes/header.php";
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2">
            <li class="breadcrumb-item">
                <a href="/home">Home</a>
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
                        <input type="range" id="rent-range" class="custom-range" min="0" max="50000" step="1000" value="50000" style="accent-color: var(--primary-color);" />
                        <span class="ml-3 font-weight-bold text-dark" id="rent-range-val" style="font-size: 14px; white-space: nowrap;">₹50,000</span>
                    </div>
                </div>
                <!-- Amenities dropdown filter -->
                <div class="col-md-3 text-md-right">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-dark dropdown-toggle w-100" type="button" id="amenitiesDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 4px; padding: 6px 12px;">
                            <i class="fas fa-filter mr-1"></i>Amenities
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="amenitiesDropdown" style="width: 260px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-wifi" data-amenity="Wifi">
                                <label class="custom-control-label" for="chk-wifi"><i class="fas fa-wifi mr-1 text-muted"></i> Wifi</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-ac" data-amenity="Air Conditioner">
                                <label class="custom-control-label" for="chk-ac"><i class="fas fa-snowflake mr-1 text-muted"></i> Air Conditioner</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-power" data-amenity="Power Backup">
                                <label class="custom-control-label" for="chk-power"><i class="fas fa-bolt mr-1 text-muted"></i> Power Backup</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-parking" data-amenity="Parking">
                                <label class="custom-control-label" for="chk-parking"><i class="fas fa-parking mr-1 text-muted"></i> Parking</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-washing" data-amenity="Washing Machine">
                                <label class="custom-control-label" for="chk-washing"><i class="fas fa-tshirt mr-1 text-muted"></i> Washing Machine</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-tv" data-amenity="TV">
                                <label class="custom-control-label" for="chk-tv"><i class="fas fa-tv mr-1 text-muted"></i> TV</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-geyser" data-amenity="Geyser">
                                <label class="custom-control-label" for="chk-geyser"><i class="fas fa-hot-tub mr-1 text-muted"></i> Geyser</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-dining" data-amenity="Dining">
                                <label class="custom-control-label" for="chk-dining"><i class="fas fa-utensils mr-1 text-muted"></i> Dining / Meals</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-water" data-amenity="Water Purifier">
                                <label class="custom-control-label" for="chk-water"><i class="fas fa-faucet mr-1 text-muted"></i> Water Purifier</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-bed" data-amenity="Bed with Mattress">
                                <label class="custom-control-label" for="chk-bed"><i class="fas fa-bed mr-1 text-muted"></i> Bed with Mattress</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-lift" data-amenity="Lift">
                                <label class="custom-control-label" for="chk-lift"><i class="fas fa-arrow-up mr-1 text-muted"></i> Lift</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-cctv" data-amenity="CCTV">
                                <label class="custom-control-label" for="chk-cctv"><i class="fas fa-video mr-1 text-muted"></i> CCTV</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input amenity-filter-chk" id="chk-fireext" data-amenity="Fire Extinguisher">
                                <label class="custom-control-label" for="chk-fireext"><i class="fas fa-fire-extinguisher mr-1 text-muted"></i> Fire Extinguisher</label>
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
                if (!empty($property['primary_image']) && !empty($property_images)) {
                    $primary_full_path = "img/properties/" . $property['id'] . "/" . $property['primary_image'];
                    $key = array_search($primary_full_path, $property_images);
                    if ($key !== false) {
                        unset($property_images[$key]);
                        array_unshift($property_images, $primary_full_path);
                    }
                }
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200"><rect width="100%" height="100%" fill="#f1f5f9"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="14" fill="#94a3b8">No Image Available</text></svg>';
                $image_src = !empty($property_images) ? $property_images[0] : 'data:image/svg+xml;base64,' . base64_encode($svg);
                
                // Fetch amenities from pre-grouped query results (avoids N+1 queries)
                $prop_amenities_str = $property['amenities_list'] ?: '';
        ?>
            <div class="property-card property-id-<?= $property['id'] ?> row" data-rent="<?= $property['rent'] ?>" data-gender="<?= $property['gender'] ?>" data-amenities="<?= htmlspecialchars($prop_amenities_str) ?>">
                <div class="image-container col-md-4">
                    <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($property['name']) ?>" />
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
                                $prop_id = (int)$property['id'];
                                $interested_data = isset($interested_map[$prop_id]) ? $interested_map[$prop_id] : ['count' => 0, 'user_ids' => []];
                                $interested_users_count = $interested_data['count'];
                                $is_interested = in_array($user_id, $interested_data['user_ids']);

                                if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
                                    if ($is_interested) {
                            ?>
                                    <i class="is-interested-image property-id-<?= $property['id'] ?> fas fa-heart" property_id="<?= $property['id'] ?>" aria-label="Remove from wishlist" role="button" tabindex="0"></i>
                            <?php
                                    } else {
                            ?>
                                    <i class="is-interested-image property-id-<?= $property['id'] ?> far fa-heart" property_id="<?= $property['id'] ?>" aria-label="Add to wishlist" role="button" tabindex="0"></i>
                            <?php
                                    }
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
                            <?php
                                $pid_l = (int)$property['id'];
                                if (isset($room_avail_map[$pid_l])) {
                                    $ra = $room_avail_map[$pid_l];
                                    $avail_l = max(0, (int)$ra['available_beds']);
                                    $avail_class = $avail_l === 0 ? 'badge-danger' : ($avail_l <= 2 ? 'badge-warning' : 'badge-success');
                                    $avail_text  = $avail_l === 0 ? 'Full' : $avail_l . ' beds free';
                            ?>
                            <span class="badge <?= $avail_class ?> mt-1 d-inline-block" style="font-size: 10px; border-radius: 6px; padding: 2px 6px;">
                                <i class="fas fa-bed mr-1"></i><?= $avail_text ?>
                            </span>
                            <?php } ?>
                        </div>
                        <div class="button-container col-6">
                            <a href="/pg/<?= $property['id'] ?>" class="btn btn-primary">View</a>
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