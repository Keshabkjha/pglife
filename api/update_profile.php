<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to update your profile."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $institution_or_organization = isset($_POST['institution_or_organization']) ? trim($_POST['institution_or_organization']) : '';
    $upi_id = isset($_POST['upi_id']) ? trim($_POST['upi_id']) : '';

    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if ($role === 'owner' && !empty($upi_id)) {
        if (!preg_match('/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/', $upi_id)) {
            echo json_encode(array("success" => false, "message" => "Invalid UPI ID format. E.g., user@upi."));
            return;
        }
    }

    if (empty($full_name) || empty($phone) || empty($institution_or_organization)) {
        echo json_encode(array("success" => false, "message" => "All fields are required."));
        return;
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(array("success" => false, "message" => "Phone number must be exactly 10 digits."));
        return;
    }

    $profile_pic_path = null;
    $upload_success = false;

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_pic'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(array("success" => false, "message" => "Error uploading profile picture."));
            return;
        }

        // Validate size
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(array("success" => false, "message" => "Profile picture size must not exceed 2MB."));
            return;
        }

        // Validate MIME type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp', 'image/gif');
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(array("success" => false, "message" => "Invalid image type. Only JPG, PNG, WEBP, and GIF are allowed."));
            return;
        }

        // Get file extension
        $ext = 'jpg';
        if ($mime_type === 'image/png') $ext = 'png';
        elseif ($mime_type === 'image/webp') $ext = 'webp';
        elseif ($mime_type === 'image/gif') $ext = 'gif';

        // Ensure folder exists
        $upload_dir = '../img/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique name
        $filename = $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest_path)) {
            $profile_pic_path = 'img/profiles/' . $filename;
            $upload_success = true;

            // Delete old profile picture if exists
            $sql_old = "SELECT profile_pic FROM users WHERE id = ?";
            $stmt_old = mysqli_prepare($conn, $sql_old);
            if ($stmt_old) {
                mysqli_stmt_bind_param($stmt_old, "i", $user_id);
                mysqli_stmt_execute($stmt_old);
                $res_old = mysqli_stmt_get_result($stmt_old);
                if ($res_old && $row_old = mysqli_fetch_assoc($res_old)) {
                    $old_pic = $row_old['profile_pic'];
                    if (!empty($old_pic) && file_exists('../' . $old_pic)) {
                        @unlink('../' . $old_pic);
                    }
                }
                mysqli_stmt_close($stmt_old);
            }
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to save uploaded profile picture."));
            return;
        }
    }

    if ($role === 'owner') {
        if ($upload_success) {
            $sql = "UPDATE users SET full_name = ?, phone = ?, institution_or_organization = ?, profile_pic = ?, upi_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(array("success" => false, "message" => "Something went wrong!"));
                return;
            }
            mysqli_stmt_bind_param($stmt, "sssssi", $full_name, $phone, $institution_or_organization, $profile_pic_path, $upi_id, $user_id);
        } else {
            $sql = "UPDATE users SET full_name = ?, phone = ?, institution_or_organization = ?, upi_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(array("success" => false, "message" => "Something went wrong!"));
                return;
            }
            mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $phone, $institution_or_organization, $upi_id, $user_id);
        }
    } else {
        if ($upload_success) {
            $sql = "UPDATE users SET full_name = ?, phone = ?, institution_or_organization = ?, profile_pic = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(array("success" => false, "message" => "Something went wrong!"));
                return;
            }
            mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $phone, $institution_or_organization, $profile_pic_path, $user_id);
        } else {
            $sql = "UPDATE users SET full_name = ?, phone = ?, institution_or_organization = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                echo json_encode(array("success" => false, "message" => "Something went wrong!"));
                return;
            }
            mysqli_stmt_bind_param($stmt, "sssi", $full_name, $phone, $institution_or_organization, $user_id);
        }
    }

    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    // Update Session
    $_SESSION['full_name'] = $full_name;
    if ($upload_success) {
        $_SESSION['profile_pic'] = $profile_pic_path;
    }

    echo json_encode(array("success" => true, "message" => "Profile successfully updated!"));
    mysqli_close($conn);
?>
