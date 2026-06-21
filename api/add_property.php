<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
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
    // Verify all uploaded files are valid images and within file limits
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
    $files = $_FILES['property_images'];
    $file_count = count($files['name']);

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            echo json_encode(array("success" => false, "message" => "Error uploading file: " . $files['name'][$i]));
            return;
        }

        // Limit size to 5MB
        if ($files['size'][$i] > 5 * 1024 * 1024) {
            echo json_encode(array("success" => false, "message" => "File " . $files['name'][$i] . " exceeds the maximum allowed size of 5MB."));
            return;
        }

        // Check mime type
        $mime_type = mime_content_type($files['tmp_name'][$i]);
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(array("success" => false, "message" => "Invalid image type for file: " . $files['name'][$i] . ". Only PNG, JPEG, GIF, and WEBP are allowed."));
            return;
        }

        // Validate image content integrity
        $image_info = getimagesize($files['tmp_name'][$i]);
        if ($image_info === false) {
            echo json_encode(array("success" => false, "message" => "File " . $files['name'][$i] . " is not a valid image file."));
            return;
        }
    }

    // Geocode address using OpenStreetMap Nominatim API on the backend
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

    // Fallback: search for the city center if the full address fails to geocode
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

    // Insert property into database including cached coordinates
    $sql_insert = "INSERT INTO properties (city_id, owner_id, name, address, description, gender, rent, latitude, longitude, rating_clean, rating_food, rating_safety, views) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0.0, 0.0, 0.0, 0)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        echo json_encode(array("success" => false, "message" => "Failed to prepare database query."));
        return;
    }

    mysqli_stmt_bind_param($stmt_insert, "iissssidd", $city_id, $owner_id, $name, $address, $description, $gender, $rent, $latitude, $longitude);
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
        if (!mkdir($dest_dir, 0755, true)) {
            echo json_encode(array("success" => false, "message" => "Property created but failed to create directory for images."));
            return;
        }
    }

    $primary_image_index = isset($_POST['primary_image_index']) ? (int)$_POST['primary_image_index'] : 0;

    // Save files locally
    $mime_map = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];

    $primary_image_filename = null;
    for ($i = 0; $i < $file_count; $i++) {
        $orig_name = $files['name'][$i];
        $mime_type = mime_content_type($files['tmp_name'][$i]);
        $ext = isset($mime_map[$mime_type]) ? $mime_map[$mime_type] : 'jpg';
        
        // Generate a random unique name to prevent collisions or special character bugs
        $new_filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest_path = $dest_dir . $new_filename;
        if (!move_uploaded_file($files['tmp_name'][$i], $dest_path)) {
            echo json_encode(array("success" => false, "message" => "Property created but failed to save image: " . $orig_name));
            return;
        }

        if ($i === $primary_image_index) {
            $primary_image_filename = $new_filename;
        }
    }

    if (!empty($primary_image_filename)) {
        $sql_update_primary = "UPDATE properties SET primary_image = ? WHERE id = ?";
        $stmt_update_primary = mysqli_prepare($conn, $sql_update_primary);
        if ($stmt_update_primary) {
            mysqli_stmt_bind_param($stmt_update_primary, "si", $primary_image_filename, $new_property_id);
            mysqli_stmt_execute($stmt_update_primary);
            mysqli_stmt_close($stmt_update_primary);
        }
    }

    echo json_encode(array("success" => true, "message" => "Property successfully listed!"));
    mysqli_close($conn);
?>
