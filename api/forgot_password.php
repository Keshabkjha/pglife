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

    // Rate limit: max 3 forgot-password OTP requests per IP per 15 minutes
    $client_ip = get_client_ip();
    $rate = check_rate_limit($conn, 'forgot_otp', $client_ip, 3, 900);
    if (!$rate['allowed']) {
        echo json_encode(array("success" => false, "message" => "Too many password reset requests. Please try again in " . format_retry_after($rate['retry_after']) . "."));
        return;
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email)) {
        echo json_encode(array("success" => false, "message" => "Please enter your registered email address."));
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array("success" => false, "message" => "Please enter a valid email address."));
        return;
    }

    // Check if user exists
    $sql = "SELECT id, full_name FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        echo json_encode(array("success" => true, "message" => "An OTP has been sent to your email. Please verify to reset your password."));
        return;
    }

    // Generate 6-digit verification code
    $otp = (string)random_int(100000, 999999);
    $otp_expiry = time() + 300; // 5 minutes

    // Store in session
    $_SESSION['password_reset'] = [
        'email' => $email,
        'otp' => $otp,
        'otp_expiry' => $otp_expiry,
        'verified' => false
    ];

    // Send OTP email
    $subject = "Password Reset Verification OTP - PGLife";
    $body = "<h2>Password Reset Request</h2>" .
            "<p>Hi " . htmlspecialchars($user['full_name']) . ",</p>" .
            "<p>We received a request to reset your password. Please enter the following One-Time Password (OTP) in the recovery modal to verify your identity:</p>" .
            "<p style='font-size:24px; font-weight:bold; letter-spacing:2px; color:#4F46E5;'>" . $otp . "</p>" .
            "<p>This code is valid for 5 minutes. If you did not make this request, you can safely ignore this email.</p>" .
            "<p>Best Regards,<br>The PGLife Team</p>";

    $email_sent = send_smtp_email($email, $subject, $body);
    if (!$email_sent) {
        echo json_encode(array("success" => false, "message" => "Failed to send recovery email. Please check configuration."));
        return;
    }

    echo json_encode(array("success" => true, "message" => "An OTP has been sent to your email. Please verify to reset your password."));
    mysqli_close($conn);
?>
