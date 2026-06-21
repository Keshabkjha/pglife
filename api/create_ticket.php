<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    // 1. Verify CSRF Token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    // 2. Enforce authentication and role check (must be a seeker)
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'seeker') {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Only seekers can report maintenance issues."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // 3. Validate Inputs
    if ($property_id <= 0 || empty($title) || empty($description)) {
        echo json_encode(array("success" => false, "message" => "All fields (title and description) are required."));
        return;
    }

    if (strlen($title) > 100) {
        echo json_encode(array("success" => false, "message" => "Title must not exceed 100 characters."));
        return;
    }

    if (strlen($description) > 1000) {
        echo json_encode(array("success" => false, "message" => "Description must not exceed 1000 characters."));
        return;
    }

    // 4. Verify Active Booking for the Property (Prevent Abuse)
    $sql_booking = "SELECT id FROM bookings WHERE user_id = ? AND property_id = ?";
    $stmt_booking = mysqli_prepare($conn, $sql_booking);
    if (!$stmt_booking) {
        echo json_encode(array("success" => false, "message" => "Database check failed. Please try again."));
        return;
    }
    mysqli_stmt_bind_param($stmt_booking, "ii", $user_id, $property_id);
    mysqli_stmt_execute($stmt_booking);
    mysqli_stmt_store_result($stmt_booking);
    $booking_count = mysqli_stmt_num_rows($stmt_booking);
    mysqli_stmt_close($stmt_booking);

    if ($booking_count === 0) {
        echo json_encode(array("success" => false, "message" => "Access Denied. You can only file maintenance tickets for properties you have booked."));
        return;
    }

    // 5. Insert Ticket into Database
    $sql_insert = "INSERT INTO maintenance_tickets (property_id, user_id, title, description, status) VALUES (?, ?, ?, ?, 'open')";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        echo json_encode(array("success" => false, "message" => "Failed to log complaint. Please try again later."));
        return;
    }
    mysqli_stmt_bind_param($stmt_insert, "iiss", $property_id, $user_id, $title, $description);
    $result_insert = mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);

    if ($result_insert) {
        echo json_encode(array("success" => true, "message" => "Complaint logged successfully! The property owner will review it shortly."));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to log complaint. Database error."));
    }

    mysqli_close($conn);
?>
