<?php

if (headers_sent()) {
    return;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://use.fontawesome.com https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://use.fontawesome.com https://fonts.gstatic.com; img-src 'self' data: blob: https://unpkg.com https://*.openstreetmap.org https://api.dicebear.com; connect-src 'self' https://nominatim.openstreetmap.org; frame-ancestors 'self';");

require_once __DIR__ . '/app_config.php';
if (is_production_env() && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
