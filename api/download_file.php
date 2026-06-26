<?php
    require("../includes/database_connect.php");
    require_once("../includes/secure_storage.php");

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $stored_path = null;
    $download_name = 'document';

    if ($type === 'kyc') {
        $sql = "SELECT kyc_document FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            http_response_code(500);
            echo 'Unable to process request';
            exit;
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row || empty($row['kyc_document'])) {
            http_response_code(404);
            echo 'Document not found';
            exit;
        }

        $stored_path = $row['kyc_document'];
        $download_name = 'kyc_document';
    } elseif ($type === 'receipt') {
        $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
        if ($payment_id <= 0) {
            http_response_code(400);
            echo 'Invalid payment reference';
            exit;
        }

        $sql = "SELECT pay.screenshot, b.user_id AS seeker_id, p.owner_id
                FROM payments pay
                INNER JOIN bookings b ON pay.booking_id = b.id
                INNER JOIN properties p ON b.property_id = p.id
                WHERE pay.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            http_response_code(500);
            echo 'Unable to process request';
            exit;
        }
        mysqli_stmt_bind_param($stmt, "i", $payment_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row || empty($row['screenshot'])) {
            http_response_code(404);
            echo 'Receipt not found';
            exit;
        }

        $seeker_id = (int)$row['seeker_id'];
        $owner_id = (int)$row['owner_id'];
        if ($user_id !== $seeker_id && $user_id !== $owner_id) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }

        $stored_path = $row['screenshot'];
        $download_name = 'payment_receipt_' . $payment_id;
    } else {
        http_response_code(400);
        echo 'Invalid download type';
        exit;
    }

    $file_path = resolve_stored_file_path($stored_path);
    if (!$file_path || !is_file($file_path)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }

    $mime = mime_type_for_file($file_path);
    $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    if ($ext) {
        $download_name .= '.' . $ext;
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($download_name) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($file_path);
    mysqli_close($conn);
    exit;
