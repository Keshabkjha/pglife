<?php
    require("../includes/database_connect.php");
    require_once("notify.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "is_logged_in" => false, "message" => "Please login to book a property."));
        return;
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seeker') {
        echo json_encode(array("success" => false, "is_logged_in" => true, "message" => "Only seekers can book properties."));
        return;
    }

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if ($property_id <= 0) {
        echo json_encode(array("success" => false, "message" => "Invalid property."));
        return;
    }

    // Check if already booked
    $sql_check = "SELECT * FROM bookings WHERE user_id = ? AND property_id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if (!$stmt_check) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $property_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    if (!$result_check) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    if (mysqli_num_rows($result_check) > 0) {
        echo json_encode(array("success" => false, "message" => "You have already booked this property!"));
        return;
    }

    // Book the property
    $sql_book = "INSERT INTO bookings (user_id, property_id) VALUES (?, ?)";
    $stmt_book = mysqli_prepare($conn, $sql_book);
    if (!$stmt_book) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_book, "ii", $user_id, $property_id);
    $result_book = mysqli_stmt_execute($stmt_book);

    if (!$result_book) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    // Get property owner to notify
    $sql_owner = "SELECT owner_id, name FROM properties WHERE id = ?";
    $stmt_owner = mysqli_prepare($conn, $sql_owner);
    if ($stmt_owner) {
        mysqli_stmt_bind_param($stmt_owner, "i", $property_id);
        mysqli_stmt_execute($stmt_owner);
        $res_owner = mysqli_stmt_get_result($stmt_owner);
        if ($row_owner = mysqli_fetch_assoc($res_owner)) {
            $owner_id_notif = (int)$row_owner['owner_id'];
            $prop_name = $row_owner['name'];
            $seeker_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'A seeker';
            create_notification($conn, $owner_id_notif, 'booking', $seeker_name . ' booked your property', $seeker_name . ' booked ' . $prop_name, '/pg/' . $property_id);
        }
        mysqli_stmt_close($stmt_owner);
    }

    echo json_encode(array("success" => true, "message" => "Property successfully booked!"));
    mysqli_close($conn);
?>
