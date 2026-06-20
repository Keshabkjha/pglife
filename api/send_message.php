<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
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
