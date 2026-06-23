<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Not logged in."));
        return;
    }

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

    if ($message_id <= 0) {
        echo json_encode(array("success" => false, "message" => "Invalid message ID."));
        return;
    }

    // Fetch message to verify ownership
    $sql_fetch = "SELECT sender_id, receiver_id FROM messages WHERE id = ?";
    $stmt_fetch = mysqli_prepare($conn, $sql_fetch);
    if (!$stmt_fetch) {
        echo json_encode(array("success" => false, "message" => "Something went wrong."));
        return;
    }
    mysqli_stmt_bind_param($stmt_fetch, "i", $message_id);
    mysqli_stmt_execute($stmt_fetch);
    $res = mysqli_stmt_get_result($stmt_fetch);
    $msg = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt_fetch);

    if (!$msg) {
        echo json_encode(array("success" => false, "message" => "Message not found."));
        return;
    }

    // Soft-delete based on who is deleting
    if ((int)$msg['sender_id'] === $user_id) {
        $sql = "UPDATE messages SET deleted_by_sender = 1 WHERE id = ?";
    } elseif ((int)$msg['receiver_id'] === $user_id) {
        $sql = "UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?";
    } else {
        echo json_encode(array("success" => false, "message" => "Access denied."));
        return;
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(array("success" => false, "message" => "Something went wrong."));
        return;
    }
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        echo json_encode(array("success" => true, "message_id" => $message_id, "message" => "Message deleted."));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to delete message."));
    }

    mysqli_close($conn);
?>
