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

    // Rate limit: max 5 reset verification attempts per IP per 15 minutes
    $client_ip = get_client_ip();
    $rate = check_rate_limit($conn, 'reset_otp', $client_ip, 5, 900);
    if (!$rate['allowed']) {
        echo json_encode(array("success" => false, "message" => "Too many attempts. Please try again in " . format_retry_after($rate['retry_after']) . "."));
        return;
    }

    if (!isset($_SESSION['password_reset'])) {
        echo json_encode(array("success" => false, "message" => "No pending password reset request found."));
        return;
    }

    $reset = $_SESSION['password_reset'];
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($otp) || empty($password)) {
        echo json_encode(array("success" => false, "message" => "Please fill in all fields."));
        return;
    }

    if (strlen($password) < 8) {
        echo json_encode(array("success" => false, "message" => "Password must be at least 8 characters long."));
        return;
    }

    // Rate limit reset OTP attempts (max 5)
    if (!isset($_SESSION['reset_otp_attempts'])) {
        $_SESSION['reset_otp_attempts'] = 0;
    }
    $_SESSION['reset_otp_attempts']++;
    if ($_SESSION['reset_otp_attempts'] > 5) {
        unset($_SESSION['password_reset']);
        unset($_SESSION['reset_otp_attempts']);
        echo json_encode(array("success" => false, "message" => "Too many failed attempts. Please restart the request."));
        return;
    }

    // Verify OTP and Expiration
    if ($reset['otp'] !== $otp) {
        $remaining = 5 - $_SESSION['reset_otp_attempts'];
        echo json_encode(array("success" => false, "message" => "Invalid OTP code. Please check your email and try again. Remaining attempts: " . $remaining));
        return;
    }

    if (time() > $reset['otp_expiry']) {
        unset($_SESSION['password_reset']);
        unset($_SESSION['reset_otp_attempts']);
        echo json_encode(array("success" => false, "message" => "Verification OTP has expired. Please initiate the request again."));
        return;
    }

    // Update password in DB
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong during password update."));
        return;
    }
    mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $reset['email']);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Something went wrong. Could not update password."));
        return;
    }

    // Send confirmation email
    $subject = "Security Alert: Password Updated - PGLife";
    $body = "<h2>Password Changed Successfully</h2>" .
            "<p>Your PGLife password was updated successfully.</p>" .
            "<p>If you did not perform this change, please contact support immediately.</p>" .
            "<p>Best Regards,<br>The PGLife Team</p>";
    send_smtp_email($reset['email'], $subject, $body);

    // Clear reset session
    unset($_SESSION['password_reset']);
    unset($_SESSION['reset_otp_attempts']);

    echo json_encode(array("success" => true, "message" => "Your password has been reset successfully! You can now login."));
    mysqli_close($conn);
?>
