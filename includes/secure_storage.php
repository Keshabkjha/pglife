<?php

function secure_storage_root() {
    return dirname(__DIR__) . '/storage';
}

function secure_kyc_dir() {
    return secure_storage_root() . '/kyc';
}

function secure_receipts_dir() {
    return secure_storage_root() . '/receipts';
}

function ensure_secure_dir($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}

function secure_storage_db_path($category, $filename) {
    return 'storage/' . $category . '/' . $filename;
}

function resolve_stored_file_path($stored_path) {
    if (empty($stored_path)) {
        return null;
    }

    $stored_path = str_replace('\\', '/', $stored_path);

    if (strpos($stored_path, 'storage/') === 0) {
        $full = secure_storage_root() . '/' . substr($stored_path, strlen('storage/'));
        if (is_file($full)) {
            return $full;
        }
    }

    if (strpos($stored_path, 'img/kyc/') === 0 || strpos($stored_path, 'img/receipts/') === 0) {
        $legacy = dirname(__DIR__) . '/' . $stored_path;
        if (is_file($legacy)) {
            return $legacy;
        }
    }

    return null;
}

function delete_stored_file($stored_path) {
    $full = resolve_stored_file_path($stored_path);
    if ($full && is_file($full)) {
        @unlink($full);
    }
}

function mime_type_for_file($file_path) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    return $mime ?: 'application/octet-stream';
}
