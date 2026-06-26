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

function require_csrf_token() {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('success' => false, 'message' => 'Security verification failed (CSRF token mismatch).'));
        exit;
    }
}
