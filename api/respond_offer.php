<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Only owners can respond to bargaining offers."));
        return;
    }

    $owner_id = (int)$_SESSION['user_id'];
    $message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($message_id <= 0 || !in_array($action, array('accept', 'decline'))) {
        echo json_encode(array("success" => false, "message" => "Invalid parameters."));
        return;
    }

    // Fetch the bargaining message and verify ownership
    $sql_offer = "SELECT m.*, p.owner_id, p.name AS property_name 
                  FROM messages m 
                  INNER JOIN properties p ON m.property_id = p.id 
                  WHERE m.id = ?";
    $stmt_offer = mysqli_prepare($conn, $sql_offer);
    if (!$stmt_offer) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_offer, "i", $message_id);
    mysqli_stmt_execute($stmt_offer);
    $res_offer = mysqli_stmt_get_result($stmt_offer);
    $offer = mysqli_fetch_assoc($res_offer);
    mysqli_stmt_close($stmt_offer);

    if (!$offer) {
        echo json_encode(array("success" => false, "message" => "Bargaining offer not found."));
        return;
    }

    if ((int)$offer['owner_id'] !== $owner_id) {
        echo json_encode(array("success" => false, "message" => "Access denied. You do not own this property."));
        return;
    }

    if ((int)$offer['offer_status'] !== 1) {
        echo json_encode(array("success" => false, "message" => "This offer is no longer pending or is invalid."));
        return;
    }

    // Set offer status: 2 (Accepted) or 3 (Declined)
    $new_status = ($action === 'accept') ? 2 : 3;
    $status_text = ($action === 'accept') ? 'accepted' : 'declined';

    // Start transaction to update and insert system message atomically
    mysqli_begin_transaction($conn);

    $sql_update = "UPDATE messages SET offer_status = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if (!$stmt_update) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Failed to update offer status."));
        return;
    }
    mysqli_stmt_bind_param($stmt_update, "ii", $new_status, $message_id);
    $result_update = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if (!$result_update) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Failed to update offer status."));
        return;
    }

    // Insert system message logging response
    $system_msg = "Offer of ₹" . number_format($offer['offer_amount']) . "/month has been " . $status_text . " by the property manager.";
    $sql_system = "INSERT INTO messages (sender_id, receiver_id, property_id, message, offer_status) VALUES (?, ?, ?, ?, 0)";
    $stmt_system = mysqli_prepare($conn, $sql_system);
    if (!$stmt_system) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Failed to log system message."));
        return;
    }
    // Sender is owner (owner_id), recipient is seeker (offer['sender_id'])
    mysqli_stmt_bind_param($stmt_system, "iiis", $owner_id, $offer['sender_id'], $offer['property_id'], $system_msg);
    $result_system = mysqli_stmt_execute($stmt_system);
    mysqli_stmt_close($stmt_system);

    if (!$result_system) {
        mysqli_rollback($conn);
        echo json_encode(array("success" => false, "message" => "Failed to log system message."));
        return;
    }

    mysqli_commit($conn);

    echo json_encode(array("success" => true, "message" => "Offer successfully " . $status_text . "!"));
    mysqli_close($conn);
?>
