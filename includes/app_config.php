<?php

function app_env() {
    $env = getenv('APP_ENV');
    if ($env === false || $env === '') {
        return 'development';
    }
    return strtolower(trim($env));
}

function is_production_env() {
    return app_env() === 'production';
}

function feature_mock_kyc_enabled() {
    $flag = getenv('FEATURE_MOCK_KYC');
    if ($flag !== false && $flag !== '') {
        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }
    return !is_production_env();
}

function request_is_https() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) : '';
    return $forwarded === 'https';
}

function site_base_url() {
    static $url = null;
    if ($url !== null) {
        return $url;
    }

    $configured = getenv('APP_URL');
    if (is_string($configured) && trim($configured) !== '') {
        $url = rtrim(trim($configured), '/');
        return $url;
    }

    $host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : '';
    if ($host !== '') {
        $url = (request_is_https() ? 'https' : 'http') . '://' . $host;
        return $url;
    }

    $url = 'https://www.pglife.in';
    return $url;
}

function canonical_city_name($input) {
    $key = strtolower(trim((string)$input));
    $key = preg_replace('/\s+/', ' ', $key);
    $aliases = array(
        'delhi' => 'Delhi',
        'new delhi' => 'Delhi',
        'mumbai' => 'Mumbai',
        'bombay' => 'Mumbai',
        'bengaluru' => 'Bengaluru',
        'bangalore' => 'Bengaluru',
        'bangaluru' => 'Bengaluru',
        'hyderabad' => 'Hyderabad',
        'kolkata' => 'Kolkata',
        'calcutta' => 'Kolkata',
        'chennai' => 'Chennai',
        'madras' => 'Chennai',
        'pune' => 'Pune',
        'ahmedabad' => 'Ahmedabad',
        'amdavad' => 'Ahmedabad',
        'jaipur' => 'Jaipur',
        'noida' => 'Noida',
        'gurgaon' => 'Gurgaon',
        'gurugram' => 'Gurgaon',
    );
    return isset($aliases[$key]) ? $aliases[$key] : null;
}

function city_image_file($city_name) {
    $files = array(
        'Bengaluru' => 'bangalore.png',
    );
    if (isset($files[$city_name])) {
        return $files[$city_name];
    }
    return strtolower($city_name) . '.png';
}

function require_csrf_token() {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('success' => false, 'message' => 'Security verification failed (CSRF token mismatch).'));
        exit;
    }
}
