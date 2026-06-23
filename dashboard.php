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
                    $profile_img = 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($user['full_name']) . '&backgroundColor=6366f1,8b5cf6,ec4899&radius=50';
                    if (!empty($user['profile_pic'])) {
                        $profile_img = $user['profile_pic'];
                    }
                ?>
                <img src="<?= htmlspecialchars($profile_img) ?>" class="rounded-circle img-thumbnail shadow-sm" style="width: 100px; height: 100px; object-fit: cover;" alt="Profile Picture" />
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
                    <div class="col-3 edit-profile text-right">
                        <div data-toggle="modal" data-target="#edit-profile-modal" style="cursor: pointer;" class="mb-1">
                            <i class="fas fa-edit mr-1"></i>Edit Profile
                        </div>
                        <a href="#" data-toggle="modal" data-target="#delete-account-modal" style="font-size: 12px; color: #ef4444; text-decoration: none; cursor: pointer;">
                            <i class="fas fa-trash-alt mr-1"></i>Delete Account
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC Status Panel -->
        <hr class="my-4" />
        <div class="kyc-status-panel p-4 rounded border" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
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
                    <p class="mt-2 mb-0" style="font-size: 11px; color: #94A3B8; line-height: 1.5;">
                        <i class="fas fa-shield-alt mr-1"></i>By uploading a document, you consent to PG Life storing and reviewing your identity proof for verification purposes. Your document is stored securely and will not be shared with third parties. You may request deletion at any time. See our <a href="/privacy" target="_blank" style="color: var(--primary-color); text-decoration: none;">Privacy Policy</a> for details.
                    </p>
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
    
    <?php if ($is_owner) {
        include "includes/dashboard_owner.php";
    } else {
        include "includes/dashboard_seeker.php";
    }
    ?>

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

    <!-- Delete Account Modal -->
    <div class="modal fade" id="delete-account-modal" tabindex="-1" role="dialog" aria-labelledby="delete-account-heading" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 12px; border: 2px solid #fee2e2;">
                <div class="modal-header" style="background: #fef2f2; border-bottom: 1px solid #fecaca; border-radius: 10px 10px 0 0;">
                    <h5 class="modal-title font-weight-bold text-danger" id="delete-account-heading">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Delete Account
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger border-0 rounded-lg p-3 mb-3" style="background: #fef2f2; font-size: 13px; line-height: 1.6;">
                        <strong>This action is permanent and irreversible.</strong><br>
                        Deleting your account will permanently remove:
                        <ul class="mb-0 mt-1 pl-3">
                            <li>Your profile and all personal data</li>
                            <li>All bookings, interested properties, and reviews</li>
                            <li>All messages, maintenance tickets, and payment records</li>
                            <li>Your KYC documents and profile photo</li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner') { ?>
                            <li><strong>All your property listings and associated data</strong></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <form id="delete-account-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                        <div class="form-group">
                            <label for="delete-confirm-password" class="font-weight-bold text-dark" style="font-size: 13px;">Enter your password to confirm:</label>
                            <input type="password" class="form-control" id="delete-confirm-password" name="password" placeholder="Your account password" autocomplete="current-password" required minlength="8">
                        </div>
                        <div id="delete-account-error" class="alert alert-danger d-none py-2" style="font-size: 13px;"></div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger font-weight-bold px-4" id="confirm-delete-btn">
                                <i class="fas fa-trash-alt mr-1"></i>Permanently Delete My Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
        include "includes/footer.php";
    ?>

    <!-- QRCode.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="text/javascript" src="js/dashboard.js"></script>
</body>

</html>