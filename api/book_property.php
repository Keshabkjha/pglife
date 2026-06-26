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

    // Enforce room inventory: require at least one available room_type
    mysqli_begin_transaction($conn);
    
    $req_room_type_id = isset($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : 0;
    $room_type_id = null;
    
    if ($req_room_type_id > 0) {
        $sql_room = "SELECT id, total_beds, occupied_beds FROM room_types WHERE id = ? AND property_id = ? AND total_beds > occupied_beds FOR UPDATE";
        $stmt_room = mysqli_prepare($conn, $sql_room);
        if (!$stmt_room) {
            mysqli_rollback($conn);
            echo json_encode(array("success" => false, "message" => "Booking failed: inventory system unavailable."));
            return;
        }
        mysqli_stmt_bind_param($stmt_room, "ii", $req_room_type_id, $property_id);
    } else {
        $sql_room = "SELECT id, total_beds, occupied_beds FROM room_types WHERE property_id = ? AND total_beds > occupied_beds LIMIT 1 FOR UPDATE";
        $stmt_room = mysqli_prepare($conn, $sql_room);
        if (!$stmt_room) {
            mysqli_rollback($conn);
            echo json_encode(array("success" => false, "message" => "Booking failed: inventory system unavailable."));
            return;
        }
        mysqli_stmt_bind_param($stmt_room, "i", $property_id);
    }
    
    mysqli_stmt_execute($stmt_room);
    $result_room = mysqli_stmt_get_result($stmt_room);
    if (!$result_room || mysqli_num_rows($result_room) === 0) {
        mysqli_rollback($conn);
        mysqli_stmt_close($stmt_room);
        echo json_encode(array("success" => false, "message" => "Sorry, no rooms are available for booking at this property."));
        return;
    }
    $row_room = mysqli_fetch_assoc($result_room);
    $room_type_id = (int)$row_room['id'];
    mysqli_stmt_close($stmt_room);

    // Update occupied beds
    $sql_update_room = "UPDATE room_types SET occupied_beds = occupied_beds + 1 WHERE id = ?";
    $stmt_update_room = mysqli_prepare($conn, $sql_update_room);
    if (!$stmt_update_room) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_update_room, "i", $room_type_id);
    $result_update_room = mysqli_stmt_execute($stmt_update_room);
    mysqli_stmt_close($stmt_update_room);
    if (!$result_update_room) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    // Book the property
    $sql_book = "INSERT INTO bookings (user_id, property_id, room_type_id) VALUES (?, ?, ?)";
    $stmt_book = mysqli_prepare($conn, $sql_book);
    if (!$stmt_book) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_book, "iii", $user_id, $property_id, $room_type_id);
    $result_book = mysqli_stmt_execute($stmt_book);
    mysqli_stmt_close($stmt_book);

    if (!$result_book) {
        mysqli_rollback($conn);
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

    mysqli_commit($conn);

    echo json_encode(array("success" => true, "message" => "Property successfully booked!"));
    mysqli_close($conn);
?>
