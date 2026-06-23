<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Not logged in."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];

    // Get unread count
    $sql_count = "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt_count = mysqli_prepare($conn, $sql_count);
    $unread_count = 0;
    if ($stmt_count) {
        mysqli_stmt_bind_param($stmt_count, "i", $user_id);
        mysqli_stmt_execute($stmt_count);
        $res_count = mysqli_stmt_get_result($stmt_count);
        if ($row = mysqli_fetch_assoc($res_count)) {
            $unread_count = (int)$row['cnt'];
        }
        mysqli_stmt_close($stmt_count);
    }

    // Get recent notifications (last 20)
    $sql = "SELECT id, type, title, body, link, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20";
    $stmt = mysqli_prepare($conn, $sql);
    $notifications = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $notifications = mysqli_fetch_all($res, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }

    echo json_encode(array(
        "success" => true,
        "unread_count" => $unread_count,
        "data" => $notifications
    ));
    mysqli_close($conn);
?>
