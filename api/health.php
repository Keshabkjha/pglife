<?php
    // Health Check Endpoint
    // Tests database connection and returns system status JSON

    // Temporarily suppress default database connection error output to output custom JSON
    ob_start();
    require_once("../includes/database_connect.php");
    ob_clean();

    header('Content-Type: application/json; charset=utf-8');

    $db_ok = false;
    if (isset($conn) && $conn) {
        $db_ok = mysqli_ping($conn);
    }

    $status_code = $db_ok ? 200 : 503;
    http_response_code($status_code);

    echo json_encode(array(
        "status" => $db_ok ? "healthy" : "unhealthy",
        "timestamp" => date('Y-m-d H:i:s'),
        "request_id" => defined('REQUEST_ID') ? REQUEST_ID : null,
        "services" => array(
            "database" => $db_ok ? "up" : "down"
        )
    ));

    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
?>
