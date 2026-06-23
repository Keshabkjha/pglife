<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to view conversations."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];

    // Fetch distinct contact user IDs and property IDs that have exchanged messages with this user
    $sql_conv = "SELECT DISTINCT 
                        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS contact_id,
                        m.property_id,
                        p.name AS property_name,
                        p.owner_id AS property_owner_id,
                        u.full_name AS contact_name,
                        u.role AS contact_role,
                        u.gender AS contact_gender,
                        u.profile_pic AS contact_profile_pic
                 FROM messages m
                 INNER JOIN properties p ON m.property_id = p.id
                 INNER JOIN users u ON u.id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
                 WHERE m.sender_id = ? OR m.receiver_id = ?
                 LIMIT 50";
    $stmt_conv = mysqli_prepare($conn, $sql_conv);
    if (!$stmt_conv) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_conv, "iiii", $user_id, $user_id, $user_id, $user_id);
    mysqli_stmt_execute($stmt_conv);
    $res_conv = mysqli_stmt_get_result($stmt_conv);
    $conversations = mysqli_fetch_all($res_conv, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_conv);

    $data = array();

    foreach ($conversations as $conv) {
        $contact_id = (int)$conv['contact_id'];
        $property_id = (int)$conv['property_id'];

        // Get last message details
        $sql_last = "SELECT message, created_at, offer_status FROM messages 
                     WHERE property_id = ? 
                       AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
                     ORDER BY created_at DESC LIMIT 1";
        $stmt_last = mysqli_prepare($conn, $sql_last);
        $last_msg = "";
        $last_time = "";
        if ($stmt_last) {
            mysqli_stmt_bind_param($stmt_last, "iiiii", $property_id, $user_id, $contact_id, $contact_id, $user_id);
            mysqli_stmt_execute($stmt_last);
            $res_last = mysqli_stmt_get_result($stmt_last);
            if ($row_last = mysqli_fetch_assoc($res_last)) {
                $last_msg = $row_last['message'];
                $last_time = $row_last['created_at'];
            }
            mysqli_stmt_close($stmt_last);
        }

        // Get unread count (messages sent by contact_id to user_id that are unread)
        $sql_unread = "SELECT COUNT(*) AS count FROM messages 
                       WHERE property_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0";
        $stmt_unread = mysqli_prepare($conn, $sql_unread);
        $unread_count = 0;
        if ($stmt_unread) {
            mysqli_stmt_bind_param($stmt_unread, "iii", $property_id, $contact_id, $user_id);
            mysqli_stmt_execute($stmt_unread);
            $res_unread = mysqli_stmt_get_result($stmt_unread);
            if ($row_unread = mysqli_fetch_assoc($res_unread)) {
                $unread_count = (int)$row_unread['count'];
            }
            mysqli_stmt_close($stmt_unread);
        }

        $data[] = array(
            "contact_id" => $contact_id,
            "contact_name" => $conv['contact_name'],
            "contact_role" => $conv['contact_role'],
            "contact_gender" => $conv['contact_gender'],
            "contact_profile_pic" => $conv['contact_profile_pic'],
            "property_id" => $property_id,
            "property_name" => $conv['property_name'],
            "last_message" => $last_msg,
            "last_time" => $last_time,
            "unread_count" => $unread_count
        );
    }

    // Sort conversations by last message time (most recent first)
    usort($data, function($a, $b) {
        return strcmp($b['last_time'] ?? '', $a['last_time'] ?? '');
    });

    echo json_encode(array("success" => true, "data" => $data));
    mysqli_close($conn);
?>
