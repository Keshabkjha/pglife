<?php
    require("../includes/database_connect.php");
    require_once("notify.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
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

    // Get owner details and property name before deletion
    $sql_owner = "SELECT owner_id, name FROM properties WHERE id = ?";
    $stmt_owner = mysqli_prepare($conn, $sql_owner);
    $owner_id_notif = 0;
    $prop_name = '';
    if ($stmt_owner) {
        mysqli_stmt_bind_param($stmt_owner, "i", $property_id);
        mysqli_stmt_execute($stmt_owner);
        $res_owner = mysqli_stmt_get_result($stmt_owner);
        if ($row_owner = mysqli_fetch_assoc($res_owner)) {
            $owner_id_notif = (int)$row_owner['owner_id'];
            $prop_name = $row_owner['name'];
        }
        mysqli_stmt_close($stmt_owner);
    }

    mysqli_begin_transaction($conn);

    // Get room_type_id of the booking
    $room_type_id = null;
    $sql_sel = "SELECT room_type_id FROM bookings WHERE user_id = ? AND property_id = ? FOR UPDATE";
    $stmt_sel = mysqli_prepare($conn, $sql_sel);
    if ($stmt_sel) {
        mysqli_stmt_bind_param($stmt_sel, "ii", $user_id, $property_id);
        mysqli_stmt_execute($stmt_sel);
        $res_sel = mysqli_stmt_get_result($stmt_sel);
        if ($row_sel = mysqli_fetch_assoc($res_sel)) {
            $room_type_id = $row_sel['room_type_id'] ? (int)$row_sel['room_type_id'] : null;
        }
        mysqli_stmt_close($stmt_sel);
    }

    $sql = "DELETE FROM bookings WHERE user_id = ? AND property_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $property_id);
    $result = mysqli_stmt_execute($stmt);

    if (!$result || mysqli_affected_rows($conn) === 0) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Booking not found or already cancelled."));
        mysqli_stmt_close($stmt);
        return;
    }
    mysqli_stmt_close($stmt);

    // Decrement occupied beds if room_type_id is set
    if ($room_type_id) {
        $sql_dec = "UPDATE room_types SET occupied_beds = GREATEST(0, occupied_beds - 1) WHERE id = ?";
        $stmt_dec = mysqli_prepare($conn, $sql_dec);
        if ($stmt_dec) {
            mysqli_stmt_bind_param($stmt_dec, "i", $room_type_id);
            mysqli_stmt_execute($stmt_dec);
            mysqli_stmt_close($stmt_dec);
        }
    }

    mysqli_commit($conn);

    if ($owner_id_notif > 0) {
        $seeker_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'A seeker';
        create_notification($conn, $owner_id_notif, 'booking', $seeker_name . ' cancelled booking', $seeker_name . ' cancelled booking for ' . $prop_name, '/pg/' . $property_id);
    }

    echo json_encode(array("success" => true, "message" => "Booking successfully cancelled.", "property_id" => $property_id));
    mysqli_close($conn);
?>
