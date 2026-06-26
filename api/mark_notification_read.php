<?php
    require("../includes/database_connect.php");
    require_once("../includes/app_config.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Not logged in."));
        return;
    }

    require_csrf_token();

    $user_id = (int)$_SESSION['user_id'];
    $mark_all = isset($_POST['all']) && $_POST['all'] === '1';
    $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

    if ($mark_all) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        echo json_encode(array("success" => true, "message" => "All notifications marked as read."));
    } elseif ($notification_id > 0) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        echo json_encode(array("success" => true, "message" => "Notification marked as read."));
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid request."));
    }

    mysqli_close($conn);
?>
