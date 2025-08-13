<?php
// Simple diagnostic without Laravel bootstrap
$diagnostics = [
    'server' => [
        'php_version' => PHP_VERSION,
        'sapi_name' => php_sapi_name(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'not set',
    ],
    'paths' => [
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'php_self' => $_SERVER['PHP_SELF'] ?? 'not set',
    ],
    'files' => [
        'index.php' => file_exists(__DIR__ . '/index.php'),
        '.htaccess' => file_exists(__DIR__ . '/.htaccess'),
        'web.config' => file_exists(__DIR__ . '/web.config'),
        '../.env' => file_exists(__DIR__ . '/../.env'),
        '../vendor' => is_dir(__DIR__ . '/../vendor'),
        'build/manifest.json' => file_exists(__DIR__ . '/build/manifest.json'),
    ],
    'nginx_or_apache' => [
        'mod_rewrite' => function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : 'N/A',
        'htaccess_readable' => is_readable(__DIR__ . '/.htaccess'),
    ],
    'all_server_vars' => $_SERVER,
];

header('Content-Type: application/json');
echo json_encode($diagnostics, JSON_PRETTY_PRINT);