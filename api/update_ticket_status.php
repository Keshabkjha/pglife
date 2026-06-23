<?php
    require("../includes/database_connect.php");
    require_once("notify.php");
    header('Content-Type: application/json; charset=utf-8');

    // 1. Verify CSRF Token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(array("success" => false, "message" => "Security verification failed (CSRF token mismatch)."));
        return;
    }

    // 2. Enforce Authentication
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        echo json_encode(array("success" => false, "message" => "Unauthorized access. Please login."));
        return;
    }

    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];
    $ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // 3. Validate Parameters
    if ($ticket_id <= 0 || ($status !== 'open' && $status !== 'resolved')) {
        echo json_encode(array("success" => false, "message" => "Invalid parameters."));
        return;
    }

    // 4. Fetch Ticket Details
    $sql_ticket = "SELECT property_id, user_id FROM maintenance_tickets WHERE id = ?";
    $stmt_ticket = mysqli_prepare($conn, $sql_ticket);
    if (!$stmt_ticket) {
        echo json_encode(array("success" => false, "message" => "Database error."));
        return;
    }
    mysqli_stmt_bind_param($stmt_ticket, "i", $ticket_id);
    mysqli_stmt_execute($stmt_ticket);
    $result_ticket = mysqli_stmt_get_result($stmt_ticket);
    $ticket = mysqli_fetch_assoc($result_ticket);
    mysqli_stmt_close($stmt_ticket);

    if (!$ticket) {
        echo json_encode(array("success" => false, "message" => "Maintenance ticket not found."));
        return;
    }

    $property_id = (int)$ticket['property_id'];
    $ticket_owner_id = (int)$ticket['user_id'];

    // 5. Enforce Access Rules (Owners must own the property; Seekers must own the ticket)
    if ($role === 'owner') {
        $sql_owner = "SELECT id FROM properties WHERE id = ? AND owner_id = ?";
        $stmt_owner = mysqli_prepare($conn, $sql_owner);
        if (!$stmt_owner) {
            echo json_encode(array("success" => false, "message" => "Database check failed."));
            return;
        }
        mysqli_stmt_bind_param($stmt_owner, "ii", $property_id, $user_id);
        mysqli_stmt_execute($stmt_owner);
        mysqli_stmt_store_result($stmt_owner);
        $owner_check_count = mysqli_stmt_num_rows($stmt_owner);
        mysqli_stmt_close($stmt_owner);

        if ($owner_check_count === 0) {
            echo json_encode(array("success" => false, "message" => "Access Denied. You do not own the property associated with this complaint."));
            return;
        }
    } else if ($role === 'seeker') {
        if ($user_id !== $ticket_owner_id) {
            echo json_encode(array("success" => false, "message" => "Access Denied. You cannot modify other users' maintenance tickets."));
            return;
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid profile type."));
        return;
    }

    // Get property name to notify seeker
    $prop_name = '';
    $sql_prop = "SELECT name FROM properties WHERE id = ?";
    $stmt_prop = mysqli_prepare($conn, $sql_prop);
    if ($stmt_prop) {
        mysqli_stmt_bind_param($stmt_prop, "i", $property_id);
        mysqli_stmt_execute($stmt_prop);
        $res_prop = mysqli_stmt_get_result($stmt_prop);
        if ($row_prop = mysqli_fetch_assoc($res_prop)) {
            $prop_name = $row_prop['name'];
        }
        mysqli_stmt_close($stmt_prop);
    }

    // 6. Update Ticket Status
    $sql_update = "UPDATE maintenance_tickets SET status = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    if (!$stmt_update) {
        echo json_encode(array("success" => false, "message" => "Failed to update ticket. Database error."));
        return;
    }
    mysqli_stmt_bind_param($stmt_update, "si", $status, $ticket_id);
    $result_update = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if ($result_update) {
        if ($role === 'owner') {
            // Notify seeker about status update
            $notif_title = 'Maintenance ticket ' . $status;
            $notif_body = 'Your maintenance ticket for ' . $prop_name . ' has been marked as ' . $status . '.';
            create_notification($conn, $ticket_owner_id, 'message', $notif_title, $notif_body, '/dashboard');
        }
        echo json_encode(array("success" => true, "message" => "Ticket status successfully updated to '" . $status . "'!", "ticket_id" => $ticket_id, "status" => $status));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to update ticket status."));
    }

    mysqli_close($conn);
?>
