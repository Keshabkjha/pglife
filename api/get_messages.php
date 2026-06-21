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

    // Authorization: verify user has a relationship to this property
    $sql_auth = "SELECT p.owner_id FROM properties p WHERE p.id = ?";
    $stmt_auth = mysqli_prepare($conn, $sql_auth);
    $authorized = false;
    if ($stmt_auth) {
        mysqli_stmt_bind_param($stmt_auth, "i", $property_id);
        mysqli_stmt_execute($stmt_auth);
        $res_auth = mysqli_stmt_get_result($stmt_auth);
        if ($row_auth = mysqli_fetch_assoc($res_auth)) {
            if ((int)$row_auth['owner_id'] === $user_id) {
                $authorized = true;
            }
        }
        mysqli_stmt_close($stmt_auth);
    }
    if (!$authorized) {
        // Check if user has a booking or interest in this property
        $sql_rel = "SELECT id FROM bookings WHERE user_id = ? AND property_id = ? 
                    UNION SELECT id FROM interested_users_properties WHERE user_id = ? AND property_id = ?";
        $stmt_rel = mysqli_prepare($conn, $sql_rel);
        if ($stmt_rel) {
            mysqli_stmt_bind_param($stmt_rel, "iiii", $user_id, $property_id, $user_id, $property_id);
            mysqli_stmt_execute($stmt_rel);
            mysqli_stmt_store_result($stmt_rel);
            if (mysqli_stmt_num_rows($stmt_rel) > 0) {
                $authorized = true;
            }
            mysqli_stmt_close($stmt_rel);
        }
    }
    if (!$authorized) {
        echo json_encode(array("success" => false, "message" => "Access denied."));
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
