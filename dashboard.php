<?php
    session_start();
    require("includes/database_connect.php");

    if(!isset($_SESSION['user_id'])){
        header("location: /home");
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

    $sql_2 = "SELECT p.*, u.id AS owner_id, u.full_name AS owner_name, u.gender AS owner_gender, u.profile_pic AS owner_profile_pic,
                     (SELECT offer_amount FROM messages 
                      WHERE property_id = p.id 
                        AND ((sender_id = iup.user_id AND receiver_id = p.owner_id) OR (sender_id = p.owner_id AND receiver_id = iup.user_id)) 
                        AND offer_status = 2 
                      ORDER BY created_at DESC LIMIT 1) AS bargained_rent
                FROM interested_users_properties iup 
                INNER JOIN properties p ON iup.property_id = p.id
                INNER JOIN users u ON p.owner_id = u.id
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

    $sql_3 = "SELECT b.id AS booking_id, b.booking_date, p.*, pay.id AS payment_id, pay.status AS payment_status, pay.utr_number AS payment_utr, pay.screenshot AS payment_screenshot,
                     u.id AS owner_id, u.full_name AS owner_name, u.upi_id AS owner_upi, u.gender AS owner_gender, u.profile_pic AS owner_profile_pic,
                     (SELECT offer_amount FROM messages 
                      WHERE property_id = p.id 
                        AND ((sender_id = b.user_id AND receiver_id = p.owner_id) OR (sender_id = p.owner_id AND receiver_id = b.user_id)) 
                        AND offer_status = 2 
                      ORDER BY created_at DESC LIMIT 1) AS bargained_rent
                FROM bookings b 
                INNER JOIN properties p ON b.property_id = p.id
                INNER JOIN users u ON p.owner_id = u.id
                LEFT JOIN payments pay ON b.id = pay.booking_id
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

    // Fetch maintenance tickets submitted by this seeker
    $sql_seeker_tickets = "SELECT t.*, p.name AS property_name 
                           FROM maintenance_tickets t 
                           INNER JOIN properties p ON t.property_id = p.id
                           WHERE t.user_id = ? 
                           ORDER BY t.created_at DESC";
    $stmt_seeker_tickets = mysqli_prepare($conn, $sql_seeker_tickets);
    $seeker_tickets = [];
    if ($stmt_seeker_tickets) {
        mysqli_stmt_bind_param($stmt_seeker_tickets, "i", $user_id);
        mysqli_stmt_execute($stmt_seeker_tickets);
        $res_seeker_tickets = mysqli_stmt_get_result($stmt_seeker_tickets);
        if ($res_seeker_tickets) {
            $seeker_tickets = mysqli_fetch_all($res_seeker_tickets, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt_seeker_tickets);
    }
    
    // Group seeker tickets by property_id for easy listing
    $seeker_tickets_by_prop = [];
    foreach ($seeker_tickets as $st) {
        $seeker_tickets_by_prop[$st['property_id']][] = $st;
    }

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
        $sql_owner_reviews = "SELECT r.*, p.name AS property_name, u.is_verified 
                              FROM reviews r 
                              INNER JOIN properties p ON r.property_id = p.id
                              INNER JOIN users u ON r.user_id = u.id
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

        // Fetch maintenance tickets for owner properties
        $sql_owner_tickets = "SELECT t.*, p.name AS property_name, u.full_name AS seeker_name, u.phone AS seeker_phone 
                              FROM maintenance_tickets t 
                              INNER JOIN properties p ON t.property_id = p.id
                              INNER JOIN users u ON t.user_id = u.id
                              WHERE p.owner_id = ?
                              ORDER BY t.created_at DESC";
        $stmt_owner_tickets = mysqli_prepare($conn, $sql_owner_tickets);
        $owner_tickets = [];
        if ($stmt_owner_tickets) {
            mysqli_stmt_bind_param($stmt_owner_tickets, "i", $user_id);
            mysqli_stmt_execute($stmt_owner_tickets);
            $res_owner_tickets = mysqli_stmt_get_result($stmt_owner_tickets);
            if ($res_owner_tickets) {
                $owner_tickets = mysqli_fetch_all($res_owner_tickets, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt_owner_tickets);
        }
        
        // Group tickets by property_id
        $tickets_by_prop = [];
        foreach ($owner_tickets as $ot) {
            $tickets_by_prop[$ot['property_id']][] = $ot;
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

        // Fetch all payments for owner properties
        $owner_payments = [];
        $sql_owner_payments = "SELECT pay.*, b.booking_date, p.name AS property_name, u.full_name AS seeker_name, u.email AS seeker_email, u.phone AS seeker_phone 
                               FROM payments pay 
                               INNER JOIN bookings b ON pay.booking_id = b.id
                               INNER JOIN properties p ON b.property_id = p.id
                               INNER JOIN users u ON b.user_id = u.id
                               WHERE p.owner_id = ?
                               ORDER BY pay.payment_date DESC";
        $stmt_owner_payments = mysqli_prepare($conn, $sql_owner_payments);
        if ($stmt_owner_payments) {
            mysqli_stmt_bind_param($stmt_owner_payments, "i", $user_id);
            mysqli_stmt_execute($stmt_owner_payments);
            $res_owner_payments = mysqli_stmt_get_result($stmt_owner_payments);
            if ($res_owner_payments) {
                $owner_payments = mysqli_fetch_all($res_owner_payments, MYSQLI_ASSOC);
            }
            mysqli_stmt_close($stmt_owner_payments);
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
    <link href="css/image_manager.css" rel="stylesheet" />
    <link href="css/chat.css" rel="stylesheet" />
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
                Dashboard
            </li>
        </ol>
    </nav>

    <div class="user-profile page-container">
        <h1>My Profile</h1>
        <div class="row">
            <div class="profile-picture col-md-3">
                <?php
                    $profile_img = 'img/man.png';
                    if (!empty($user['profile_pic'])) {
                        $profile_img = $user['profile_pic'];
                    } elseif ($user['gender'] === 'female') {
                        $profile_img = 'img/Female_icon.png';
                    }
                ?>
                <img src="<?= $profile_img ?>" class="rounded-circle img-thumbnail shadow-sm" style="width: 100px; height: 100px; object-fit: cover;" alt="Profile Picture" />
            </div>
            <div class="col-md-9">
                <div class="row align-items-end">
                    <div class="col-9">
                        <div class="user-name">
                            <?= htmlspecialchars($user['full_name']) ?>
                            <?php if ((int)$user['is_verified'] === 2) { ?>
                                <i class="fas fa-check-circle text-success ml-1" title="KYC Verified" style="color: #28a745 !important;"></i>
                            <?php } ?>
                        </div>
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

        <!-- KYC Status Panel -->
        <hr class="my-4" />
        <div class="kyc-status-panel p-4 rounded bg-white border" style="border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1 font-weight-bold text-dark"><i class="fas fa-id-card mr-2 text-primary"></i>Aadhaar / KYC Verification</h5>
                    <p class="text-muted mb-0" style="font-size: 13px;">Verify your identity to build trust with owners/seekers and unlock the Verified badge.</p>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <?php if ((int)$user['is_verified'] === 0) { ?>
                        <span class="badge badge-secondary px-3 py-2 font-weight-bold" style="font-size: 13px; border-radius: 20px;"><i class="fas fa-exclamation-circle mr-1"></i>Not Verified</span>
                    <?php } else if ((int)$user['is_verified'] === 1) { ?>
                        <span class="badge badge-warning px-3 py-2 font-weight-bold" style="font-size: 13px; border-radius: 20px; color: #fff; background-color: #f0ad4e;"><i class="fas fa-clock mr-1"></i>Pending Review</span>
                    <?php } else if ((int)$user['is_verified'] === 2) { ?>
                        <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 13px; border-radius: 20px; background-color: #28a745; color: #fff;"><i class="fas fa-check-circle mr-1"></i>KYC Verified</span>
                    <?php } ?>
                </div>
            </div>

            <div class="kyc-action-area mt-4">
                <?php if ((int)$user['is_verified'] === 0) { ?>
                    <!-- Form to upload ID -->
                    <form id="kyc-upload-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="kyc_doc" name="kyc_doc" accept=".pdf, .jpg, .jpeg, .png" required>
                                    <label class="custom-file-label" for="kyc_doc" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Choose Aadhaar, PAN or Passport (PDF, JPG, PNG - max 3MB)</label>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                                    <i class="fas fa-upload mr-1"></i>Submit for Verification
                                </button>
                            </div>
                        </div>
                    </form>
                <?php } else if ((int)$user['is_verified'] === 1) { ?>
                    <!-- Pending state and Admin Simulation -->
                    <div class="alert alert-info border-0 rounded-lg p-3 d-flex align-items-center justify-content-between flex-wrap" style="background-color: #e3f2fd; border-radius: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle text-primary mr-3" style="font-size: 24px;"></i>
                            <div>
                                <h6 class="alert-heading mb-1 font-weight-bold text-dark" style="font-size: 14px;">Your document is under review</h6>
                                <p class="mb-0 text-muted" style="font-size: 12px;">We are currently verifying the uploaded document. You can simulate the admin approval instantly below.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm font-weight-bold px-4 py-2 mt-2 mt-lg-0 shadow-sm" id="btn-simulate-kyc-approval" style="border-radius: 20px; font-size: 13px;">
                            <i class="fas fa-magic mr-1"></i>Simulate Admin Verification
                        </button>
                    </div>
                <?php } else if ((int)$user['is_verified'] === 2) { ?>
                    <!-- Verified State Details -->
                    <div class="d-flex align-items-center p-3 rounded-lg border-success" style="background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px;">
                        <i class="fas fa-shield-alt text-success mr-3" style="font-size: 32px; color: #28a745;"></i>
                        <div>
                            <h6 class="mb-1 font-weight-bold text-dark">Identity Successfully Verified</h6>
                            <p class="mb-0 text-muted" style="font-size: 12px;">Your profile is officially verified. A verified checkmark badge will be displayed next to your name throughout the app.</p>
                        </div>
                    </div>
                <?php } ?>
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

                        // Fetch amenities IDs for editing
                        $sql_pa = "SELECT amenity_id FROM properties_amenities WHERE property_id = ?";
                        $stmt_pa = mysqli_prepare($conn, $sql_pa);
                        $pa_ids = [];
                        if ($stmt_pa) {
                            mysqli_stmt_bind_param($stmt_pa, "i", $property['id']);
                            mysqli_stmt_execute($stmt_pa);
                            $res_pa = mysqli_stmt_get_result($stmt_pa);
                            if ($res_pa) {
                                while($row_pa = mysqli_fetch_assoc($res_pa)) {
                                    $pa_ids[] = (int)$row_pa['amenity_id'];
                                }
                            }
                            mysqli_stmt_close($stmt_pa);
                        }

                        // Fetch existing room types for this property
                        $prop_room_types = [];
                        $sql_rt = "SELECT id, room_type, label, price_per_month, total_beds, occupied_beds, amenities, is_active FROM room_types WHERE property_id = ? ORDER BY price_per_month ASC";
                        $stmt_rt = mysqli_prepare($conn, $sql_rt);
                        if ($stmt_rt) {
                            mysqli_stmt_bind_param($stmt_rt, "i", $property['id']);
                            mysqli_stmt_execute($stmt_rt);
                            $res_rt = mysqli_stmt_get_result($stmt_rt);
                            if ($res_rt) {
                                while ($row_rt = mysqli_fetch_assoc($res_rt)) {
                                    $row_rt['available_beds'] = max(0, (int)$row_rt['total_beds'] - (int)$row_rt['occupied_beds']);
                                    $prop_room_types[] = $row_rt;
                                }
                            }
                            mysqli_stmt_close($stmt_rt);
                        }
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
                                        <a href="/pg/<?= $property['id'] ?>" class="btn btn-outline-primary btn-sm px-3 mr-2 font-weight-bold d-flex align-items-center" style="border-radius: 4px; height: 36px;">
                                            <i class="far fa-eye mr-1"></i>View
                                        </a>
                                        <button class="btn btn-outline-info btn-sm px-3 mr-2 font-weight-bold view-bookings-btn d-flex align-items-center" 
                                                data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                                data-bookings='<?= json_encode($bookings_by_prop[$property['id']] ?? []) ?>'
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="fas fa-users mr-1"></i>Bookings (<?= $prop_bookings_count ?>)
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm px-3 mr-2 font-weight-bold view-reviews-btn d-flex align-items-center" 
                                                data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                                data-reviews='<?= json_encode($reviews_by_prop[$property['id']] ?? []) ?>'
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="far fa-comments mr-1"></i>Reviews (<?= count($reviews_by_prop[$property['id']] ?? []) ?>)
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm px-3 mr-2 font-weight-bold view-tickets-btn d-flex align-items-center" 
                                                data-property-name="<?= htmlspecialchars($property['name']) ?>" 
                                                data-tickets='<?= json_encode($tickets_by_prop[$property['id']] ?? []) ?>'
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="fas fa-tools mr-1"></i>Tickets (<?= count($tickets_by_prop[$property['id']] ?? []) ?>)
                                        </button>
                                        <?php $basenames = array_map('basename', $property_images); ?>
                                        <button class="btn btn-outline-success btn-sm px-3 mr-2 font-weight-bold edit-property-btn d-flex align-items-center" 
                                                data-property='<?= json_encode($property) ?>'
                                                data-amenities-ids='<?= json_encode($pa_ids) ?>'
                                                data-images='<?= json_encode($basenames) ?>'
                                                data-primary-image="<?= htmlspecialchars($property['primary_image'] ?? '') ?>"
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="far fa-edit mr-1"></i>Edit
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm px-3 font-weight-bold delete-property-btn d-flex align-items-center" 
                                                property_id="<?= $property['id'] ?>"
                                                property_name="<?= htmlspecialchars($property['name']) ?>"
                                                style="border-radius: 4px; height: 36px;">
                                            <i class="far fa-trash-alt mr-1"></i>Delete
                                        </button>
                                    </div>
                                </div>

                                <!-- Room Types Availability Mini-Panel -->
                                <?php if (!empty($prop_room_types)) { ?>
                                <div class="border-top pt-3 mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted font-weight-bold" style="font-size: 12px;"><i class="fas fa-door-open mr-1 text-primary"></i>ROOM AVAILABILITY</small>
                                        <button class="btn btn-link btn-sm p-0 text-primary manage-rooms-btn font-weight-bold" 
                                                data-property-id="<?= $property['id'] ?>"
                                                data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                                data-rooms='<?= json_encode($prop_room_types) ?>'
                                                style="font-size: 12px;">
                                            <i class="far fa-edit mr-1"></i>Manage Rooms
                                        </button>
                                    </div>
                                    <div class="d-flex flex-wrap">
                                    <?php foreach ($prop_room_types as $rt) {
                                        $avail = $rt['available_beds'];
                                        $sc = $avail === 0 ? 'danger' : ($avail <= 2 ? 'warning' : 'success');
                                        $sl = $avail === 0 ? 'Full' : ($avail . ' free');
                                    ?>
                                        <span class="badge badge-<?= $sc ?> mr-1 mb-1 px-2 py-1" style="font-size: 11px; border-radius: 6px;">
                                            <?= htmlspecialchars($rt['label']) ?> &mdash; <?= $sl ?>
                                        </span>
                                    <?php } ?>
                                    </div>
                                </div>
                                <?php } else { ?>
                                <div class="border-top pt-2 mt-2 text-right">
                                    <button class="btn btn-outline-secondary btn-sm manage-rooms-btn" 
                                            data-property-id="<?= $property['id'] ?>"
                                            data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                            data-rooms='[]'
                                            style="font-size: 12px; border-radius: 6px;">
                                        <i class="fas fa-plus mr-1"></i>Add Room Types
                                    </button>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <!-- Owner Active Chats & Bargaining Section -->
            <h2 class="mb-4 font-weight-bold text-dark mt-5" style="font-size: 24px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Active Chats & Rent Bargains</h2>
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm border rounded-lg" style="border-radius: 12px; border: 1px solid #e3e3e3;">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 text-dark" style="font-size: 13px;">
                                    <thead>
                                        <tr class="bg-light">
                                            <th>Property Context</th>
                                            <th>Seeker Name</th>
                                            <th>Last Message Preview</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="owner-chats-table-body">
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading conversations...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="no-owner-chats-message" class="text-center py-5 d-none text-muted">
                                <i class="far fa-comments mb-3" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mb-0 font-weight-bold">No active conversations found.</p>
                                <small>Seekers will appear here when they send you inquiries or rent offers.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Owner Rent Collection Ledger -->
            <h2 class="mb-4 font-weight-bold text-dark mt-5" style="font-size: 24px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Rent Collection Ledger</h2>
            <?php if (count($owner_payments) === 0) { ?>
                <div class="text-center py-5 border rounded bg-white shadow-sm mt-3" style="border-radius: 8px;">
                    <i class="fas fa-receipt text-muted mb-3" style="font-size: 48px; opacity: 0.3;"></i>
                    <p class="text-muted font-weight-bold">No rent payment submissions received yet.</p>
                </div>
            <?php } else { ?>
                <div class="table-responsive bg-white border rounded shadow-sm p-3 mt-3" style="border-radius: 12px;">
                    <table class="table table-hover table-striped mb-0 text-dark" style="font-size: 13px;">
                        <thead>
                            <tr class="thead-dark text-white">
                                <th>Property</th>
                                <th>Seeker Details</th>
                                <th>Rent Amount</th>
                                <th>UTR / Transaction ID</th>
                                <th>Payment Date</th>
                                <th>Receipt</th>
                                <th>Status & Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($owner_payments as $payment) { ?>
                                <tr id="owner-payment-row-<?= $payment['id'] ?>">
                                    <td class="font-weight-bold"><?= htmlspecialchars($payment['property_name']) ?></td>
                                    <td>
                                        <div class="font-weight-bold"><?= htmlspecialchars($payment['seeker_name']) ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($payment['seeker_email']) ?> | <?= htmlspecialchars($payment['seeker_phone']) ?></div>
                                    </td>
                                    <td class="font-weight-bold text-primary">₹ <?= number_format($payment['amount']) ?></td>
                                    <td><code class="px-2 py-1 bg-light text-danger rounded font-weight-bold"><?= htmlspecialchars($payment['utr_number']) ?></code></td>
                                    <td><?= date('d M Y, h:i A', strtotime($payment['payment_date'])) ?></td>
                                    <td>
                                        <?php if (!empty($payment['screenshot'])) { ?>
                                            <a href="<?= $payment['screenshot'] ?>" target="_blank" class="btn btn-outline-info btn-xs py-1 px-2 font-weight-bold" style="font-size: 11px;">
                                                <i class="fas fa-file-image mr-1"></i>View Image
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-muted font-italic">No Attachment</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ((int)$payment['status'] === 0) { ?>
                                                <span class="badge badge-warning px-2 py-1 text-uppercase mr-2 payment-status-badge text-white" style="font-size: 11px; background-color: #f0ad4e;"><i class="fas fa-clock mr-1"></i>Pending</span>
                                                <button class="btn btn-xs btn-success font-weight-bold mr-1 owner-verify-payment-btn" data-payment-id="<?= $payment['id'] ?>" data-action="approve" style="font-size: 11px; padding: 4px 8px;">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-xs btn-danger font-weight-bold owner-verify-payment-btn" data-payment-id="<?= $payment['id'] ?>" data-action="reject" style="font-size: 11px; padding: 4px 8px;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php } else if ((int)$payment['status'] === 1) { ?>
                                                <span class="badge badge-success px-2 py-1 text-uppercase payment-status-badge text-white" style="font-size: 11px; background-color: #28a745;"><i class="fas fa-check-circle mr-1"></i>Verified</span>
                                            <?php } else if ((int)$payment['status'] === 2) { ?>
                                                <span class="badge badge-danger px-2 py-1 text-uppercase payment-status-badge text-white" style="font-size: 11px; background-color: #dc3545;"><i class="fas fa-times-circle mr-1"></i>Rejected</span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
                        $rent_amount = $property['bargained_rent'] !== null ? (int)$property['bargained_rent'] : (int)$property['rent'];
                        $is_rent_bargained = $property['bargained_rent'] !== null;
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

                        <!-- Rent Payment Status card -->
                        <div class="payment-status-block my-3 p-3 rounded d-flex align-items-center justify-content-between" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <div>
                                <small class="text-muted d-block font-weight-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Rent Payment</small>
                                <?php if ($property['payment_status'] === null || (int)$property['payment_status'] === 2) { ?>
                                    <span class="font-weight-bold text-danger" style="font-size: 14px;">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Unpaid
                                        <?php if ((int)$property['payment_status'] === 2) { ?>
                                            (Last transaction rejected)
                                        <?php } ?>
                                    </span>
                                <?php } else if ((int)$property['payment_status'] === 0) { ?>
                                    <span class="font-weight-bold text-warning" style="font-size: 14px;">
                                        <i class="fas fa-clock mr-1"></i>Pending Verification (UTR: <?= htmlspecialchars($property['payment_utr']) ?>)
                                    </span>
                                <?php } else if ((int)$property['payment_status'] === 1) { ?>
                                    <span class="font-weight-bold text-success" style="font-size: 14px;">
                                        <i class="fas fa-check-circle mr-1"></i>Paid & Verified (UTR: <?= htmlspecialchars($property['payment_utr']) ?>)
                                    </span>
                                <?php } ?>
                            </div>
                            <div>
                                <?php if ($property['payment_status'] === null || (int)$property['payment_status'] === 2) { ?>
                                    <button class="btn btn-sm btn-primary seeker-pay-rent-btn font-weight-bold px-3" 
                                             data-booking-id="<?= $property['booking_id'] ?>"
                                             data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                             data-rent="<?= $rent_amount ?>"
                                             data-owner-name="<?= htmlspecialchars($property['owner_name']) ?>"
                                             data-owner-upi="<?= htmlspecialchars($property['owner_upi'] ?? 'pglife@upi') ?>"
                                             style="border-radius: 20px; font-size: 12px; height: 32px;">
                                        <i class="fas fa-wallet mr-1"></i>Pay Rent
                                    </button>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="row no-gutters align-items-center">
                            <div class="rent-container col-sm-4 mb-2 mb-sm-0">
                                <?php if ($is_rent_bargained) { ?>
                                    <div class="rent" style="font-size: 18px; color: #28a745;"><i class="fas fa-handshake mr-1"></i>₹ <?= number_format($rent_amount) ?>/-</div>
                                    <div class="rent-unit"><del class="text-muted">₹ <?= number_format($property['rent']) ?></del> Negotiated</div>
                                <?php } else { ?>
                                    <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                                    <div class="rent-unit">per month</div>
                                <?php } ?>
                             </div>
                             <div class="button-container col-sm-8 d-flex justify-content-end flex-wrap gap-2">
                                 <a href="/pg/<?= $property['id'] ?>" class="btn btn-primary mr-2 mb-2" style="width: auto; float: none; height: 36px; line-height: 24px;">View</a>
                                 <button class="btn btn-outline-primary seeker-chat-btn mr-2 mb-2 font-weight-bold d-flex align-items-center" 
                                         data-contact-id="<?= $property['owner_id'] ?>"
                                         data-contact-name="<?= htmlspecialchars($property['owner_name']) ?>"
                                         data-contact-gender="<?= htmlspecialchars($property['owner_gender']) ?>"
                                         data-contact-profile-pic="<?= htmlspecialchars($property['owner_profile_pic'] ?? '') ?>"
                                         data-property-id="<?= $property['id'] ?>"
                                         data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                         style="width: auto; float: none; font-size: 13px; height: 36px;">
                                     <i class="fas fa-comments mr-1"></i>Chat with Owner
                                 </button>
                                 <button class="btn btn-warning seeker-report-issue-btn mr-2 mb-2 font-weight-bold d-flex align-items-center" 
                                         data-property-id="<?= $property['id'] ?>" 
                                         data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                         style="width: auto; float: none; font-size: 13px; height: 36px;"><i class="fas fa-exclamation-triangle mr-1"></i>Report Issue</button>
                                 <button class="btn btn-info seeker-view-tickets-btn mr-2 mb-2 font-weight-bold d-flex align-items-center" 
                                         data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                         data-tickets='<?= json_encode($seeker_tickets_by_prop[$property['id']] ?? []) ?>'
                                         style="width: auto; float: none; font-size: 13px; height: 36px;"><i class="fas fa-ticket-alt mr-1"></i>My Tickets (<?= count($seeker_tickets_by_prop[$property['id']] ?? []) ?>)</button>
                                 <button class="btn btn-danger cancel-booking-btn mb-2 font-weight-bold d-flex align-items-center" property_id="<?= $property['id'] ?>" style="width: auto; float: none; height: 36px;">Cancel</button>
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
                        $rent_amount = $property['bargained_rent'] !== null ? (int)$property['bargained_rent'] : (int)$property['rent'];
                        $is_rent_bargained = $property['bargained_rent'] !== null;
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
                        <div class="row no-gutters align-items-center">
                            <div class="rent-container col-sm-6 mb-2 mb-sm-0">
                                <?php if ($is_rent_bargained) { ?>
                                    <div class="rent" style="font-size: 18px; color: #28a745;"><i class="fas fa-handshake mr-1"></i>₹ <?= number_format($rent_amount) ?>/-</div>
                                    <div class="rent-unit"><del class="text-muted">₹ <?= number_format($property['rent']) ?></del> Negotiated</div>
                                <?php } else { ?>
                                    <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                                    <div class="rent-unit">per month</div>
                                <?php } ?>
                            </div>
                            <div class="button-container col-sm-6 d-flex justify-content-end gap-2 flex-wrap">
                                <a href="/pg/<?= $property['id'] ?>" class="btn btn-primary mr-2" style="width: auto; float: none; height: 36px; line-height: 24px;">View</a>
                                <button class="btn btn-outline-primary seeker-chat-btn font-weight-bold d-flex align-items-center" 
                                        data-contact-id="<?= $property['owner_id'] ?>"
                                        data-contact-name="<?= htmlspecialchars($property['owner_name']) ?>"
                                        data-contact-gender="<?= htmlspecialchars($property['owner_gender']) ?>"
                                        data-contact-profile-pic="<?= htmlspecialchars($property['owner_profile_pic'] ?? '') ?>"
                                        data-property-id="<?= $property['id'] ?>"
                                        data-property-name="<?= htmlspecialchars($property['name']) ?>"
                                        style="width: auto; float: none; font-size: 13px; height: 36px;">
                                    <i class="fas fa-comments mr-1"></i>Chat with Owner
                                </button>
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
                    <form id="edit-profile-form" class="form" role="form" method="post" action="api/update_profile.php" enctype="multipart/form-data">
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

                        <?php if ($is_owner) { ?>
                            <div class="input-group form-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-wallet"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control" name="upi_id" placeholder="UPI ID for Rent Collection (e.g. owner@paytm)" value="<?= htmlspecialchars($user['upi_id'] ?? '') ?>" maxlength="100">
                            </div>
                        <?php } ?>

                        <div class="form-group">
                            <label for="profile_pic" class="font-weight-bold text-dark mb-1" style="font-size: 13px;">Profile Picture (Max 2MB)</label>
                            <div class="custom-file mb-3">
                                <input type="file" class="custom-file-input" name="profile_pic" id="profile_pic" accept="image/*">
                                <label class="custom-file-label" for="profile_pic" id="profile-pic-label-text">Choose image...</label>
                            </div>
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
                                <div class="custom-file mb-2">
                                    <input type="file" class="custom-file-input" name="property_images[]" id="prop-images" multiple accept="image/*" required>
                                    <label class="custom-file-label" id="file-label-text" for="prop-images">Choose images...</label>
                                </div>
                                <small class="text-muted mt-1 d-block" style="font-size: 11px;">Supported formats: PNG, JPG, JPEG, WEBP, GIF. You can select multiple images.</small>
                                
                                <div id="add-image-previews-container" class="image-preview-grid d-none"></div>
                                <input type="hidden" name="primary_image_index" id="add-primary-image-index" value="0" />
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

        <!-- Edit Property Modal -->
        <div class="modal fade" id="edit-property-modal" tabindex="-1" role="dialog" aria-labelledby="edit-property-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="edit-property-heading">Edit PG Property Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="edit-property-form" class="form" role="form" method="post" action="api/edit_property.php" enctype="multipart/form-data">
                        <div class="modal-body p-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                            <input type="hidden" name="property_id" id="edit-prop-id" />
                            
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="edit-prop-name" class="font-weight-bold text-dark" style="font-size: 13px;">PG Name</label>
                                    <input type="text" class="form-control" name="name" id="edit-prop-name" placeholder="e.g. Saxena's Paying Guest" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="edit-prop-city" class="font-weight-bold text-dark" style="font-size: 13px;">City</label>
                                    <select class="form-control" name="city_id" id="edit-prop-city" required>
                                        <option value="">Select City</option>
                                        <?php foreach ($all_cities as $city) { ?>
                                            <option value="<?= $city['id'] ?>"><?= htmlspecialchars($city['name']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit-prop-address" class="font-weight-bold text-dark" style="font-size: 13px;">Full Address (Include City, State and Pincode for Geocoding Map)</label>
                                <textarea class="form-control" name="address" id="edit-prop-address" rows="2" placeholder="e.g. H.No. 3958 Kaseru Walan, Pahar Ganj, New Delhi, Delhi 110055" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="edit-prop-desc" class="font-weight-bold text-dark" style="font-size: 13px;">Description</label>
                                <textarea class="form-control" name="description" id="edit-prop-desc" rows="3" placeholder="Describe the PG rooms, facilities, rent terms..." required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="edit-prop-gender" class="font-weight-bold text-dark" style="font-size: 13px;">Gender Preference</label>
                                    <select class="form-control" name="gender" id="edit-prop-gender" required>
                                        <option value="unisex">Unisex (Co-living)</option>
                                        <option value="male">Boys Only</option>
                                        <option value="female">Girls Only</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="edit-prop-rent" class="font-weight-bold text-dark" style="font-size: 13px;">Monthly Rent (₹)</label>
                                    <input type="number" class="form-control" name="rent" id="edit-prop-rent" placeholder="e.g. 6000" min="1" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold text-dark d-block" style="font-size: 13px;">Amenities</label>
                                <div class="row mx-0">
                                    <?php foreach ($all_amenities as $amenity) { ?>
                                        <div class="col-md-4 col-sm-6 mb-2 form-check">
                                            <input class="form-check-input edit-amenity-chk" type="checkbox" name="amenities[]" value="<?= $amenity['id'] ?>" id="edit-amenity-<?= $amenity['id'] ?>">
                                            <label class="form-check-label text-dark" style="font-size: 12px; cursor: pointer;" for="edit-amenity-<?= $amenity['id'] ?>">
                                                <?= htmlspecialchars($amenity['name']) ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="form-group border-top pt-3">
                                <label class="font-weight-bold text-dark d-block" style="font-size: 13px;">Manage Current Images</label>
                                <div id="edit-current-images-container" class="image-preview-grid">
                                    <!-- Populated via JS -->
                                </div>
                                <div id="deleted-images-inputs-container"></div>
                                <input type="hidden" name="primary_image" id="edit-primary-image-val" value="" />
                            </div>

                            <div class="form-group border-top pt-3">
                                <label for="edit-prop-images" class="font-weight-bold text-dark" style="font-size: 13px;">Upload Additional Images (Optional)</label>
                                <div class="custom-file mb-2">
                                    <input type="file" class="custom-file-input" name="property_images[]" id="edit-prop-images" multiple accept="image/*">
                                    <label class="custom-file-label" id="edit-file-label-text" for="edit-prop-images">Choose images...</label>
                                </div>
                                <small class="text-muted mt-1 d-block" style="font-size: 11px;">Supported formats: PNG, JPG, JPEG, WEBP, GIF. You can select multiple images.</small>
                                
                                <div id="edit-image-previews-container" class="image-preview-grid d-none"></div>
                                <input type="hidden" name="new_primary_image_index" id="edit-new-primary-image-index" value="" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success px-4 font-weight-bold">Save Changes</button>
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

        <!-- View Owner Property Tickets Modal -->
        <div class="modal fade" id="view-owner-tickets-modal" tabindex="-1" role="dialog" aria-labelledby="owner-tickets-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 8px;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="owner-tickets-heading">Property Maintenance Complaints</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <h6 class="owner-ticket-property-title font-weight-bold text-primary mb-3" style="font-size: 16px;">Property: </h6>
                        <div class="owner-tickets-list-container">
                            <!-- Populated via JS -->
                        </div>
                        <div class="no-owner-tickets-message text-center py-4 d-none text-muted">
                            <i class="fas fa-shield-alt mb-2 text-success" style="font-size: 36px; opacity: 0.8;"></i>
                            <p class="mb-0 font-weight-bold">No complaints reported for this property. Everything is in order!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <!-- Create Maintenance Ticket Modal (Seeker) -->
        <div class="modal fade" id="create-ticket-modal" tabindex="-1" role="dialog" aria-labelledby="create-ticket-heading" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="create-ticket-heading">Report Maintenance Issue</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="create-ticket-form" class="form" role="form" method="post" action="api/create_ticket.php">
                        <div class="modal-body p-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                            <input type="hidden" name="property_id" id="ticket-property-id" />
                            
                            <div class="form-group">
                                <label class="font-weight-bold text-muted d-block" style="font-size: 11px; text-transform: uppercase;">Property</label>
                                <div id="ticket-property-name-display" class="font-weight-bold text-dark mb-3" style="font-size: 16px;"></div>
                            </div>

                            <div class="form-group">
                                <label for="ticket-title" class="font-weight-bold text-dark" style="font-size: 13px;">Issue Title</label>
                                <input type="text" class="form-control" name="title" id="ticket-title" placeholder="e.g. Bathroom tap leaking" maxlength="100" required>
                            </div>

                            <div class="form-group">
                                <label for="ticket-desc" class="font-weight-bold text-dark" style="font-size: 13px;">Description</label>
                                <textarea class="form-control" name="description" id="ticket-desc" rows="4" placeholder="Please describe the issue in detail..." maxlength="1000" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning px-4 font-weight-bold text-dark">Submit Complaint</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Seeker Tickets Modal -->
        <div class="modal fade" id="seeker-view-tickets-modal" tabindex="-1" role="dialog" aria-labelledby="seeker-tickets-heading" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 8px;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="seeker-tickets-heading">My Maintenance Requests</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <h6 class="seeker-ticket-property-title font-weight-bold text-primary mb-3" style="font-size: 16px;">Property: </h6>
                        <div class="seeker-tickets-list-container">
                            <!-- Populated via JS -->
                        </div>
                        <div class="no-seeker-tickets-message text-center py-4 d-none text-muted">
                            <i class="fas fa-check-circle mb-2 text-success" style="font-size: 36px; opacity: 0.8;"></i>
                            <p class="mb-0 font-weight-bold">No maintenance tickets reported for this property.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seeker Pay Rent Modal -->
        <div class="modal fade" id="pay-rent-modal" tabindex="-1" role="dialog" aria-labelledby="pay-rent-heading" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-dark" id="pay-rent-heading"><i class="fas fa-wallet mr-2 text-primary"></i>Online UPI Rent Payment</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="pay-rent-form" class="form" role="form" method="post" action="api/submit_payment.php" enctype="multipart/form-data">
                        <div class="modal-body p-4 text-center">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                            <input type="hidden" name="booking_id" id="pay-booking-id" />
                            
                            <div class="payment-details bg-light p-3 rounded mb-4 text-left" style="border-radius: 8px;">
                                <div class="row mb-2">
                                    <div class="col-6 text-muted" style="font-size: 12px;">PG Name:</div>
                                    <div class="col-6 font-weight-bold text-dark text-right" id="pay-property-name" style="font-size: 13px;"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted" style="font-size: 12px;">Owner Name:</div>
                                    <div class="col-6 font-weight-bold text-dark text-right" id="pay-owner-name" style="font-size: 13px;"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 text-muted" style="font-size: 12px;">Rent Amount:</div>
                                    <div class="col-6 font-weight-bold text-primary text-right" style="font-size: 15px;">₹ <span id="pay-rent-amount"></span></div>
                                </div>
                                <div class="row text-left">
                                    <div class="col-6 text-muted" style="font-size: 12px;">Recipient UPI:</div>
                                    <div class="col-6 font-weight-bold text-info text-right" id="pay-owner-upi" style="font-size: 13px; word-break: break-all;"></div>
                                </div>
                            </div>

                            <div class="qr-code-section mb-4">
                                <div id="pay-qrcode-container" class="d-inline-block p-3 border rounded bg-white shadow-sm" style="min-width: 150px; min-height: 150px;">
                                    <!-- QR Code is rendered here via JS -->
                                </div>
                                <p class="text-muted mt-2 mb-0" style="font-size: 11px;">Scan this QR code with Google Pay, PhonePe, Paytm, or any UPI app to pay.</p>
                            </div>

                            <!-- Mobile UPI Deep Link -->
                            <div class="mobile-pay-deep-link mb-4 d-md-none">
                                <a href="#" id="pay-upi-deep-link" class="btn btn-outline-primary btn-block font-weight-bold" style="border-radius: 8px;">
                                    <i class="fas fa-mobile-alt mr-2"></i>Pay directly with UPI App
                                </a>
                            </div>

                            <div class="form-group text-left border-top pt-3">
                                <label for="pay-utr" class="font-weight-bold text-dark" style="font-size: 13px;">Enter Transaction ID / UTR (Mandatory)</label>
                                <input type="text" class="form-control" name="utr_number" id="pay-utr" placeholder="e.g. 302819283728" maxlength="50" required>
                                <small class="text-muted" style="font-size: 11px;">Check your UPI app history to find the transaction number.</small>
                            </div>

                            <div class="form-group text-left">
                                <label for="pay-screenshot" class="font-weight-bold text-dark" style="font-size: 13px;">Upload Receipt Screenshot (Optional)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="receipt_screenshot" id="pay-screenshot" accept="image/*,application/pdf">
                                    <label class="custom-file-label" id="pay-screenshot-label" for="pay-screenshot">Choose receipt file...</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">Confirm & Submit Proof</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- ── Room Types Management Modal (Owner) ──────────────────── -->
    <?php if ($_SESSION['role'] === 'owner') { ?>
    <div class="modal fade" id="manage-rooms-modal" tabindex="-1" role="dialog" aria-labelledby="manageRoomsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px 8px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="manageRoomsModalLabel">
                        <i class="fas fa-door-open mr-2"></i>Manage Room Types &mdash; <span id="rooms-modal-property-name"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size: 13px;"><i class="fas fa-info-circle mr-1 text-primary"></i>Define room types with real-time bed availability. Seekers see this on the property detail page.</p>
                    <input type="hidden" id="rooms-modal-property-id" value="">
                    <div id="rooms-modal-list"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="add-room-row-btn">
                        <i class="fas fa-plus mr-1"></i>Add Room Type
                    </button>
                </div>
                <div class="modal-footer">
                    <div id="rooms-save-feedback" class="mr-auto text-success font-weight-bold" style="display: none;"></div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary font-weight-bold" id="save-rooms-btn">
                        <i class="fas fa-save mr-1"></i>Save Room Types
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php
        include "includes/footer.php";
    ?>

    <!-- QRCode.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="text/javascript" src="js/dashboard.js"></script>
</body>

</html>