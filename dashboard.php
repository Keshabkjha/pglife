<?php
    session_start();
    require("includes/database_connect.php");

    if(!isset($_SESSION['user_id'])){
        header("location: home.php");
        die();
    }

    $user_id = (int)$_SESSION['user_id'];

    $sql_1 = "SELECT * FROM users WHERE id = ?";
    $stmt_1 = mysqli_prepare($conn, $sql_1);
    if (!$stmt_1) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_1, "i", $user_id);
    mysqli_stmt_execute($stmt_1);
    $result_1 = mysqli_stmt_get_result($stmt_1);
    if(!$result_1){
        echo "Something went wrong!";
        return;
    }

    $user = mysqli_fetch_assoc($result_1);
    if(!$user){
        echo "Something went wrong!";
        return;
    }

    $sql_2 = "SELECT p.* FROM interested_users_properties iup 
                INNER JOIN properties p 
                ON iup.property_id = p.id
                WHERE iup.user_id = ?";
    $stmt_2 = mysqli_prepare($conn, $sql_2);
    if (!$stmt_2) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_2, "i", $user_id);
    mysqli_stmt_execute($stmt_2);
    $result_2 = mysqli_stmt_get_result($stmt_2);
    if(!$result_2){
        echo "Something went wrong!";
        return;
    }

    $interested_properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

    $sql_3 = "SELECT p.* FROM bookings b 
                INNER JOIN properties p 
                ON b.property_id = p.id
                WHERE b.user_id = ?";
    $stmt_3 = mysqli_prepare($conn, $sql_3);
    if (!$stmt_3) {
        echo "Something went wrong!";
        return;
    }
    mysqli_stmt_bind_param($stmt_3, "i", $user_id);
    mysqli_stmt_execute($stmt_3);
    $result_3 = mysqli_stmt_get_result($stmt_3);
    if(!$result_3){
        echo "Something went wrong!";
        return;
    }

    $booked_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);

    // If user is owner, query owner properties and analytics
    $is_owner = (isset($_SESSION['role']) && $_SESSION['role'] === 'owner');
    $owner_properties = [];
    $total_listings = 0;
    $total_bookings_count = 0;
    $total_views_count = 0;
    $avg_owner_rating = 0;
    $owner_bookings = [];
    $owner_reviews = [];
    $all_cities = [];
    $all_amenities = [];
    $bookings_by_prop = [];
    $reviews_by_prop = [];

    if ($is_owner) {
        // Fetch listed properties
        $sql_owner_props = "SELECT * FROM properties WHERE owner_id = ?";
        $stmt_owner_props = mysqli_prepare($conn, $sql_owner_props);
        if ($stmt_owner_props) {
            mysqli_stmt_bind_param($stmt_owner_props, "i", $user_id);
            mysqli_stmt_execute($stmt_owner_props);
            $res_owner_props = mysqli_stmt_get_result($stmt_owner_props);
            if ($res_owner_props) {
                $owner_properties = mysqli_fetch_all($res_owner_props, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt_owner_props);
        }

        $total_listings = count($owner_properties);

        // Fetch bookings for owner properties
        $sql_owner_bookings = "SELECT b.*, p.name AS property_name, u.full_name AS seeker_name, u.email AS seeker_email, u.phone AS seeker_phone 
                               FROM bookings b 
                               INNER JOIN properties p ON b.property_id = p.id
                               INNER JOIN users u ON b.user_id = u.id
                               WHERE p.owner_id = ?
                               ORDER BY b.booking_date DESC";
        $stmt_owner_bookings = mysqli_prepare($conn, $sql_owner_bookings);
        if ($stmt_owner_bookings) {
            mysqli_stmt_bind_param($stmt_owner_bookings, "i", $user_id);
            mysqli_stmt_execute($stmt_owner_bookings);
            $res_owner_bookings = mysqli_stmt_get_result($stmt_owner_bookings);
            if ($res_owner_bookings) {
                $owner_bookings = mysqli_fetch_all($res_owner_bookings, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt_owner_bookings);
        }
        $total_bookings_count = count($owner_bookings);

        // Fetch reviews for owner properties
        $sql_owner_reviews = "SELECT r.*, p.name AS property_name 
                              FROM reviews r 
                              INNER JOIN properties p ON r.property_id = p.id
                              WHERE p.owner_id = ?
                              ORDER BY r.created_at DESC";
        $stmt_owner_reviews = mysqli_prepare($conn, $sql_owner_reviews);
        if ($stmt_owner_reviews) {
            mysqli_stmt_bind_param($stmt_owner_reviews, "i", $user_id);
            mysqli_stmt_execute($stmt_owner_reviews);
            $res_owner_reviews = mysqli_stmt_get_result($stmt_owner_reviews);
            if ($res_owner_reviews) {
                $owner_reviews = mysqli_fetch_all($res_owner_reviews, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt_owner_reviews);
        }

        // Calculate total views and avg rating
        $sum_ratings = 0;
        $rating_count = 0;
        foreach ($owner_properties as $p) {
            $total_views_count += (int)$p['views'];
            $prop_avg = ($p['rating_clean'] + $p['rating_food'] + $p['rating_safety']) / 3;
            if ($prop_avg > 0) {
                $sum_ratings += $prop_avg;
                $rating_count++;
            }
        }
        $avg_owner_rating = ($rating_count > 0) ? round($sum_ratings / $rating_count, 1) : 0.0;

        // Fetch all cities for the listing modal
        $sql_cities = "SELECT * FROM cities ORDER BY name";
        $result_cities = mysqli_query($conn, $sql_cities);
        if ($result_cities) {
            $all_cities = mysqli_fetch_all($result_cities, MYSQLI_ASSOC);
        }

        // Fetch all amenities for the listing checkboxes
        $sql_all_amenities = "SELECT * FROM amenities ORDER BY name";
        $result_all_amenities = mysqli_query($conn, $sql_all_amenities);
        if ($result_all_amenities) {
            $all_amenities = mysqli_fetch_all($result_all_amenities, MYSQLI_ASSOC);
        }

        // Group bookings and reviews by property_id for easy frontend usage
        foreach ($owner_bookings as $ob) {
            $bookings_by_prop[$ob['property_id']][] = $ob;
        }
        foreach ($owner_reviews as $or) {
            $reviews_by_prop[$or['property_id']][] = $or;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PG Life</title>
 
    <?php 
        include "includes/head_links.php";
    ?>

    <link href="css/dashboard.css" rel="stylesheet" />
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
                Dashboard
            </li>
        </ol>
    </nav>

    <div class="user-profile page-container">
        <h1>My Profile</h1>
        <div class="row">
            <div class="profile-picture col-md-3">
                <i class="fas fa-user text-center"></i>
            </div>
            <div class="col-md-9">
                <div class="row align-items-end">
                    <div class="col-9">
                        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                        <div class="user-phone"><?= htmlspecialchars($user['phone']) ?></div>
                        <div class="user-college"><?= htmlspecialchars($user['institution_or_organization']) ?></div>
                    </div>                
                    <div class="col-3 edit-profile" data-toggle="modal" data-target="#edit-profile-modal" style="cursor: pointer;">
                        Edit Profile
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_owner) { ?>
        <!-- Owner Control Panel -->
        <div class="owner-dashboard page-container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h1 class="mb-0 font-weight-bold text-dark">Owner Dashboard</h1>
                <button class="btn btn-success font-weight-bold px-4 py-2 shadow-sm rounded-lg" data-toggle="modal" data-target="#add-property-modal" style="border-radius: 30px; font-size: 14px;">
                    <i class="fas fa-plus-circle mr-2"></i>List New PG
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="row mb-5">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="owner-stat-card properties-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border: none;">
                        <div class="stat-content">
                            <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Properties Listed</span>
                            <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;"><?= $total_listings ?></span>
                        </div>
                        <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="owner-stat-card bookings-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none;">
                        <div class="stat-content">
                            <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Total Bookings</span>
                            <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;"><?= $total_bookings_count ?></span>
                        </div>
                        <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="owner-stat-card views-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #7f00ff 0%, #e100ff 100%); border: none;">
                        <div class="stat-content">
                            <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Total Views</span>
                            <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;"><?= $total_views_count ?></span>
                        </div>
                        <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="owner-stat-card rating-card shadow-sm p-4 rounded-lg text-white bg-dark position-relative overflow-hidden" style="border-radius: 12px; background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%); border: none;">
                        <div class="stat-content">
                            <span class="stat-label d-block text-uppercase font-weight-bold" style="font-size: 11px; opacity: 0.8; letter-spacing: 1px;">Average Rating</span>
                            <span class="stat-value font-weight-bold" style="font-size: 32px; line-height: 1.2;">
                                <?= $avg_owner_rating > 0 ? $avg_owner_rating . ' <span style="font-size: 18px;"><i class="fas fa-star text-warning"></i></span>' : 'N/A' ?>
                            </span>
                        </div>
                        <div class="stat-icon-wrapper position-absolute" style="right: 20px; bottom: 20px; font-size: 36px; opacity: 0.15;">
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My PGs Section -->
            <h2 class="mb-4 font-weight-bold text-dark" style="font-size: 24px; border-bottom: 2px solid #eee; padding-bottom: 10px;">My Properties & Performance</h2>
            <?php if (count($owner_properties) === 0) { ?>
                <div class="text-center py-5 border rounded bg-white shadow-sm mt-3" style="border-radius: 8px;">
                    <i class="fas fa-hotel text-muted mb-3" style="font-size: 48px; opacity: 0.3;"></i>
                    <p class="text-muted font-weight-bold">You have not listed any properties yet.</p>
                    <button class="btn btn-primary px-4 py-2 mt-2" data-toggle="modal" data-target="#add-property-modal">List Your First PG</button>
                </div>
            <?php } else { ?>
                <div class="owner-properties-grid mt-3">
                    <?php 
                    foreach ($owner_properties as $property) { 
                        $property_images = glob("img/properties/" . $property['id'] . "/*");
                        $image_path = !empty($property_images) ? $property_images[0] : 'img/logo.png';
                        
                        $prop_bookings = $bookings_by_prop[$property['id']] ?? [];
                        $prop_bookings_count = count($prop_bookings);
                        
                        $prop_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                        $prop_rating = round($prop_rating, 1);
                    ?>
                        <div class="property-card row mb-4 mx-0 shadow-sm border rounded bg-white overflow-hidden" style="border-radius: 10px; border: 1px solid #e3e3e3;">
                            <div class="image-container col-md-4 p-0">
                                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($property['name']) ?>" class="img-fluid w-100 h-100" style="min-height: 200px; object-fit: cover; max-width: 100%;" />
                            </div>
                            <div class="content-container col-md-8 p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                                        <h3 class="property-name text-dark font-weight-bold mb-0" style="font-size: 20px;"><?= htmlspecialchars($property['name']) ?></h3>
                                        <div class="gender-badge px-3 py-1 rounded text-uppercase font-weight-bold" style="font-size: 11px; background-color: #f7f7f7;">
                                            <?php if ($property['gender'] === 'male') { ?>
                                                <span class="text-primary"><i class="fas fa-male mr-1"></i>Boys Only</span>
                                            <?php } else if ($property['gender'] === 'female') { ?>
                                                <span class="text-danger"><i class="fas fa-female mr-1"></i>Girls Only</span>
                                            <?php } else { ?>
                                                <span class="text-success"><i class="fas fa-users mr-1"></i>Unisex</span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <p class="property-address text-muted mb-3" style="font-size: 13px;"><i class="fas fa-map-marker-alt mr-2 text-danger"></i><?= htmlspecialchars($property['address']) ?></p>
                                    
                                    <!-- Property Performance Stats -->
                                    <div class="d-flex flex-wrap align-items-center mb-4">
                                        <div class="metric-item mr-3 py-2 px-3 bg-light rounded text-center" style="border-radius: 6px; min-width: 80px;">
                                            <span class="metric-label text-muted d-block" style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Views</span>
                                            <span class="metric-val font-weight-bold text-dark" style="font-size: 16px;"><i class="far fa-eye mr-1 text-info"></i><?= $property['views'] ?></span>
                                        </div>
                                        <div class="metric-item mr-3 py-2 px-3 bg-light rounded text-center" style="border-radius: 6px; min-width: 80px;">
                                            <span class="metric-label text-muted d-block" style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Bookings</span>
                                            <span class="metric-val font-weight-bold text-dark" style="font-size: 16px;"><i class="far fa-calendar-check mr-1 text-success"></i><?= $prop_bookings_count ?></span>
                                        </div>
                                        <div class="metric-item py-2 px-3 bg-light rounded text-center" style="border-radius: 6px; min-width: 80px;">
                                            <span class="metric-label text-muted d-block" style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Avg Rating</span>
                                            <span class="metric-val font-weight-bold text-dark" style="font-size: 16px;">
                                                <i class="fas fa-star mr-1 text-warning"></i><?= $prop_rating > 0 ? $prop_rating : 'N/A' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row no-gutters align-items-center border-top pt-3">
                                    <div class="rent-container col-sm-4 mb-2 mb-sm-0">
                                        <div class="rent text-primary font-weight-bold mb-0" style="font-size: 20px;">₹ <?= number_format($property['rent']) ?>/-</div>
                                        <div class="rent-unit text-muted" style="font-size: 11px;">per month</div>
                                    </div>
                                    <div class="button-container col-sm-8 d-flex justify-content-end flex-wrap gap-2">
                                        <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-outline-primary btn-sm px-3 mr-2 font-weight-bold d-flex align-items-center" style="border-radius: 4px; height: 36px;">
                                            <i class="far fa-eye mr-1"></i>View
                                        </a>
                                        <button class="btn btn-outline-info btn-sm px-3 mr-2 font-weight-bold view-bookings-btn d-flex align-items-center" 
                                                data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                                data-bookings='<?= json_encode($bookings_by_prop[$property['id']] ?? []) ?>'
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="fas fa-users mr-1"></i>Bookings (<?= $prop_bookings_count ?>)
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm px-3 font-weight-bold view-reviews-btn d-flex align-items-center" 
                                                data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                                data-reviews='<?= json_encode($reviews_by_prop[$property['id']] ?? []) ?>'
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="far fa-comments mr-1"></i>Reviews (<?= count($reviews_by_prop[$property['id']] ?? []) ?>)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <!-- Seeker View -->
        <?php if(count($booked_properties) > 0) { ?>
        <div class="booked-properties">
            <div class="page-container py-4">
                <h1 class="mb-4">My Booked Properties</h1>
                
                <?php
                    foreach ($booked_properties as $property) {
                        $property_images = glob("img/properties/" . $property['id'] . "/*");
                ?>
                <div class="property-card property-id-<?= $property['id'] ?> row mb-4">
                    <div class="image-container col-md-4">
                        <img src="<?= $property_images[0] ?>" alt="<?= htmlspecialchars($property['name']) ?>" />
                    </div>
                    <div class="content-container col-md-8">
                        <div class="row no-gutters justify-content-between">
                             <?php
                                 $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety'])/3;
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
                                <span class="badge badge-success px-3 py-2 text-uppercase">Booked</span>
                            </div>
                        </div>
                        <div class="detail-container">
                            <div class="property-name"><?= htmlspecialchars($property['name']) ?></div>
                            <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
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
                                <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                                <div class="rent-unit">per month</div>
                             </div>
                             <div class="button-container col-6 d-flex justify-content-end">
                                 <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary mr-2" style="width: auto; float: none;">View</a>
                                 <button class="btn btn-danger cancel-booking-btn" property_id="<?= $property['id'] ?>" style="width: auto; float: none;">Cancel</button>
                             </div>
                        </div>
                    </div>
                </div>
                <?php
                    }
                ?>
            </div>
        </div>
        <?php } ?>

        <?php if(count($interested_properties) > 0) { ?>
        <div class="interested-properties border-top">
            <div class="page-container py-4">
                <h1 class="mb-4">My Interested Properties</h1>
                <?php
                    foreach ($interested_properties as $property) {
                        $property_images = glob("img/properties/" . $property['id'] . "/*");
                ?>
                <div class="property-card property-id-<?= $property['id'] ?> row">
                    <div class="image-container col-md-4">
                        <img src="<?= $property_images[0] ?>" alt="<?= htmlspecialchars($property['name']) ?>" />
                    </div>
                    <div class="content-container col-md-8">
                        <div class="row no-gutters justify-content-between">
                             <?php
                                 $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety'])/3;
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
                                <i class="is-interested-image fas fa-heart" property_id="<?= $property['id'] ?>"></i>
                            </div>
                        </div>
                        <div class="detail-container">
                            <div class="property-name"><?= htmlspecialchars($property['name']) ?></div>
                            <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
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
                                <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                                <div class="rent-unit">per month</div>
                            </div>
                            <div class="button-container col-6 d-flex justify-content-end">
                                <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    }
                ?>
            </div>
        </div>
        <?php } ?>
    <?php } ?>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="edit-profile-modal" tabindex="-1" role="dialog" aria-labelledby="edit-profile-heading" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit-profile-heading">Edit Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="edit-profile-form" class="form" role="form" method="post" action="api/update_profile.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                        <div class="input-group form-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" name="full_name" placeholder="Full Name" value="<?= htmlspecialchars($user['full_name']) ?>" maxlength="30" required>
                        </div>

                        <div class="input-group form-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-phone-alt"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($user['phone']) ?>" maxlength="10" minlength="10" required>
                        </div>

                        <div class="input-group form-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-university"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" name="institution_or_organization" placeholder="Institution / Organization (College/Company)" value="<?= htmlspecialchars($user['institution_or_organization']) ?>" maxlength="150" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_owner) { ?>
        <!-- Add Property Modal -->
        <div class="modal fade" id="add-property-modal" tabindex="-1" role="dialog" aria-labelledby="add-property-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="add-property-heading">List a New PG Property</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="add-property-form" class="form" role="form" method="post" action="api/add_property.php" enctype="multipart/form-data">
                        <div class="modal-body p-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                            
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="prop-name" class="font-weight-bold text-dark" style="font-size: 13px;">PG Name</label>
                                    <input type="text" class="form-control" name="name" id="prop-name" placeholder="e.g. Saxena's Paying Guest" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="prop-city" class="font-weight-bold text-dark" style="font-size: 13px;">City</label>
                                    <select class="form-control" name="city_id" id="prop-city" required>
                                        <option value="">Select City</option>
                                        <?php foreach ($all_cities as $city) { ?>
                                            <option value="<?= $city['id'] ?>"><?= htmlspecialchars($city['name']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="prop-address" class="font-weight-bold text-dark" style="font-size: 13px;">Full Address (Include City, State and Pincode for Geocoding Map)</label>
                                <textarea class="form-control" name="address" id="prop-address" rows="2" placeholder="e.g. H.No. 3958 Kaseru Walan, Pahar Ganj, New Delhi, Delhi 110055" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="prop-desc" class="font-weight-bold text-dark" style="font-size: 13px;">Description</label>
                                <textarea class="form-control" name="description" id="prop-desc" rows="3" placeholder="Describe the PG rooms, facilities, rent terms, nearby locations..." required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="prop-gender" class="font-weight-bold text-dark" style="font-size: 13px;">Gender Preference</label>
                                    <select class="form-control" name="gender" id="prop-gender" required>
                                        <option value="unisex">Unisex (Co-living)</option>
                                        <option value="male">Boys Only</option>
                                        <option value="female">Girls Only</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="prop-rent" class="font-weight-bold text-dark" style="font-size: 13px;">Monthly Rent (₹)</label>
                                    <input type="number" class="form-control" name="rent" id="prop-rent" placeholder="e.g. 6000" min="1" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold text-dark d-block" style="font-size: 13px;">Amenities</label>
                                <div class="row mx-0">
                                    <?php foreach ($all_amenities as $amenity) { ?>
                                        <div class="col-md-4 col-sm-6 mb-2 form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $amenity['id'] ?>" id="amenity-<?= $amenity['id'] ?>">
                                            <label class="form-check-label text-dark" style="font-size: 12px; cursor: pointer;" for="amenity-<?= $amenity['id'] ?>">
                                                <?= htmlspecialchars($amenity['name']) ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="prop-images" class="font-weight-bold text-dark" style="font-size: 13px;">Property Images (Upload at least one image)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="property_images[]" id="prop-images" multiple accept="image/*" required>
                                    <label class="custom-file-label" id="file-label-text" for="prop-images">Choose images...</label>
                                </div>
                                <small class="text-muted mt-1 d-block" style="font-size: 11px;">Supported formats: PNG, JPG, JPEG, WEBP, GIF. You can select multiple images.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success px-4 font-weight-bold">Publish Listing</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Bookings Modal -->
        <div class="modal fade" id="view-bookings-modal" tabindex="-1" role="dialog" aria-labelledby="view-bookings-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 8px;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="view-bookings-heading">Property Bookings</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <h6 class="booking-property-title font-weight-bold text-primary mb-3" style="font-size: 16px;">Property: </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0 text-dark" style="font-size: 13px;">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Seeker Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Booking Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bookings-table-body">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                        <div class="no-bookings-message text-center py-4 d-none text-muted">
                            <i class="far fa-calendar-times mb-2" style="font-size: 36px; opacity: 0.5;"></i>
                            <p class="mb-0 font-weight-bold">No bookings recorded for this property yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Reviews Modal -->
        <div class="modal fade" id="view-reviews-modal" tabindex="-1" role="dialog" aria-labelledby="view-reviews-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 8px;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="view-reviews-heading">Property Reviews & Feedback</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <h6 class="review-property-title font-weight-bold text-primary mb-3" style="font-size: 16px;">Property: </h6>
                        <div class="reviews-list-container">
                            <!-- Populated via JS -->
                        </div>
                        <div class="no-reviews-message text-center py-4 d-none text-muted">
                            <i class="far fa-comments mb-2" style="font-size: 36px; opacity: 0.5;"></i>
                            <p class="mb-0 font-weight-bold">No reviews submitted for this property yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
        include "includes/footer.php";
    ?>

    <script type="text/javascript" src="js/dashboard.js"></script>
</body>

</html>