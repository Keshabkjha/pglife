<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');
    require_once("../includes/rate_limiter.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to delete your account."));
        return;
    }

    // Rate limit: max 2 deletion attempts per IP per 30 minutes
    $client_ip = get_client_ip();
    $rate = check_rate_limit($conn, 'delete_account', $client_ip, 2, 1800);
    if (!$rate['allowed']) {
        echo json_encode(array("success" => false, "message" => "Too many attempts. Please try again in " . format_retry_after($rate['retry_after']) . "."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];

    // Check password before proceeding
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (empty($password)) {
        echo json_encode(array("success" => false, "message" => "Password confirmation is required to delete your account."));
        return;
    }

    // Verify password
    $sql_user = "SELECT password FROM users WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $sql_user);
    if (!$stmt_user) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $res_user = mysqli_stmt_get_result($stmt_user);
    $user = mysqli_fetch_assoc($res_user);
    mysqli_stmt_close($stmt_user);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(array("success" => false, "message" => "Incorrect password. Account deletion aborted."));
        return;
    }

    // Begin transaction
    mysqli_begin_transaction($conn);

    // Clean up user files (KYC doc, profile pic)
    $sql_kyc = "SELECT kyc_document FROM users WHERE id = ?";
    $stmt_kyc = mysqli_prepare($conn, $sql_kyc);
    if ($stmt_kyc) {
        mysqli_stmt_bind_param($stmt_kyc, "i", $user_id);
        mysqli_stmt_execute($stmt_kyc);
        $res_kyc = mysqli_stmt_get_result($stmt_kyc);
        if ($row_kyc = mysqli_fetch_assoc($res_kyc)) {
            if (!empty($row_kyc['kyc_document']) && file_exists('../' . $row_kyc['kyc_document'])) {
                @unlink('../' . $row_kyc['kyc_document']);
            }
        }
        mysqli_stmt_close($stmt_kyc);
    }

    // Remove profile pic from disk
    $sql_pic = "SELECT profile_pic FROM users WHERE id = ?";
    $stmt_pic = mysqli_prepare($conn, $sql_pic);
    if ($stmt_pic) {
        mysqli_stmt_bind_param($stmt_pic, "i", $user_id);
        mysqli_stmt_execute($stmt_pic);
        $res_pic = mysqli_stmt_get_result($stmt_pic);
        if ($row_pic = mysqli_fetch_assoc($res_pic)) {
            if (!empty($row_pic['profile_pic']) && file_exists('../' . $row_pic['profile_pic'])) {
                @unlink('../' . $row_pic['profile_pic']);
            }
        }
        mysqli_stmt_close($stmt_pic);
    }

    // Remove payment receipt files
    $sql_receipts = "SELECT screenshot FROM payments p 
                     INNER JOIN bookings b ON p.booking_id = b.id 
                     WHERE b.user_id = ?";
    $stmt_receipts = mysqli_prepare($conn, $sql_receipts);
    if ($stmt_receipts) {
        mysqli_stmt_bind_param($stmt_receipts, "i", $user_id);
        mysqli_stmt_execute($stmt_receipts);
        $res_receipts = mysqli_stmt_get_result($stmt_receipts);
        while ($row_receipt = mysqli_fetch_assoc($res_receipts)) {
            if (!empty($row_receipt['screenshot']) && file_exists('../' . $row_receipt['screenshot'])) {
                @unlink('../' . $row_receipt['screenshot']);
            }
        }
        mysqli_stmt_close($stmt_receipts);
    }

    // Wipe payments, bookings, tickets, messages, interests, reviews
    $sql_del_payments = "DELETE p FROM payments p 
                         INNER JOIN bookings b ON p.booking_id = b.id 
                         WHERE b.user_id = ?";
    $stmt_del_payments = mysqli_prepare($conn, $sql_del_payments);
    if ($stmt_del_payments) {
        mysqli_stmt_bind_param($stmt_del_payments, "i", $user_id);
        mysqli_stmt_execute($stmt_del_payments);
        mysqli_stmt_close($stmt_del_payments);
    }

    $sql_del_bookings = "DELETE FROM bookings WHERE user_id = ?";
    $stmt_del_bookings = mysqli_prepare($conn, $sql_del_bookings);
    if ($stmt_del_bookings) {
        mysqli_stmt_bind_param($stmt_del_bookings, "i", $user_id);
        mysqli_stmt_execute($stmt_del_bookings);
        mysqli_stmt_close($stmt_del_bookings);
    }

    // Delete maintenance tickets
    $sql_del_tickets = "DELETE FROM maintenance_tickets WHERE user_id = ?";
    $stmt_del_tickets = mysqli_prepare($conn, $sql_del_tickets);
    if ($stmt_del_tickets) {
        mysqli_stmt_bind_param($stmt_del_tickets, "i", $user_id);
        mysqli_stmt_execute($stmt_del_tickets);
        mysqli_stmt_close($stmt_del_tickets);
    }

    // Delete messages (sent and received)
    $sql_del_msgs_sent = "DELETE FROM messages WHERE sender_id = ?";
    $stmt_del_msgs_sent = mysqli_prepare($conn, $sql_del_msgs_sent);
    if ($stmt_del_msgs_sent) {
        mysqli_stmt_bind_param($stmt_del_msgs_sent, "i", $user_id);
        mysqli_stmt_execute($stmt_del_msgs_sent);
        mysqli_stmt_close($stmt_del_msgs_sent);
    }

    $sql_del_msgs_recv = "DELETE FROM messages WHERE receiver_id = ?";
    $stmt_del_msgs_recv = mysqli_prepare($conn, $sql_del_msgs_recv);
    if ($stmt_del_msgs_recv) {
        mysqli_stmt_bind_param($stmt_del_msgs_recv, "i", $user_id);
        mysqli_stmt_execute($stmt_del_msgs_recv);
        mysqli_stmt_close($stmt_del_msgs_recv);
    }

    // Delete interested properties
    $sql_del_interest = "DELETE FROM interested_users_properties WHERE user_id = ?";
    $stmt_del_interest = mysqli_prepare($conn, $sql_del_interest);
    if ($stmt_del_interest) {
        mysqli_stmt_bind_param($stmt_del_interest, "i", $user_id);
        mysqli_stmt_execute($stmt_del_interest);
        mysqli_stmt_close($stmt_del_interest);
    }

    // Delete reviews
    $sql_del_reviews = "DELETE FROM reviews WHERE user_id = ?";
    $stmt_del_reviews = mysqli_prepare($conn, $sql_del_reviews);
    if ($stmt_del_reviews) {
        mysqli_stmt_bind_param($stmt_del_reviews, "i", $user_id);
        mysqli_stmt_execute($stmt_del_reviews);
        mysqli_stmt_close($stmt_del_reviews);
    }

    // If user is an owner, delete all their properties and associated data
    $sql_check_owner = "SELECT id FROM properties WHERE owner_id = ?";
    $stmt_check_owner = mysqli_prepare($conn, $sql_check_owner);
    $owner_property_ids = [];
    if ($stmt_check_owner) {
        mysqli_stmt_bind_param($stmt_check_owner, "i", $user_id);
        mysqli_stmt_execute($stmt_check_owner);
        $res_owner = mysqli_stmt_get_result($stmt_check_owner);
        while ($row_prop = mysqli_fetch_assoc($res_owner)) {
            $owner_property_ids[] = (int)$row_prop['id'];
        }
        mysqli_stmt_close($stmt_check_owner);
    }

    if (!empty($owner_property_ids)) {
        foreach ($owner_property_ids as $prop_id) {
            // Delete payment records for bookings on this property
            $sql_del_prop_payments = "DELETE p FROM payments p INNER JOIN bookings b ON p.booking_id = b.id WHERE b.property_id = ?";
            $stmt_del_prop_payments = mysqli_prepare($conn, $sql_del_prop_payments);
            if ($stmt_del_prop_payments) {
                mysqli_stmt_bind_param($stmt_del_prop_payments, "i", $prop_id);
                mysqli_stmt_execute($stmt_del_prop_payments);
                mysqli_stmt_close($stmt_del_prop_payments);
            }

            // Delete bookings, interested, reviews, testimonials, amenities, messages, tickets, room types
            $sub_tables = [
                "DELETE FROM bookings WHERE property_id = ?",
                "DELETE FROM interested_users_properties WHERE property_id = ?",
                "DELETE FROM reviews WHERE property_id = ?",
                "DELETE FROM testimonials WHERE property_id = ?",
                "DELETE FROM properties_amenities WHERE property_id = ?",
                "DELETE FROM messages WHERE property_id = ?",
                "DELETE FROM maintenance_tickets WHERE property_id = ?",
                "DELETE FROM room_types WHERE property_id = ?"
            ];
            foreach ($sub_tables as $sub_sql) {
                $stmt_sub = mysqli_prepare($conn, $sub_sql);
                if ($stmt_sub) {
                    mysqli_stmt_bind_param($stmt_sub, "i", $prop_id);
                    mysqli_stmt_execute($stmt_sub);
                    mysqli_stmt_close($stmt_sub);
                }
            }

            // Delete property images from disk
            $prop_dir = "../img/properties/" . $prop_id;
            if (is_dir($prop_dir)) {
                $files = glob($prop_dir . "/*");
                if ($files) {
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                }
                @rmdir($prop_dir);
            }

            // Delete the property itself
            $sql_del_prop = "DELETE FROM properties WHERE id = ?";
            $stmt_del_prop = mysqli_prepare($conn, $sql_del_prop);
            if ($stmt_del_prop) {
                mysqli_stmt_bind_param($stmt_del_prop, "i", $prop_id);
                mysqli_stmt_execute($stmt_del_prop);
                mysqli_stmt_close($stmt_del_prop);
            }
        }
    }

    // Delete the user record (cascades: profile_pic, kyc_document paths removed above)
    $sql_del_user = "DELETE FROM users WHERE id = ?";
    $stmt_del_user = mysqli_prepare($conn, $sql_del_user);
    if (!$stmt_del_user) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Failed to delete account. Please try again."));
        return;
    }
    mysqli_stmt_bind_param($stmt_del_user, "i", $user_id);
    $result = mysqli_stmt_execute($stmt_del_user);
    mysqli_stmt_close($stmt_del_user);

    if (!$result) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Failed to delete account. Please try again."));
        return;
    }

    mysqli_commit($conn);

    // Destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode(array("success" => true, "message" => "Your account and all associated data have been permanently deleted."));
    mysqli_close($conn);
?>
