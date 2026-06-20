<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Only owners can delete listings."));
        return;
    }

    $owner_id = (int)$_SESSION['user_id'];
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if ($property_id <= 0) {
        echo json_encode(array("success" => false, "message" => "Invalid property ID."));
        return;
    }

    // Verify property ownership
    $sql_verify = "SELECT id FROM properties WHERE id = ? AND owner_id = ?";
    $stmt_verify = mysqli_prepare($conn, $sql_verify);
    if (!$stmt_verify) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_verify, "ii", $property_id, $owner_id);
    mysqli_stmt_execute($stmt_verify);
    mysqli_stmt_store_result($stmt_verify);
    if (mysqli_stmt_num_rows($stmt_verify) === 0) {
        echo json_encode(array("success" => false, "message" => "Property not found or access denied."));
        mysqli_stmt_close($stmt_verify);
        return;
    }
    mysqli_stmt_close($stmt_verify);

    // Delete associated records first (due to FK constraints without cascade)
    $tables = [
        "properties_amenities",
        "bookings",
        "interested_users_properties",
        "reviews",
        "testimonials"
    ];

    foreach ($tables as $table) {
        $sql_del = "DELETE FROM {$table} WHERE property_id = ?";
        $stmt_del = mysqli_prepare($conn, $sql_del);
        if ($stmt_del) {
            mysqli_stmt_bind_param($stmt_del, "i", $property_id);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);
        }
    }

    // Delete property itself
    $sql_del_prop = "DELETE FROM properties WHERE id = ? AND owner_id = ?";
    $stmt_del_prop = mysqli_prepare($conn, $sql_del_prop);
    if (!$stmt_del_prop) {
        echo json_encode(array("success" => false, "message" => "Failed to delete property listing."));
        return;
    }
    mysqli_stmt_bind_param($stmt_del_prop, "ii", $property_id, $owner_id);
    $result = mysqli_stmt_execute($stmt_del_prop);
    mysqli_stmt_close($stmt_del_prop);

    if (!$result) {
        echo json_encode(array("success" => false, "message" => "Failed to delete property from the database."));
        return;
    }

    // Delete property images directory from filesystem
    $dest_dir = "../img/properties/" . $property_id;
    deleteDirectory($dest_dir);

    echo json_encode(array("success" => true, "message" => "Property listing and all associated records deleted successfully."));
    mysqli_close($conn);

    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
?>
