<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $db_host = getenv('DB_HOST') ?: "localhost:3307";
    $db_user = getenv('DB_USER') ?: "root";
    $db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";
    $db_name = getenv('DB_NAME') ?: "pglife";

    $conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);
    
    if(mysqli_connect_errno()){
        echo "Connection Error: ".mysqli_connect_error();
        return;
    }
?>