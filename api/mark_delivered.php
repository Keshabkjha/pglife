<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Not logged in."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $contact_id = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if ($contact_id <= 0 || $property_id <= 0) {
        echo json_encode(array("success" => false, "message" => "Invalid parameters."));
        return;
    }

    // Mark all undelivered messages from this contact to this user as delivered
    $sql = "UPDATE messages SET delivered_at = NOW() 
            WHERE property_id = ? AND sender_id = ? AND receiver_id = ? AND delivered_at IS NULL";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong."));
        return;
    }
    mysqli_stmt_bind_param($stmt, "iii", $property_id, $contact_id, $user_id);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(array("success" => true, "marked" => $affected));
    mysqli_close($conn);
?>
