<?php
    require_once __DIR__ . '/logger.php';

    // Load local environment variables from .env file if present (skip inside Docker)
    $env_path = __DIR__ . '/../.env';
    if (file_exists($env_path) && !file_exists('/.dockerenv')) {
        $env_lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($env_lines as $env_line) {
            if (strpos(trim($env_line), '#') === 0) {
                continue;
            }
            $parts = explode('=', $env_line, 2);
            if (count($parts) === 2) {
                $env_name = trim($parts[0]);
                $env_val = trim($parts[1]);
                putenv("{$env_name}={$env_val}");
                $_ENV[$env_name] = $env_val;
                $_SERVER[$env_name] = $env_val;
            }
        }
    }

    require_once __DIR__ . '/app_config.php';

    ini_set('log_errors', '1');
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('expose_php', '0');

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if (request_is_https()) {
            ini_set('session.cookie_secure', '1');
        }
        session_start();
    }

    require_once __DIR__ . '/security_headers.php';

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $db_host = getenv('DB_HOST') ?: "localhost:3307";
    $db_user = getenv('DB_USER') ?: "root";
    $db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";
    $db_name = getenv('DB_NAME') ?: "pglife";

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);
    
    if(mysqli_connect_errno()){
        error_log("Database connection failed: " . mysqli_connect_error());
        http_response_code(500);
        echo "A secure connection to the database could not be established. Please check server logs or try again later.";
        exit;
    }
?>