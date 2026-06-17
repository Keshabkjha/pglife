<?php
    session_start();
    require("../includes/database_connect.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to submit a review."));
        return;
    }

    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $user_name = $_SESSION['full_name'];

    if ($property_id <= 0 || $rating < 1 || $rating > 5 || empty($content)) {
        echo json_encode(array("success" => false, "message" => "Invalid parameters. Please fill in all fields."));
        return;
    }

    $sql = "INSERT INTO reviews (property_id, user_name, rating, content) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    mysqli_stmt_bind_param($stmt, "isis", $property_id, $user_name, $rating, $content);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    echo json_encode(array("success" => true, "message" => "Review submitted successfully!"));
    mysqli_close($conn);
?>
