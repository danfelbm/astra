<?php
// Diagnostic test file for Laravel Cloud
echo json_encode([
    'status' => 'OK',
    'php_version' => PHP_VERSION,
    'laravel_path' => realpath(__DIR__ . '/../'),
    'public_path' => __DIR__,
    'index_exists' => file_exists(__DIR__ . '/index.php'),
    'env_exists' => file_exists(__DIR__ . '/../.env'),
    'vendor_exists' => file_exists(__DIR__ . '/../vendor'),
    'storage_writable' => is_writable(__DIR__ . '/../storage'),
    'build_exists' => file_exists(__DIR__ . '/build/manifest.json'),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
], JSON_PRETTY_PRINT);