<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');
    require("../includes/mail_helper.php");
    require_once("../includes/rate_limiter.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    // Rate limit: max 5 OTP verifications per IP per 15 minutes
    $client_ip = get_client_ip();
    $rate = check_rate_limit($conn, 'verify_otp', $client_ip, 5, 900);
    if (!$rate['allowed']) {
        echo json_encode(array("success" => false, "message" => "Too many verification attempts. Please try again in " . format_retry_after($rate['retry_after']) . "."));
        return;
    }

    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    if (empty($otp)) {
        echo json_encode(array("success" => false, "message" => "Please enter the OTP."));
        return;
    }

    if (!isset($_SESSION['pending_signup'])) {
        echo json_encode(array("success" => false, "message" => "No pending registration found. Please signup again."));
        return;
    }

    $pending = $_SESSION['pending_signup'];

    // Rate limit OTP attempts (max 5)
    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }
    $_SESSION['otp_attempts']++;
    if ($_SESSION['otp_attempts'] > 5) {
        unset($_SESSION['pending_signup']);
        unset($_SESSION['otp_attempts']);
        echo json_encode(array("success" => false, "message" => "Too many failed attempts. Please register again."));
        return;
    }

    // Verify OTP and Expiration
    if ($pending['otp'] !== $otp) {
        $remaining = 5 - $_SESSION['otp_attempts'];
        echo json_encode(array("success" => false, "message" => "Invalid OTP. Please try again. Remaining attempts: " . $remaining));
        return;
    }

    if (time() > $pending['otp_expiry']) {
        unset($_SESSION['pending_signup']);
        unset($_SESSION['otp_attempts']);
        echo json_encode(array("success" => false, "message" => "OTP has expired. Please signup again."));
        return;
    }

    // Insert user into Database
    $sql_insert = "INSERT INTO users (email, password, full_name, phone, gender, institution_or_organization, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        echo json_encode(array("success" => false, "message" => "Something went wrong during user creation."));
        return;
    }

    mysqli_stmt_bind_param($stmt_insert, "sssssss", $pending['email'], $pending['password'], $pending['full_name'], $pending['phone'], $pending['gender'], $pending['institution_or_organization'], $pending['role']);
    $result_insert = mysqli_stmt_execute($stmt_insert);

    if (!$result_insert) {
        echo json_encode(array("success" => false, "message" => "Something went wrong during user creation."));
        return;
    }

    // Get the newly created user ID
    $user_id = mysqli_insert_id($conn);

    // Auto-login the user
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $pending['full_name'];
    $_SESSION['email'] = $pending['email'];
    $_SESSION['role'] = $pending['role'];
    $_SESSION['profile_pic'] = null;
    $_SESSION['gender'] = $pending['gender'];

    // Send Warm Welcome Email
    $subject = "Welcome to PGLife family! 🛏️";
    $body = "<h2>Welcome, " . htmlspecialchars($pending['full_name']) . "!</h2>" .
            "<p>Your account has been successfully verified and created.</p>" .
            "<p>We are absolutely thrilled to have you with us. Find the perfect PG, book with a single click, and enjoy a hassle-free living experience.</p>" .
            "<p>Best Regards,<br>The PGLife Team</p>";

    send_smtp_email($pending['email'], $subject, $body);

    // Clear session pending data
    unset($_SESSION['pending_signup']);
    unset($_SESSION['otp_attempts']);

    echo json_encode(array("success" => true, "message" => "Account successfully verified and created!"));
    mysqli_close($conn);
?>
