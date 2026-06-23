<?php
    require("../includes/database_connect.php");
    require_once("notify.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "Please login to send messages."));
        return;
    }

    $sender_id = (int)$_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $offer_amount = isset($_POST['offer_amount']) ? (int)$_POST['offer_amount'] : 0;

    if ($receiver_id <= 0 || $property_id <= 0 || (empty($message) && $offer_amount <= 0)) {
        echo json_encode(array("success" => false, "message" => "Invalid recipient, property, or empty message."));
        return;
    }

    if ($sender_id === $receiver_id) {
        echo json_encode(array("success" => false, "message" => "You cannot send a message to yourself."));
        return;
    }

    // Verify the receiver has a relationship to this property (owner, booker, or interested)
    $sql_recv_check = "SELECT owner_id FROM properties WHERE id = ?";
    $stmt_recv = mysqli_prepare($conn, $sql_recv_check);
    $receiver_authorized = false;
    if ($stmt_recv) {
        mysqli_stmt_bind_param($stmt_recv, "i", $property_id);
        mysqli_stmt_execute($stmt_recv);
        $res_recv = mysqli_stmt_get_result($stmt_recv);
        if ($row_recv = mysqli_fetch_assoc($res_recv)) {
            if ((int)$row_recv['owner_id'] === $receiver_id) {
                $receiver_authorized = true;
            }
        }
        mysqli_stmt_close($stmt_recv);
    }
    if (!$receiver_authorized) {
        $sql_rel = "SELECT id FROM bookings WHERE user_id = ? AND property_id = ? 
                    UNION SELECT id FROM interested_users_properties WHERE user_id = ? AND property_id = ?";
        $stmt_rel = mysqli_prepare($conn, $sql_rel);
        if ($stmt_rel) {
            mysqli_stmt_bind_param($stmt_rel, "iiii", $receiver_id, $property_id, $receiver_id, $property_id);
            mysqli_stmt_execute($stmt_rel);
            mysqli_stmt_store_result($stmt_rel);
            if (mysqli_stmt_num_rows($stmt_rel) > 0) {
                $receiver_authorized = true;
            }
            mysqli_stmt_close($stmt_rel);
        }
    }
    if (!$receiver_authorized) {
        echo json_encode(array("success" => false, "message" => "The recipient is not associated with this property."));
        return;
    }

    $offer_status = 0;
    $offer_amount_val = null;

    if ($offer_amount > 0) {
        $offer_status = 1; // Pending Offer
        $offer_amount_val = $offer_amount;
        if (empty($message)) {
            $message = "Sent a rent bargain offer of ₹" . number_format($offer_amount) . "/month.";
        }
    }

    $sql_insert = "INSERT INTO messages (sender_id, receiver_id, property_id, message, offer_amount, offer_status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    if (!$stmt_insert) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_insert, "iiisii", $sender_id, $receiver_id, $property_id, $message, $offer_amount_val, $offer_status);
    $result = mysqli_stmt_execute($stmt_insert);
    $msg_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_insert);

    if ($result) {
        // Notify receiver about new message or offer
        $sender_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Someone';
        if ($offer_amount > 0) {
            create_notification($conn, $receiver_id, 'offer', 'New rent offer from ' . $sender_name, $message, '/pg/' . $property_id);
        } else {
            create_notification($conn, $receiver_id, 'message', 'New message from ' . $sender_name, $message, '/pg/' . $property_id);
        }

        echo json_encode(array(
            "success" => true,
            "message" => "Message sent successfully!",
            "data" => array(
                "id" => $msg_id,
                "sender_id" => $sender_id,
                "receiver_id" => $receiver_id,
                "property_id" => $property_id,
                "message" => $message,
                "offer_amount" => $offer_amount_val,
                "offer_status" => $offer_status,
                "created_at" => date('Y-m-d H:i:s'),
                "is_read" => 0
            )
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to save message."));
    }

    mysqli_close($conn);
?>
