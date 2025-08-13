<?php
// Diagnostic test file for Laravel Cloud

// Try to bootstrap Laravel
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    $request = \Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    
    $diagnostics = [
        'status' => 'Laravel Bootstrap OK',
        'php_version' => PHP_VERSION,
        'laravel_path' => realpath(__DIR__ . '/../'),
        'public_path' => __DIR__,
        'index_exists' => file_exists(__DIR__ . '/index.php'),
        'env_exists' => file_exists(__DIR__ . '/../.env'),
        'vendor_exists' => file_exists(__DIR__ . '/../vendor'),
        'storage_writable' => is_writable(__DIR__ . '/../storage'),
        'build_exists' => file_exists(__DIR__ . '/build/manifest.json'),
        'manifest_exists' => file_exists(__DIR__ . '/build/manifest.json'),
        'app_js_exists' => file_exists(__DIR__ . '/build/assets/app-CirNizQj.js'),
        'app_css_exists' => file_exists(__DIR__ . '/build/assets/app-DkfPGrQc.css'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
        'app_url' => config('app.url'),
        'app_env' => config('app.env'),
        'routes_cached' => file_exists(__DIR__ . '/../bootstrap/cache/routes-v7.php'),
        'config_cached' => file_exists(__DIR__ . '/../bootstrap/cache/config.php'),
    ];
    
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}