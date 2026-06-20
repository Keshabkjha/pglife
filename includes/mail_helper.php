<?php
function send_smtp_email($to, $subject, $body) {
    $smtp_host = getenv('SMTP_HOST') ?: "localhost";
    $smtp_port = (int)(getenv('SMTP_PORT') ?: 1025);
    $smtp_user = getenv('SMTP_USER') ?: "";
    $smtp_password = getenv('SMTP_PASSWORD') ?: "";
    $smtp_from_email = getenv('SMTP_FROM_EMAIL') ?: $smtp_user;
    $smtp_from_name = getenv('SMTP_FROM_NAME') ?: "PG Life";

    // Determine connection prefix (ssl:// for port 465)
    $host_prefix = "";
    if ($smtp_port === 465) {
        $host_prefix = "ssl://";
    }

    $socket = @fsockopen($host_prefix . $smtp_host, $smtp_port, $errno, $errstr, 8);
    if (!$socket) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }

    // Helper to read SMTP responses and verify expected code
    $readResponse = function($socket, $expected_code) {
        $response = "";
        while ($str = fgets($socket, 512)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if ($code !== $expected_code) {
            error_log("SMTP unexpected response: " . trim($response) . " (Expected: $expected_code)");
            return false;
        }
        return true;
    };

    // Read initial greeting (220)
    if (!$readResponse($socket, 220)) { fclose($socket); return false; }

    // EHLO
    fwrite($socket, "EHLO localhost\r\n");
    if (!$readResponse($socket, 250)) { fclose($socket); return false; }

    // STARTTLS support for port 587
    if ($smtp_port === 587) {
        fwrite($socket, "STARTTLS\r\n");
        if (!$readResponse($socket, 220)) { fclose($socket); return false; }
        // Enable encryption on existing socket
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("SMTP STARTTLS handshake failed.");
            fclose($socket);
            return false;
        }
        // EHLO again after TLS handshake
        fwrite($socket, "EHLO localhost\r\n");
        if (!$readResponse($socket, 250)) { fclose($socket); return false; }
    }

    // Authenticate if user credentials are provided
    if (!empty($smtp_user) && !empty($smtp_password)) {
        fwrite($socket, "AUTH LOGIN\r\n");
        if (!$readResponse($socket, 334)) { fclose($socket); return false; }

        fwrite($socket, base64_encode($smtp_user) . "\r\n");
        if (!$readResponse($socket, 334)) { fclose($socket); return false; }

        fwrite($socket, base64_encode($smtp_password) . "\r\n");
        if (!$readResponse($socket, 235)) { fclose($socket); return false; }
    }

    // MAIL FROM (Envelope Sender)
    $envelope_from = !empty($smtp_user) ? $smtp_user : "noreply@pglife.com";
    fwrite($socket, "MAIL FROM: <" . $envelope_from . ">\r\n");
    if (!$readResponse($socket, 250)) { fclose($socket); return false; }

    // RCPT TO
    fwrite($socket, "RCPT TO: <" . $to . ">\r\n");
    if (!$readResponse($socket, 250)) { fclose($socket); return false; }

    // DATA
    fwrite($socket, "DATA\r\n");
    if (!$readResponse($socket, 354)) { fclose($socket); return false; }

    // Headers and Body (must end with \r\n.\r\n)
    $from_header = !empty($smtp_from_email) ? $smtp_from_email : $envelope_from;
    $headers = "To: " . $to . "\r\n" .
               "From: " . $smtp_from_name . " <" . $from_header . ">\r\n" .
               "Reply-To: " . $from_header . "\r\n" .
               "Subject: " . $subject . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n" .
               "X-Mailer: PHP/" . phpversion() . "\r\n\r\n";

    fwrite($socket, $headers . $body . "\r\n.\r\n");
    if (!$readResponse($socket, 250)) { fclose($socket); return false; }

    // QUIT
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}
?>
