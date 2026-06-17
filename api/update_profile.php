<?php
    session_start();
    require("../includes/database_connect.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to update your profile."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $institution_or_organization = isset($_POST['institution_or_organization']) ? trim($_POST['institution_or_organization']) : '';

    if (empty($full_name) || empty($phone) || empty($institution_or_organization)) {
        echo json_encode(array("success" => false, "message" => "All fields are required."));
        return;
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(array("success" => false, "message" => "Phone number must be exactly 10 digits."));
        return;
    }

    $sql = "UPDATE users SET full_name = ?, phone = ?, institution_or_organization = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    mysqli_stmt_bind_param($stmt, "sssi", $full_name, $phone, $institution_or_organization, $user_id);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    // Update Session
    $_SESSION['full_name'] = $full_name;

    echo json_encode(array("success" => true, "message" => "Profile successfully updated!"));
    mysqli_close($conn);
?>
