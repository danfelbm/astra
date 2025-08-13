<?php
// Minimal Laravel test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MINIMAL LARAVEL TEST ===\n\n";

// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Make kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test different URLs
$testUrls = [
    '/' => 'Root',
    '/plain-test' => 'Plain test',
    '/login' => 'Login',
    '/test-laravel' => 'Test Laravel',
    '/up' => 'Health check',
];

foreach ($testUrls as $url => $name) {
    $request = Illuminate\Http\Request::create($url, 'GET');
    $response = $kernel->handle($request);
    
    echo sprintf("%-20s: %d %s\n", 
        $name, 
        $response->getStatusCode(),
        $response->getStatusCode() == 200 ? '✓' : '✗'
    );
    
    if ($url == '/plain-test' && $response->getStatusCode() == 200) {
        echo "  Content: " . substr($response->getContent(), 0, 50) . "\n";
    }
}

// Check route cache
echo "\n=== CACHE STATUS ===\n";
$routeCachePath = $app->getCachedRoutesPath();
echo "Route cache path: $routeCachePath\n";
echo "Route cache exists: " . (file_exists($routeCachePath) ? 'YES' : 'NO') . "\n";

$configCachePath = $app->getCachedConfigPath();
echo "Config cache path: $configCachePath\n";
echo "Config cache exists: " . (file_exists($configCachePath) ? 'YES' : 'NO') . "\n";

// Environment
echo "\n=== ENVIRONMENT ===\n";
echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "APP_URL: " . config('app.url') . "\n";