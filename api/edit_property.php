<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Only owners can edit listings."));
        return;
    }

    $owner_id = (int)$_SESSION['user_id'];
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $city_id = isset($_POST['city_id']) ? (int)$_POST['city_id'] : 0;
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $rent = isset($_POST['rent']) ? (int)$_POST['rent'] : 0;
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    $deleted_images = isset($_POST['deleted_images']) ? $_POST['deleted_images'] : [];
    $primary_image = isset($_POST['primary_image']) ? trim($_POST['primary_image']) : '';
    $new_primary_image_index = isset($_POST['new_primary_image_index']) && $_POST['new_primary_image_index'] !== '' ? (int)$_POST['new_primary_image_index'] : -1;

    if ($property_id <= 0 || empty($name) || $city_id <= 0 || empty($address) || empty($description) || empty($gender) || $rent <= 0) {
        echo json_encode(array("success" => false, "message" => "All text/number fields are required."));
        return;
    }

    if ($gender !== 'male' && $gender !== 'female' && $gender !== 'unisex') {
        echo json_encode(array("success" => false, "message" => "Please select a valid gender preference."));
        return;
    }

    // Verify property ownership and get current address
    $sql_verify = "SELECT address, latitude, longitude FROM properties WHERE id = ? AND owner_id = ?";
    $stmt_verify = mysqli_prepare($conn, $sql_verify);
    if (!$stmt_verify) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_verify, "ii", $property_id, $owner_id);
    mysqli_stmt_execute($stmt_verify);
    $res_verify = mysqli_stmt_get_result($stmt_verify);
    $current_prop = mysqli_fetch_assoc($res_verify);
    mysqli_stmt_close($stmt_verify);

    if (!$current_prop) {
        echo json_encode(array("success" => false, "message" => "Property not found or access denied."));
        return;
    }

    $latitude = $current_prop['latitude'];
    $longitude = $current_prop['longitude'];

    // If address changed, re-geocode
    if (strtolower($address) !== strtolower($current_prop['address']) || $latitude === null || $longitude === null) {
        $latitude = null;
        $longitude = null;
        
        $query = urlencode($address);
        $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&countrycodes=in&limit=1";
        
        $ua_email = getenv('SMTP_USER') ?: 'keshabkjha11@gmail.com';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    "User-Agent: PGLifeApp/1.0 ($ua_email)"
                ],
                'timeout' => 5
            ]
        ];
        $context = stream_context_create($opts);
        $geocode_response = @file_get_contents($url, false, $context);
        if ($geocode_response) {
            $geocode_data = json_decode($geocode_response, true);
            if (!empty($geocode_data) && isset($geocode_data[0]['lat']) && isset($geocode_data[0]['lon'])) {
                $latitude = (float)$geocode_data[0]['lat'];
                $longitude = (float)$geocode_data[0]['lon'];
            }
        }

        // Fallback geocode using City Center
        if ($latitude === null || $longitude === null) {
            $sql_city_name = "SELECT name FROM cities WHERE id = ?";
            $stmt_city = mysqli_prepare($conn, $sql_city_name);
            if ($stmt_city) {
                mysqli_stmt_bind_param($stmt_city, "i", $city_id);
                mysqli_stmt_execute($stmt_city);
                $res_city = mysqli_stmt_get_result($stmt_city);
                if ($res_city && $row_city = mysqli_fetch_assoc($res_city)) {
                    $city_query = urlencode($row_city['name']);
                    $url_city = "https://nominatim.openstreetmap.org/search?format=json&q={$city_query}&countrycodes=in&limit=1";
                    $geocode_response_city = @file_get_contents($url_city, false, $context);
                    if ($geocode_response_city) {
                        $geocode_data_city = json_decode($geocode_response_city, true);
                        if (!empty($geocode_data_city) && isset($geocode_data_city[0]['lat']) && isset($geocode_data_city[0]['lon'])) {
                            $latitude = (float)$geocode_data_city[0]['lat'];
                            $longitude = (float)$geocode_data_city[0]['lon'];
                        }
                    }
                }
                mysqli_stmt_close($stmt_city);
            }
        }
    }

    // Delete requested images from disk
    if (!empty($deleted_images)) {
        $dest_dir = "../img/properties/" . $property_id . "/";
        foreach ($deleted_images as $img_file) {
            $img_file = basename($img_file);
            $file_path = $dest_dir . $img_file;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            if ($img_file === $primary_image) {
                $primary_image = '';
            }
        }
    }

    // Handle optional extra image uploads
    if (isset($_FILES['property_images']) && !empty($_FILES['property_images']['name'][0])) {
        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        $files = $_FILES['property_images'];
        $file_count = count($files['name']);

        // Verify uploads
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                if ($files['size'][$i] > 5 * 1024 * 1024) {
                    echo json_encode(array("success" => false, "message" => "File " . $files['name'][$i] . " exceeds the maximum allowed size of 5MB."));
                    return;
                }
                $mime_type = mime_content_type($files['tmp_name'][$i]);
                if (!in_array($mime_type, $allowed_types)) {
                    echo json_encode(array("success" => false, "message" => "Invalid image type for file: " . $files['name'][$i]));
                    return;
                }
                $image_info = getimagesize($files['tmp_name'][$i]);
                if ($image_info === false) {
                    echo json_encode(array("success" => false, "message" => "Invalid image contents: " . $files['name'][$i]));
                    return;
                }
            }
        }

        // Save new images
        $dest_dir = "../img/properties/" . $property_id . "/";
        if (!file_exists($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }
        $mime_map = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $mime_type = mime_content_type($files['tmp_name'][$i]);
                $ext = isset($mime_map[$mime_type]) ? $mime_map[$mime_type] : 'jpg';
                $new_filename = bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], $dest_dir . $new_filename)) {
                    if ($i === $new_primary_image_index) {
                        $primary_image = $new_filename;
                    }
                }
            }
        }
    }

    // Update properties table
    $sql_update = "UPDATE properties 
                   SET city_id = ?, name = ?, address = ?, description = ?, gender = ?, rent = ?, latitude = ?, longitude = ?, primary_image = ? 
                   WHERE id = ? AND owner_id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if (!$stmt_update) {
        echo json_encode(array("success" => false, "message" => "Failed to prepare update query."));
        return;
    }
    $p_img_val = !empty($primary_image) ? $primary_image : null;
    mysqli_stmt_bind_param($stmt_update, "issssiddsii", $city_id, $name, $address, $description, $gender, $rent, $latitude, $longitude, $p_img_val, $property_id, $owner_id);
    $result_update = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if (!$result_update) {
        echo json_encode(array("success" => false, "message" => "Failed to update property details."));
        return;
    }

    // Refresh amenities mappings
    $sql_del_amenities = "DELETE FROM properties_amenities WHERE property_id = ?";
    $stmt_del_amenities = mysqli_prepare($conn, $sql_del_amenities);
    if ($stmt_del_amenities) {
        mysqli_stmt_bind_param($stmt_del_amenities, "i", $property_id);
        mysqli_stmt_execute($stmt_del_amenities);
        mysqli_stmt_close($stmt_del_amenities);
    }

    if (!empty($amenities)) {
        $sql_ins_amenity = "INSERT INTO properties_amenities (property_id, amenity_id) VALUES (?, ?)";
        $stmt_ins_amenity = mysqli_prepare($conn, $sql_ins_amenity);
        if ($stmt_ins_amenity) {
            foreach ($amenities as $amenity_id) {
                $amenity_id = (int)$amenity_id;
                mysqli_stmt_bind_param($stmt_ins_amenity, "ii", $property_id, $amenity_id);
                mysqli_stmt_execute($stmt_ins_amenity);
            }
            mysqli_stmt_close($stmt_ins_amenity);
        }
    }

    echo json_encode(array("success" => true, "message" => "Property listings details successfully updated!"));
    mysqli_close($conn);
?>
