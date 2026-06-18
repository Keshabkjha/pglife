<?php
    require("../includes/database_connect.php");
    require("../includes/mail_helper.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        $response = array("success" => false, "message" => "Security verification failed (CSRF token mismatch).");
        echo json_encode($response);
        return;
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $institution_or_organization = isset($_POST['institution_or_organization']) ? trim($_POST['institution_or_organization']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'seeker';

    if (empty($email) || empty($password) || empty($full_name) || empty($phone) || empty($gender) || empty($role)) {
        $response = array("success" => false, "message" => "Please fill in all required fields.");
        echo json_encode($response);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = array("success" => false, "message" => "Please enter a valid email address.");
        echo json_encode($response);
        return;
    }

    if (strlen($password) < 6) {
        $response = array("success" => false, "message" => "Password must be at least 6 characters long.");
        echo json_encode($response);
        return;
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $response = array("success" => false, "message" => "Phone number must be exactly 10 digits.");
        echo json_encode($response);
        return;
    }

    if ($gender !== 'male' && $gender !== 'female') {
        $response = array("success" => false, "message" => "Please select a valid gender.");
        echo json_encode($response);
        return;
    }

    if ($role !== 'seeker' && $role !== 'owner') {
        $response = array("success" => false, "message" => "Please select a valid profile type.");
        echo json_encode($response);
        return;
    }

    // Check duplicate email using Prepared Statement
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $response = array("success" => false, "message" => "Something went wrong!");
        echo json_encode($response);
        return;
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        $response = array("success" => false, "message" => "Something went wrong!");
        echo json_encode($response);
        return;
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count != 0) {
        $response = array("success" => false, "message" => "This email id is already registered with us!");
        echo json_encode($response);
        return;
    }

    // Generate 6-digit OTP code
    $otp = (string)mt_rand(100000, 999999);
    $otp_expiry = time() + 300; // 5 minutes

    // Store pending signup data in session
    $_SESSION['pending_signup'] = [
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'full_name' => $full_name,
        'phone' => $phone,
        'gender' => $gender,
        'institution_or_organization' => $institution_or_organization,
        'role' => $role,
        'otp' => $otp,
        'otp_expiry' => $otp_expiry
    ];

    // Send verification OTP email
    $subject = "Verification OTP - PGLife";
    $body = "<h2>Welcome to PGLife!</h2>" .
            "<p>Thank you for signing up. Please enter the following One-Time Password (OTP) to complete registration:</p>" .
            "<p style='font-size:24px; font-weight:bold; letter-spacing:2px; color:#EA322E;'>" . $otp . "</p>" .
            "<p>This code is valid for 5 minutes.</p>";

    $email_sent = send_smtp_email($email, $subject, $body);
    if (!$email_sent) {
        $response = array("success" => false, "message" => "Failed to send verification email. Please try again.");
        echo json_encode($response);
        return;
    }

    $response = array("success" => true, "otp_required" => true, "message" => "An OTP has been sent to your email. Please verify.");
    echo json_encode($response);
    mysqli_close($conn);
?>