<?php
    session_start();
    require("includes/database_connect.php");

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
    $property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

    if ($property_id <= 0) {
        header("Location: /home");
        exit;
    }

    $sql_1 = "SELECT p.*, p.id AS property_id, p.name AS property_name, c.name AS city_name, 
                     u.full_name AS owner_name, u.is_verified AS owner_verified, u.phone AS owner_phone
                FROM properties p
                INNER JOIN cities c ON p.city_id = c.id
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.id = ?";
    $stmt_1 = mysqli_prepare($conn, $sql_1);
    if (!$stmt_1) {
        header("Location: /home");
        exit;
    }
    mysqli_stmt_bind_param($stmt_1, "i", $property_id);
    mysqli_stmt_execute($stmt_1);
    $result_1 = mysqli_stmt_get_result($stmt_1);
    if(!$result_1){
        mysqli_stmt_close($stmt_1);
        header("Location: /home");
        exit;
    }

    $property = mysqli_fetch_assoc($result_1);
    mysqli_stmt_close($stmt_1);
    if(!$property){
        header("Location: /home");
        exit;
    }

    // Increment Views Count if not already viewed in this session
    $view_key = 'viewed_property_' . $property_id;
    if (!isset($_SESSION[$view_key])) {
        $sql_views = "UPDATE properties SET views = views + 1 WHERE id = ?";
        $stmt_views = mysqli_prepare($conn, $sql_views);
        if ($stmt_views) {
            mysqli_stmt_bind_param($stmt_views, "i", $property_id);
            mysqli_stmt_execute($stmt_views);
            mysqli_stmt_close($stmt_views);
        }
        $_SESSION[$view_key] = true;
    }

    $sql_2 = "SELECT * FROM testimonials WHERE property_id = ?";
    $stmt_2 = mysqli_prepare($conn, $sql_2);
    if (!$stmt_2) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_2, "i", $property_id);
    mysqli_stmt_execute($stmt_2);
    $result_2 = mysqli_stmt_get_result($stmt_2);
    if(!$result_2){
        echo "Something went wrong!";
        return;
    }

    $testimonials = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

    $sql_3 = "SELECT a.*
                FROM amenities a
                INNER JOIN properties_amenities pa ON a.id = pa.amenity_id
                WHERE pa.property_id = ?";
    $stmt_3 = mysqli_prepare($conn, $sql_3);
    if (!$stmt_3) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_3, "i", $property_id);
    mysqli_stmt_execute($stmt_3);
    $result_3 = mysqli_stmt_get_result($stmt_3);
    if(!$result_3){
        echo "Something went wrong!";
        return;
    }

    $amenities = mysqli_fetch_all($result_3, MYSQLI_ASSOC);

    $sql_4 = "SELECT * FROM interested_users_properties WHERE property_id = ?";
    $stmt_4 = mysqli_prepare($conn, $sql_4);
    if (!$stmt_4) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_4, "i", $property_id);
    mysqli_stmt_execute($stmt_4);
    $result_4 = mysqli_stmt_get_result($stmt_4);
    if(!$result_4){
        echo "Something went wrong!";
        return;
    }

    $interested_users_count = mysqli_num_rows($result_4);
    $interested_users = mysqli_fetch_all($result_4, MYSQLI_ASSOC);

    $is_booked = false;
    if ($user_id) {
        $sql_booked = "SELECT * FROM bookings WHERE user_id = ? AND property_id = ?";
        $stmt_booked = mysqli_prepare($conn, $sql_booked);
        if ($stmt_booked) {
            mysqli_stmt_bind_param($stmt_booked, "ii", $user_id, $property_id);
            mysqli_stmt_execute($stmt_booked);
            $result_booked = mysqli_stmt_get_result($stmt_booked);
            if ($result_booked && mysqli_num_rows($result_booked) > 0) {
                $is_booked = true;
            }
        }
    }

    // Fetch user reviews
    $sql_reviews = "SELECT r.*, u.gender, u.profile_pic, u.is_verified FROM reviews r INNER JOIN users u ON r.user_id = u.id WHERE r.property_id = ? ORDER BY r.created_at DESC";
    $stmt_reviews = mysqli_prepare($conn, $sql_reviews);
    $reviews = [];
    if ($stmt_reviews) {
        mysqli_stmt_bind_param($stmt_reviews, "i", $property_id);
        mysqli_stmt_execute($stmt_reviews);
        $result_reviews = mysqli_stmt_get_result($stmt_reviews);
        if ($result_reviews) {
            $reviews = mysqli_fetch_all($result_reviews, MYSQLI_ASSOC);
        }
    }

    // Fetch room types for this property
    $room_types = [];
    $sql_rooms = "SELECT id, room_type, label, price_per_month, total_beds, occupied_beds, amenities, is_active FROM room_types WHERE property_id = ? AND is_active = 1 ORDER BY price_per_month ASC";
    $stmt_rooms = mysqli_prepare($conn, $sql_rooms);
    if ($stmt_rooms) {
        mysqli_stmt_bind_param($stmt_rooms, "i", $property_id);
        mysqli_stmt_execute($stmt_rooms);
        $result_rooms = mysqli_stmt_get_result($stmt_rooms);
        if ($result_rooms) {
            while ($row = mysqli_fetch_assoc($result_rooms)) {
                $row['available_beds'] = max(0, (int)$row['total_beds'] - (int)$row['occupied_beds']);
                $room_types[] = $row;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($property['property_name']); ?> in <?= htmlspecialchars($property['city_name']); ?> | PG Life</title>
    <meta name="description" content="View details for <?= htmlspecialchars($property['property_name']); ?> located at <?= htmlspecialchars($property['address']); ?>. Monthly rent: ₹<?= number_format($property['rent']); ?>. Check available amenities, reviews, and interactive location map.">
    <meta name="keywords" content="<?= htmlspecialchars($property['property_name']); ?>, PG in <?= htmlspecialchars($property['city_name']); ?>, paying guest, hostel rooms, rent in <?= htmlspecialchars($property['city_name']); ?>">

    <?php 
        include "includes/head_links.php";
    ?>

    <!-- Leaflet.js CSS for Interactive Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <link href="css/property_detail.css?v=2" rel="stylesheet" />
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
            <li class="breadcrumb-item">
                <a href="/properties/<?= urlencode($property['city_name']); ?>"><?= htmlspecialchars($property['city_name']); ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($property['property_name']); ?>
            </li>
        </ol>
    </nav>

    <div id="property-images" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <?php
                $property_images = glob("img/properties/".$property['property_id']."/*" );
                if (!empty($property['primary_image']) && !empty($property_images)) {
                    $primary_full_path = "img/properties/".$property['property_id']."/".$property['primary_image'];
                    $key = array_search($primary_full_path, $property_images);
                    if ($key !== false) {
                        unset($property_images[$key]);
                        array_unshift($property_images, $primary_full_path);
                    }
                }
                if (empty($property_images)) {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="400" viewBox="0 0 800 400"><rect width="100%" height="100%" fill="#f1f5f9"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="20" fill="#94a3b8">No Image Available</text></svg>';
                    $property_images = ['data:image/svg+xml;base64,' . base64_encode($svg)];
                }
                foreach ($property_images as $index => $property_image) {
            ?>
            <li data-target="#property-images" data-slide-to="<?= $index; ?>" class="<?= $index == 0 ? "active":""; ?>"></li>
            <?php
                }
            ?>
        </ol>
        <div class="carousel-inner">
            <?php
            foreach($property_images as $index => $property_image) {
            ?>
            <div class="carousel-item <?= $index == 0? "active":"" ?>">
                <img class="d-block w-100" src="<?= htmlspecialchars($property_image) ?>" alt="Slide <?= $index + 1 ?>" <?= $index > 0 ? 'loading="lazy"' : '' ?>>
            </div>
            <?php
                }
            ?>
        </div>
        <a class="carousel-control-prev" href="#property-images" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#property-images" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>

    <div class="property-summary page-container">
        <div class="row no-gutters justify-content-between">
            <?php
                $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                $total_rating = round($total_rating, 1);
            ?>
            <div class="star-container" title="<?= $total_rating ?>">
                <?php
                    $rating = $total_rating;
                    for($i = 0; $i < 5; $i++) {
                        if($rating >= $i + 0.8) {
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
                    $is_interested = false;
                    foreach($interested_users as $interested_user) {
                        if($interested_user['user_id'] == $user_id) {
                            $is_interested = true;
                        }
                    }
                    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
                        if ($is_interested) {
                ?>
                        <i class="is-interested-image fas fa-heart" aria-label="Remove from wishlist" role="button" tabindex="0"></i>
                <?php 
                        } else {
                ?>
                        <i class="is-interested-image far fa-heart" aria-label="Add to wishlist" role="button" tabindex="0"></i>
                <?php 
                        }
                    }
                ?>
                <div class="interested-text">
                    <span class="interested-user-count"><?= $interested_users_count; ?></span> interested
                </div>
            </div>
        </div>
        <div class="detail-container">
            <div class="property-name"><?= htmlspecialchars($property['property_name'])?></div>
            <div class="property-address"><?= htmlspecialchars($property['address'])?></div>
            <div class="property-gender">
                <?php
                    if($property['gender'] == "male") {
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
                <div class="rent">₹ <?= number_format($property['rent']); ?>/-</div>
                <div class="rent-unit">per month</div>
            </div>
            <div class="button-container col-6">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') { ?>
                    <button class="btn btn-secondary btn-block" disabled>Owner Mode</button>
                <?php } elseif ($is_booked) { ?>
                    <button class="btn btn-success btn-block" disabled>Booked</button>
                <?php } else { ?>
                    <a href="#" id="book-now-btn" class="btn btn-primary btn-block">Book Now</a>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="property-amenities">
        <div class="page-container">
            <h1>Amenities</h1>
            <div class="row justify-content-between">
                <div class="col-md-auto">
                    <h5>Building</h5>
                    <?php 
                        foreach ($amenities as $amenity) {
                            if($amenity['type'] == "Building") {
                    ?>
                        <div class="amenity-container">
                            <img src="img/amenities/<?= $amenity['icon'] ?>.svg" alt="<?= htmlspecialchars($amenity['name']) ?>">
                            <span><?= htmlspecialchars($amenity['name']) ?></span>
                        </div>
                    <?php 
                            }
                        }
                    ?>
                </div>
                
                <div class="col-md-auto">
                    <h5>Common Area</h5>
                    <?php
                        foreach($amenities as $amenity) {
                            if( $amenity['type'] == "Common Area") {
                    ?>
                    <div class="amenity-container">
                        <img src="img/amenities/<?= $amenity['icon'] ?>.svg" alt="<?= htmlspecialchars($amenity['name']) ?>">
                        <span><?= htmlspecialchars($amenity['name']) ?></span>
                    </div>
                    <?php
                            }
                        }
                    ?>                    
                </div>

                <div class="col-md-auto">
                    <h5>Bedroom</h5>
                    <?php
                        foreach ($amenities as $amenity) {
                            if ($amenity['type'] == "Bedroom") {
                    ?>
                    <div class="amenity-container">
                        <img src="img/amenities/<?= $amenity['icon'] ?>.svg" alt="<?= htmlspecialchars($amenity['name']) ?>">
                        <span><?= htmlspecialchars($amenity['name']) ?></span>
                    </div>
                    <?php
                            }
                        }
                    ?>
                </div>

                <div class="col-md-auto">
                    <h5>Washroom</h5>
                    <?php
                        foreach ($amenities as $amenity) {
                            if ($amenity['type'] == "Washroom") {
                    ?>
                    <div class="amenity-container">
                        <img src="img/amenities/<?= $amenity['icon'] ?>.svg" alt="<?= htmlspecialchars($amenity['name']) ?>">
                        <span><?= htmlspecialchars($amenity['name']) ?></span>
                    </div>
                    <?php
                            }
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="property-about page-container">
        <h1>About the Property</h1>
        <p><?= htmlspecialchars($property['description']) ?></p>

        <?php if (!empty($room_types)) { ?>
        <!-- Room Types & Availability Section -->
        <div class="room-types-section mt-4">
            <h4 class="font-weight-bold mb-3"><i class="fas fa-door-open mr-2 text-primary"></i>Available Room Types</h4>
            <div class="room-types-grid">
                <?php foreach ($room_types as $room) {
                    $available = $room['available_beds'];
                    $total     = (int)$room['total_beds'];
                    $occupied  = (int)$room['occupied_beds'];
                    $pct       = $total > 0 ? round(($occupied / $total) * 100) : 0;
                    $statusClass = $available === 0 ? 'full' : ($available <= 2 ? 'limited' : 'available');
                    $statusLabel = $available === 0 ? 'Full' : ($available <= 2 ? 'Limited Spots' : 'Available');
                    $roomIcons   = ['single' => 'fa-bed', 'double' => 'fa-users', 'triple' => 'fa-user-friends', 'dormitory' => 'fa-building', 'private' => 'fa-home'];
                    $icon        = $roomIcons[$room['room_type']] ?? 'fa-bed';
                    $amenitiesList = $room['amenities'] ? explode(',', $room['amenities']) : [];
                ?>
                <div class="room-type-card <?= $statusClass ?>">
                    <div class="room-type-header">
                        <div class="room-type-icon"><i class="fas <?= $icon ?>"></i></div>
                        <div class="room-type-info">
                            <div class="room-type-label"><?= htmlspecialchars($room['label']) ?></div>
                            <div class="room-type-price">₹<?= number_format($room['price_per_month']) ?>/mo</div>
                        </div>
                        <span class="room-availability-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </div>
                    <div class="room-occupancy-bar">
                        <div class="room-occupancy-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                    <div class="room-occupancy-text">
                        <span><?= $occupied ?> occupied / <?= $total ?> total</span>
                        <span class="font-weight-bold <?= $available === 0 ? 'text-danger' : 'text-success' ?>"><?= $available ?> free</span>
                    </div>
                    <?php if (!empty($amenitiesList)) { ?>
                    <div class="room-amenities-chips">
                        <?php foreach ($amenitiesList as $amenity) { ?>
                        <span class="room-amenity-chip"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars(trim($amenity)) ?></span>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <!-- Map Location -->
        <h4 class="mt-5 font-weight-bold">Property Location</h4>
        <?php if ($property['latitude'] !== null && $property['longitude'] !== null) { ?>
            <div id="map" class="mt-3" style="height: 300px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); border: 1px solid #ddd;"
                 data-lat="<?= (float)$property['latitude'] ?>"
                 data-lng="<?= (float)$property['longitude'] ?>"></div>
        <?php } else { ?>
            <div class="mt-3 p-4 bg-light rounded text-center border text-muted shadow-sm" style="border-radius: 8px;">
                <i class="fas fa-map-marked-alt fa-2x mb-2 text-primary"></i>
                <p class="mb-0 font-weight-bold">Interactive map location not available for this property.</p>
            </div>
        <?php } ?>

        <!-- Property Management / Owner Info -->
        <div class="owner-info-card mt-5 p-4 rounded bg-white border" style="border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); max-width: 500px;">
            <h5 class="mb-3 font-weight-bold text-dark"><i class="fas fa-user-tie mr-2 text-primary"></i>Property Manager / Owner</h5>
            <?php if ($property['owner_name']) { ?>
            <div class="d-flex align-items-center">
                <div class="owner-avatar mr-3" style="width: 50px; height: 50px; border-radius: 50%; background: #e3f2fd; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #1e3c72; font-weight: bold;">
                    <?= strtoupper(substr($property['owner_name'], 0, 1)) ?>
                </div>
                <div>
                    <h6 class="mb-1 font-weight-bold text-dark">
                        <?= htmlspecialchars($property['owner_name']) ?>
                        <?php if ((int)$property['owner_verified'] === 2) { ?>
                            <i class="fas fa-check-circle text-success ml-1" title="Verified Owner" style="color: #28a745 !important;"></i>
                        <?php } ?>
                    </h6>
                    <p class="text-muted mb-0" style="font-size: 13px;"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($property['owner_phone']) ?></p>
                </div>
            </div>
            <?php if ($user_id && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') && $user_id !== (int)$property['owner_id']) { ?>
                <div class="mt-3 border-top pt-3 text-right">
                    <button class="btn btn-primary btn-sm font-weight-bold px-3 seeker-chat-btn" 
                            data-contact-id="<?= $property['owner_id'] ?>"
                            data-contact-name="<?= htmlspecialchars($property['owner_name']) ?>"
                            data-contact-gender=""
                            data-contact-profile-pic=""
                            data-property-id="<?= $property['property_id'] ?>"
                            data-property-name="<?= htmlspecialchars($property['property_name']) ?>"
                            style="border-radius: 20px;">
                        <i class="fas fa-comments mr-1"></i>Chat with Owner
                    </button>
                </div>
            <?php } ?>
            <?php } else { ?>
            <div class="text-center py-2">
                <i class="fas fa-building text-muted mb-2" style="font-size: 32px;"></i>
                <p class="text-muted mb-0" style="font-size: 13px;">This property is currently unlisted. Owner information is not available.</p>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="property-rating">
        <div class="page-container">
            <h1>Property Rating</h1>
            <div class="row align-items-center justify-content-between">
                <div class="col-md-6">
                    <div class="rating-criteria row">
                        <div class="col-6">
                            <i class="rating-criteria-icon fas fa-broom"></i>
                            <span class="rating-criteria-text">Cleanliness</span>
                        </div>
                        <div class="rating-criteria-star-container col-6" title="<?= $property['rating_clean'] ?>">
                            <?php 
                                $rating = $property['rating_clean'];
                                for($i = 0; $i < 5; $i++) {
                                    if($rating >= $i + 0.8) {
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
                    </div>

                    <div class="rating-criteria row">
                        <div class="col-6">
                            <i class="rating-criteria-icon fas fa-utensils"></i>
                            <span class="rating-criteria-text">Food Quality</span>
                        </div>
                        <div class="rating-criteria-star-container col-6" title="<?= $property['rating_food'] ?>">
                            <?php
                                $rating = $property['rating_food'];
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
                    </div>
                    
                    <div class="rating-criteria row">
                        <div class="col-6">
                            <i class="rating-criteria-icon fa fa-lock"></i>
                            <span class="rating-criteria-text">Safety</span>
                        </div>
                        <div class="rating-criteria-star-container col-6" title="<?= $property['rating_safety'] ?>">
                            <?php
                                $rating = $property['rating_safety'];
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
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="rating-circle">
                        <?php 
                            $total_rating = ($property['rating_clean'] + $property['rating_food'] +$property['rating_safety']) / 3;
                            $total_rating = round($total_rating,1);
                        ?>
                        <div class="total-rating"><?= $total_rating ?></div>
                        <div class="rating-circle-star-container">
                            <?php
                                $rating = $total_rating;
                                for ($i = 0; $i < 5; $i++) {
                                    if($rating >= $i + 0.8) {
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews and Ratings Section -->
    <div class="property-reviews page-container border-top pt-5">
        <h1>User Reviews</h1>
        <div id="reviews-list">
            <?php if (count($reviews) == 0) { ?>
                <p class="text-muted" id="no-reviews-msg">No reviews yet. Be the first to write one!</p>
            <?php } else { 
                foreach ($reviews as $review) {
            ?>
                <div class="review-block mb-4 p-3 bg-light rounded shadow-sm border d-flex">
                    <div class="review-avatar-container mr-3 text-center" style="flex-shrink: 0;">
                        <?php
                            $review_img = 'img/man.png';
                            if (!empty($review['profile_pic'])) {
                                $review_img = $review['profile_pic'];
                            } elseif ($review['gender'] === 'female') {
                                $review_img = 'img/Female_icon.png';
                            }
                        ?>
                        <img src="<?= htmlspecialchars($review_img) ?>" class="rounded-circle img-thumbnail" style="width: 45px; height: 45px; object-fit: cover;" alt="User Avatar" />
                    </div>
                    <div class="review-details-container flex-grow-1">
                        <div class="d-flex justify-content-between mb-2">
                            <strong class="text-dark">
                                <?= htmlspecialchars($review['user_name']) ?>
                                <?php if (isset($review['is_verified']) && (int)$review['is_verified'] === 2) { ?>
                                    <i class="fas fa-check-circle text-success ml-1" title="KYC Verified" style="color: #28a745 !important; font-size: 12px;"></i>
                                <?php } ?>
                            </strong>
                            <div style="color: #EA322E; font-size: 11px;">
                                <?php for ($r = 0; $r < 5; $r++) { ?>
                                    <i class="<?= $r < $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                <?php } ?>
                            </div>
                        </div>
                        <p class="mb-2 text-muted" style="font-size: 14px;"><?= htmlspecialchars($review['content']) ?></p>
                        <small class="text-muted" style="font-size: 11px;"><i class="far fa-clock mr-1"></i><?= date('d M Y, h:i A', strtotime($review['created_at'])) ?></small>
                    </div>
                </div>
            <?php 
                }
            } ?>
        </div>

        <?php if ($user_id) { ?>
            <div class="add-review-container mt-5 p-4 border rounded bg-white shadow-sm">
                <h4 class="mb-3 font-weight-bold">Submit a Review</h4>
                <form id="add-review-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                    <input type="hidden" name="property_id" value="<?= $property_id ?>" />
                    <div class="form-group">
                        <label for="review-rating" class="font-weight-bold">Rating:</label>
                        <select class="form-control" name="rating" id="review-rating" required>
                            <option value="5">5 Stars - Excellent</option>
                            <option value="4">4 Stars - Very Good</option>
                            <option value="3">3 Stars - Average</option>
                            <option value="2">2 Stars - Poor</option>
                            <option value="1">1 Star - Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="review-content" class="font-weight-bold">Your Review:</label>
                        <textarea class="form-control" name="content" id="review-content" rows="4" placeholder="Tell others about your experience..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Submit Review</button>
                </form>
            </div>
        <?php } else { ?>
            <p class="text-muted mt-4">Please <a href="#" data-toggle="modal" data-target="#login-modal">Login</a> to write a review.</p>
        <?php } ?>
    </div>

    <div class="property-testimonials page-container border-top pt-5">
        <h1>What people say</h1>
        <?php 
            foreach ($testimonials as $testimonial) {
                $avatar = 'img/man.png';
                $name_lower = strtolower($testimonial['user_name']);
                if (strpos($name_lower, 'mira') !== false ||
                    strpos($name_lower, 'zoya') !== false ||
                    strpos($name_lower, 'farah') !== false ||
                    strpos($name_lower, 'meghna') !== false ||
                    strpos($name_lower, 'alice') !== false) {
                    $avatar = 'img/Female_icon.png';
                }
        ?>
        <div class="testimonial-block">
            <div class="testimonial-image-container">
                <img class="testimonial-img" src="<?= htmlspecialchars($avatar) ?>" alt="Testimonial user">
            </div>
            <div class="testimonial-text">
                <i class="fa fa-quote-left" aria-hidden="true"></i>
                <p><?= htmlspecialchars($testimonial['content']); ?></p>
            </div>
            <div class="testimonial-name">- <?= htmlspecialchars($testimonial['user_name']); ?></div>
        </div>
        <?php
            }
        ?>
    </div>

    <?php
        include "includes/signup_modal.php";
        include "includes/login_modal.php";
        include "includes/footer.php";
    ?>

    <!-- Leaflet.js Interactive Maps JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script type="text/javascript" src="js/property_detail.js"></script>
</body>

</html>
