<?php
function send_smtp_email($to, $subject, $body) {
    $smtp_host = getenv('SMTP_HOST') ?: "localhost";
    $smtp_port = getenv('SMTP_PORT') ?: 1025;

    // Connect to SMTP Server
    $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);
    if (!$socket) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }

    // Read initial greeting
    fgets($socket, 512);

    // Write commands and read responses
    $commands = [
        "EHLO localhost\r\n",
        "MAIL FROM: <noreply@pglife.com>\r\n",
        "RCPT TO: <" . $to . ">\r\n",
        "DATA\r\n"
    ];

    foreach ($commands as $cmd) {
        fwrite($socket, $cmd);
        fgets($socket, 512);
    }

    // Send headers and body
    $headers = "To: " . $to . "\r\n" .
               "From: PGLife <noreply@pglife.com>\r\n" .
               "Subject: " . $subject . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n" .
               "X-Mailer: PHP/" . phpversion() . "\r\n\r\n";

    fwrite($socket, $headers . $body . "\r\n.\r\n");
    fgets($socket, 512);

    fwrite($socket, "QUIT\r\n");
    fgets($socket, 512);

    fclose($socket);
    return true;
}
?>
