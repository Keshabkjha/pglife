<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to verify your identity."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];

    if (!isset($_FILES['kyc_doc']) || $_FILES['kyc_doc']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(array("success" => false, "message" => "Please select a document file to upload."));
        return;
    }

    $file = $_FILES['kyc_doc'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array("success" => false, "message" => "Error uploading file."));
        return;
    }

    // Validate size (3MB limit)
    if ($file['size'] > 3 * 1024 * 1024) {
        echo json_encode(array("success" => false, "message" => "Document size must not exceed 3MB."));
        return;
    }

    // Validate MIME type
    $allowed_types = array('application/pdf', 'image/jpeg', 'image/png');
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(array("success" => false, "message" => "Invalid document type. Only PDF, JPG, and PNG are allowed."));
        return;
    }

    // Get file extension
    $ext = 'jpg';
    if ($mime_type === 'image/png') {
        $ext = 'png';
    } elseif ($mime_type === 'application/pdf') {
        $ext = 'pdf';
    }

    // Ensure folder exists
    $upload_dir = '../img/kyc/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique name
    $filename = 'kyc_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        $kyc_doc_path = 'img/kyc/' . $filename;

        // Fetch old document path to delete if exists
        $sql_old = "SELECT kyc_document FROM users WHERE id = ?";
        $stmt_old = mysqli_prepare($conn, $sql_old);
        if ($stmt_old) {
            mysqli_stmt_bind_param($stmt_old, "i", $user_id);
            mysqli_stmt_execute($stmt_old);
            $res_old = mysqli_stmt_get_result($stmt_old);
            if ($res_old && $row_old = mysqli_fetch_assoc($res_old)) {
                $old_doc = $row_old['kyc_document'];
                if (!empty($old_doc) && file_exists('../' . $old_doc)) {
                    @unlink('../' . $old_doc);
                }
            }
            mysqli_stmt_close($stmt_old);
        }

        // Update database: set status to 1 (Pending) and save doc path
        $sql_update = "UPDATE users SET is_verified = 1, kyc_document = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        if (!$stmt_update) {
            echo json_encode(array("success" => false, "message" => "Something went wrong!"));
            return;
        }
        mysqli_stmt_bind_param($stmt_update, "si", $kyc_doc_path, $user_id);
        $result = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

        if ($result) {
            echo json_encode(array("success" => true, "message" => "Document uploaded successfully! Under review."));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to update KYC status in database."));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to save uploaded document."));
    }

    mysqli_close($conn);
?>
