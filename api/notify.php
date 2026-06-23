<?php
    // Notification helper - included by other API endpoints to create notifications
    function create_notification($conn, $user_id, $type, $title, $body, $link = null) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) return;

        $sql = "INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issss", $user_id, $type, $title, $body, $link);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
?>
