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
    
    <?php
        if(count($booked_properties) > 0)
            {
    ?>
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
    <?php
        }
    ?>

    <?php
        if(count($interested_properties) > 0)
            {
    ?>

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
    <?php
        }
    ?>
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

    <?php
        include "includes/footer.php";
    ?>

    <script type="text/javascript" src="js/dashboard.js"></script>
</body>

</html>