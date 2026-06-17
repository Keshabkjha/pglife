<?php
    session_start();
    require("../includes/database_connect.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to cancel a booking."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if ($property_id <= 0) {
        echo json_encode(array("success" => false, "message" => "Invalid property ID."));
        return;
    }

    $sql = "DELETE FROM bookings WHERE user_id = ? AND property_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $property_id);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    echo json_encode(array("success" => true, "message" => "Booking successfully cancelled.", "property_id" => $property_id));
    mysqli_close($conn);
?>
