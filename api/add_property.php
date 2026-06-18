<?php
    session_start();
    require("../includes/database_connect.php");

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Only owners can list properties."));
        return;
    }

    $owner_id = (int)$_SESSION['user_id'];
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $city_id = isset($_POST['city_id']) ? (int)$_POST['city_id'] : 0;
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $rent = isset($_POST['rent']) ? (int)$_POST['rent'] : 0;
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];

    if (empty($name) || $city_id <= 0 || empty($address) || empty($description) || empty($gender) || $rent <= 0) {
        echo json_encode(array("success" => false, "message" => "All text/number fields are required."));
        return;
    }

    if ($gender !== 'male' && $gender !== 'female' && $gender !== 'unisex') {
        echo json_encode(array("success" => false, "message" => "Please select a valid gender preference."));
        return;
    }

    // Check if at least one image is uploaded
    if (!isset($_FILES['property_images']) || empty($_FILES['property_images']['name'][0])) {
        echo json_encode(array("success" => false, "message" => "Please upload at least one image for the property."));
        return;
    }

    // Verify all uploaded files are valid images
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
    $files = $_FILES['property_images'];
    $file_count = count($files['name']);

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            echo json_encode(array("success" => false, "message" => "Error uploading file: " . $files['name'][$i]));
            return;
        }
        $mime_type = mime_content_type($files['tmp_name'][$i]);
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(array("success" => false, "message" => "Invalid image type for file: " . $files['name'][$i] . ". Only PNG, JPEG, GIF, and WEBP are allowed."));
            return;
        }
    }

    // Insert property into database
    $sql_insert = "INSERT INTO properties (city_id, owner_id, name, address, description, gender, rent, rating_clean, rating_food, rating_safety, views) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 0.0, 0.0, 0.0, 0)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        echo json_encode(array("success" => false, "message" => "Failed to prepare database query."));
        return;
    }

    mysqli_stmt_bind_param($stmt_insert, "iissssi", $city_id, $owner_id, $name, $address, $description, $gender, $rent);
    $result_insert = mysqli_stmt_execute($stmt_insert);

    if (!$result_insert) {
        echo json_encode(array("success" => false, "message" => "Failed to add property to the database."));
        return;
    }

    $new_property_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_insert);

    // Insert amenities mappings
    if (!empty($amenities)) {
        $sql_amenity = "INSERT INTO properties_amenities (property_id, amenity_id) VALUES (?, ?)";
        $stmt_amenity = mysqli_prepare($conn, $sql_amenity);
        if ($stmt_amenity) {
            foreach ($amenities as $amenity_id) {
                $amenity_id = (int)$amenity_id;
                mysqli_stmt_bind_param($stmt_amenity, "ii", $new_property_id, $amenity_id);
                mysqli_stmt_execute($stmt_amenity);
            }
            mysqli_stmt_close($stmt_amenity);
        }
    }

    // Create target directory for property images
    $dest_dir = "../img/properties/" . $new_property_id . "/";
    if (!file_exists($dest_dir)) {
        if (!mkdir($dest_dir, 0777, true)) {
            echo json_encode(array("success" => false, "message" => "Property created but failed to create directory for images."));
            return;
        }
    }

    // Save files locally
    for ($i = 0; $i < $file_count; $i++) {
        $orig_name = $files['name'][$i];
        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
        // Generate a random unique name to prevent collisions or special character bugs
        $new_filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest_path = $dest_dir . $new_filename;
        if (!move_uploaded_file($files['tmp_name'][$i], $dest_path)) {
            echo json_encode(array("success" => false, "message" => "Property created but failed to save image: " . $orig_name));
            return;
        }
    }

    echo json_encode(array("success" => true, "message" => "Property successfully listed!"));
    mysqli_close($conn);
?>
