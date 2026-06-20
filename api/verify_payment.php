<?php
    require("../includes/database_connect.php");
    header('Content-Type: application/json; charset=utf-8');

    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Only owners can verify payments."));
        return;
    }

    $owner_id = (int)$_SESSION['user_id'];
    $payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($payment_id <= 0 || !in_array($action, array('approve', 'reject'))) {
        echo json_encode(array("success" => false, "message" => "Invalid parameters."));
        return;
    }

    // Verify ownership of the property associated with the payment
    $sql_check = "SELECT p.owner_id FROM payments pay 
                  INNER JOIN bookings b ON pay.booking_id = b.id 
                  INNER JOIN properties p ON b.property_id = p.id 
                  WHERE pay.id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if (!$stmt_check) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_check, "i", $payment_id);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    $row = mysqli_fetch_assoc($res_check);
    mysqli_stmt_close($stmt_check);

    if (!$row) {
        echo json_encode(array("success" => false, "message" => "Payment record not found."));
        return;
    }

    if ((int)$row['owner_id'] !== $owner_id) {
        echo json_encode(array("success" => false, "message" => "Access denied. You do not own the property linked to this payment."));
        return;
    }

    // Determine status
    $new_status = ($action === 'approve') ? 1 : 2;

    $sql_update = "UPDATE payments SET status = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if (!$stmt_update) {
        echo json_encode(array("success" => false, "message" => "Something went wrong!"));
        return;
    }
    mysqli_stmt_bind_param($stmt_update, "ii", $new_status, $payment_id);
    $result = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if ($result) {
        $status_label = ($new_status === 1) ? "approved" : "rejected";
        echo json_encode(array("success" => true, "message" => "Payment successfully " . $status_label . "!"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to update payment status."));
    }

    mysqli_close($conn);
?>
