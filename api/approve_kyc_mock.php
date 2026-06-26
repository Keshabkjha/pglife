<?php
    require("../includes/database_connect.php");
    require_once("../includes/app_config.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!feature_mock_kyc_enabled()) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Not found."));
        exit;
    }

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to perform this action."));
        return;
    }

    // Only property owners can simulate KYC approval (admin simulation)
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        echo json_encode(array("success" => false, "message" => "Unauthorized. Only property owners can simulate admin verification."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];

    // Verify that the user currently has a pending verification status (1)
    $sql_check = "SELECT is_verified FROM users WHERE id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if (!$stmt_check) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_check, "i", $user_id);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    $row = mysqli_fetch_assoc($res_check);
    mysqli_stmt_close($stmt_check);

    if (!$row) {
        echo json_encode(array("success" => false, "message" => "User not found."));
        return;
    }

    if ((int)$row['is_verified'] !== 1) {
        echo json_encode(array("success" => false, "message" => "No pending verification document to approve. Please upload a document first."));
        return;
    }

    // Set is_verified to 2 (Verified)
    $sql_update = "UPDATE users SET is_verified = 2 WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if (!$stmt_update) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_update, "i", $user_id);
    $result = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if ($result) {
        echo json_encode(array("success" => true, "message" => "Identity successfully verified! Recruiter simulation complete."));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to verify identity."));
    }

    mysqli_close($conn);
?>
