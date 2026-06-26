<?php
    // Structured Logger for PGLife

    if (!defined('REQUEST_ID')) {
        // Generate unique request ID if not defined
        $requestId = bin2hex(random_bytes(16));
        define('REQUEST_ID', $requestId);
        
        // Add header to response
        if (!headers_sent()) {
            header("X-Request-ID: " . REQUEST_ID);
        }
    }

    /**
     * Logs a message with severity level and context.
     * Log file is stored in storage/logs/app.log which is outside public web access.
     */
    function log_message($level, $message, $context = []) {
        $log_dir = __DIR__ . '/../storage/logs';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/app.log';
        
        $log_entry = array(
            "timestamp" => date('Y-m-d H:i:s'),
            "request_id" => REQUEST_ID,
            "level" => strtoupper($level),
            "user_id" => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            "ip" => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli',
            "message" => $message,
            "context" => $context
        );
        
        @file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
    }

    // Logger shortcuts
    function log_info($message, $context = []) { log_message('info', $message, $context); }
    function log_warn($message, $context = []) { log_message('warning', $message, $context); }
    function log_error($message, $context = []) { log_message('error', $message, $context); }
?>
