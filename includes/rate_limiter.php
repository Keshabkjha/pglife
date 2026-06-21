<?php
// rate_limiter.php - DB-backed rate limiting for OTP and sensitive endpoints

// Returns ['allowed' => bool, 'remaining' => int, 'retry_after' => int (seconds)]
function check_rate_limit($conn, $action, $ip, $max_attempts = 5, $window_secs = 900) {
    $now = time();
    $window_start = date('Y-m-d H:i:s', $now - $window_secs);

    // Purge expired rows for this IP+action (cleanup)
    $sql_purge = "DELETE FROM rate_limits WHERE ip_address = ? AND action = ? AND last_attempt < ?";
    $stmt_purge = mysqli_prepare($conn, $sql_purge);
    if ($stmt_purge) {
        mysqli_stmt_bind_param($stmt_purge, "sss", $ip, $action, $window_start);
        mysqli_stmt_execute($stmt_purge);
        mysqli_stmt_close($stmt_purge);
    }

    // Count attempts within the window
    $sql_count = "SELECT attempts, first_attempt FROM rate_limits WHERE ip_address = ? AND action = ? AND last_attempt >= ?";
    $stmt_count = mysqli_prepare($conn, $sql_count);
    $attempts = 0;
    $first_attempt_time = 0;

    if ($stmt_count) {
        mysqli_stmt_bind_param($stmt_count, "sss", $ip, $action, $window_start);
        mysqli_stmt_execute($stmt_count);
        $res = mysqli_stmt_get_result($stmt_count);
        if ($row = mysqli_fetch_assoc($res)) {
            $attempts = (int)$row['attempts'];
            $first_attempt_time = strtotime($row['first_attempt']);
        }
        mysqli_stmt_close($stmt_count);
    }

    if ($attempts >= $max_attempts) {
        $retry_after = ($first_attempt_time + $window_secs) - $now;
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => max(1, $retry_after)
        ];
    }

    // Increment the counter (upsert)
    $sql_upsert = "INSERT INTO rate_limits (ip_address, action, attempts) VALUES (?, ?, 1)
                   ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()";
    $stmt_upsert = mysqli_prepare($conn, $sql_upsert);
    if ($stmt_upsert) {
        mysqli_stmt_bind_param($stmt_upsert, "ss", $ip, $action);
        mysqli_stmt_execute($stmt_upsert);
        mysqli_stmt_close($stmt_upsert);
    }

    return [
        'allowed' => true,
        'remaining' => $max_attempts - $attempts - 1,
        'retry_after' => 0
    ];
}

// Grab client IP (handles reverse proxy setups)
function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Turn seconds into "X minutes" / "X seconds" for display
function format_retry_after($seconds) {
    if ($seconds <= 60) {
        return $seconds . " second" . ($seconds !== 1 ? "s" : "");
    }
    $minutes = (int)ceil($seconds / 60);
    return $minutes . " minute" . ($minutes !== 1 ? "s" : "");
}
?>
