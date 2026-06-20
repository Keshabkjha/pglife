<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to view messages."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
    $property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

    if ($contact_id <= 0 || $property_id <= 0) {
        echo json_encode(array("success" => false, "message" => "Invalid contact or property reference."));
        return;
    }

    // Mark incoming messages as read
    $sql_read = "UPDATE messages SET is_read = 1 
                 WHERE property_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0";
    $stmt_read = mysqli_prepare($conn, $sql_read);
    if ($stmt_read) {
        mysqli_stmt_bind_param($stmt_read, "iii", $property_id, $contact_id, $user_id);
        mysqli_stmt_execute($stmt_read);
        mysqli_stmt_close($stmt_read);
    }

    // Fetch conversation history
    $sql_history = "SELECT * FROM messages 
                    WHERE property_id = ? 
                      AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
                    ORDER BY created_at ASC";
    $stmt_history = mysqli_prepare($conn, $sql_history);
    if (!$stmt_history) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_history, "iiiii", $property_id, $user_id, $contact_id, $contact_id, $user_id);
    mysqli_stmt_execute($stmt_history);
    $res_history = mysqli_stmt_get_result($stmt_history);
    $messages = mysqli_fetch_all($res_history, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_history);

    echo json_encode(array("success" => true, "data" => $messages));
    mysqli_close($conn);
?>
