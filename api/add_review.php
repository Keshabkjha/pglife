<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to submit a review."));
        return;
    }

    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $user_id = (int)$_SESSION['user_id'];
    $user_name = $_SESSION['full_name'];

    if ($property_id <= 0 || $rating < 1 || $rating > 5 || empty($content)) {
        echo json_encode(array("success" => false, "message" => "Invalid parameters. Please fill in all fields."));
        return;
    }

    if (strlen($content) > 1000) {
        echo json_encode(array("success" => false, "message" => "Review must be under 1000 characters."));
        return;
    }

    // Check for duplicate review by same user on this property
    $sql_check = "SELECT id FROM reviews WHERE property_id = ? AND user_id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "ii", $property_id, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            echo json_encode(array("success" => false, "message" => "You have already submitted a review for this property."));
            mysqli_stmt_close($stmt_check);
            return;
        }
        mysqli_stmt_close($stmt_check);
    }

    $sql = "INSERT INTO reviews (property_id, user_id, user_name, rating, content) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    mysqli_stmt_bind_param($stmt, "iisis", $property_id, $user_id, $user_name, $rating, $content);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }

    // Recalculate average rating for the property and update the properties table
    $sql_avg = "SELECT AVG(rating) as avg_rating FROM reviews WHERE property_id = ?";
    $stmt_avg = mysqli_prepare($conn, $sql_avg);
    if ($stmt_avg) {
        mysqli_stmt_bind_param($stmt_avg, "i", $property_id);
        mysqli_stmt_execute($stmt_avg);
        $res_avg = mysqli_stmt_get_result($stmt_avg);
        if ($res_avg && $row_avg = mysqli_fetch_assoc($res_avg)) {
            $new_avg = round((float)$row_avg['avg_rating'], 1);
            
            // Update cleanliness, food, and safety ratings to this new average
            $sql_update = "UPDATE properties SET rating_clean = ?, rating_food = ?, rating_safety = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "dddi", $new_avg, $new_avg, $new_avg, $property_id);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
            }
        }
        mysqli_stmt_close($stmt_avg);
    }

    echo json_encode(array("success" => true, "message" => "Review submitted successfully!"));
    mysqli_close($conn);
?>
