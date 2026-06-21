<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to submit your payment proof."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $utr_number = isset($_POST['utr_number']) ? trim($_POST['utr_number']) : '';

    if ($booking_id <= 0 || empty($utr_number)) {
        echo json_encode(array("success" => false, "message" => "Booking reference and UTR/Transaction ID are required."));
        return;
    }

    // Validate UTR format (typically 12-digit numeric, but we can allow standard alphanumeric check up to 50 chars)
    if (!preg_match('/^[a-zA-Z0-9]{6,50}$/', $utr_number)) {
        echo json_encode(array("success" => false, "message" => "Invalid Transaction ID / UTR format."));
        return;
    }

    // Check if the booking exists and belongs to this user, and fetch the rent amount (checking for accepted bargaining offers)
    $sql_booking = "SELECT b.id, p.rent,
                     (SELECT offer_amount FROM messages 
                      WHERE property_id = p.id 
                        AND ((sender_id = b.user_id AND receiver_id = p.owner_id) OR (sender_id = p.owner_id AND receiver_id = b.user_id)) 
                        AND offer_status = 2 
                      ORDER BY created_at DESC LIMIT 1) AS bargained_rent
                    FROM bookings b 
                    INNER JOIN properties p ON b.property_id = p.id 
                    WHERE b.id = ? AND b.user_id = ?";
    $stmt_booking = mysqli_prepare($conn, $sql_booking);
    if (!$stmt_booking) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_booking, "ii", $booking_id, $user_id);
    mysqli_stmt_execute($stmt_booking);
    $res_booking = mysqli_stmt_get_result($stmt_booking);
    $booking = mysqli_fetch_assoc($res_booking);
    mysqli_stmt_close($stmt_booking);

    if (!$booking) {
        echo json_encode(array("success" => false, "message" => "Booking record not found or access denied."));
        return;
    }

    $amount = ($booking['bargained_rent'] !== null) ? (int)$booking['bargained_rent'] : (int)$booking['rent'];
    $screenshot_path = null;

    // Handle receipt file upload if present
    if (isset($_FILES['receipt_screenshot']) && $_FILES['receipt_screenshot']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['receipt_screenshot'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(array("success" => false, "message" => "Error uploading receipt screenshot."));
            return;
        }

        // Validate size (3MB limit)
        if ($file['size'] > 3 * 1024 * 1024) {
            echo json_encode(array("success" => false, "message" => "Receipt file size must not exceed 3MB."));
            return;
        }

        // Validate MIME type
        $allowed_types = array('image/jpeg', 'image/png', 'application/pdf');
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(array("success" => false, "message" => "Invalid receipt document type. Only JPG, PNG, and PDF are allowed."));
            return;
        }

        $ext = 'jpg';
        if ($mime_type === 'image/png') {
            $ext = 'png';
        } elseif ($mime_type === 'application/pdf') {
            $ext = 'pdf';
        }

        // Create folder
        $upload_dir = '../img/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate name
        $filename = 'receipt_' . $booking_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest_path)) {
            $screenshot_path = 'img/receipts/' . $filename;
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save uploaded receipt."));
            return;
        }
    }

    // Insert or update payment record
    // We only keep the latest payment attempt/ledger entry per booking
    $sql_check = "SELECT id, screenshot FROM payments WHERE booking_id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if (!$stmt_check) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_check, "i", $booking_id);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    $existing = mysqli_fetch_assoc($res_check);
    mysqli_stmt_close($stmt_check);

    if ($existing) {
        // Delete old screenshot file if exists
        if (!empty($existing['screenshot']) && file_exists('../' . $existing['screenshot'])) {
            @unlink('../' . $existing['screenshot']);
        }

        $sql_update = "UPDATE payments SET amount = ?, status = 0, utr_number = ?, screenshot = ?, payment_date = NOW() WHERE booking_id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        if (!$stmt_update) {
            echo json_encode(array("success" => false, "message" => "Something went wrong!"));
            return;
        }
        mysqli_stmt_bind_param($stmt_update, "issi", $amount, $utr_number, $screenshot_path, $booking_id);
        $result = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    } else {
        $sql_insert = "INSERT INTO payments (booking_id, amount, status, utr_number, screenshot) VALUES (?, ?, 0, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        if (!$stmt_insert) {
            echo json_encode(array("success" => false, "message" => "Something went wrong!"));
            return;
        }
        mysqli_stmt_bind_param($stmt_insert, "iiss", $booking_id, $amount, $utr_number, $screenshot_path);
        $result = mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);
    }

    if ($result) {
        echo json_encode(array("success" => true, "message" => "Payment proof submitted successfully! Pending owner approval."));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to save payment proof in database."));
    }

    mysqli_close($conn);
?>
