<?php
// Test Laravel routes
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get the router
$router = $app->make('router');

echo "=== LARAVEL ROUTES TEST ===\n\n";

// Get all routes
$routes = $router->getRoutes();

echo "Total routes registered: " . count($routes) . "\n\n";

echo "First 10 routes:\n";
$count = 0;
foreach ($routes as $route) {
    if ($count++ >= 10) break;
    $methods = implode('|', $route->methods());
    $uri = $route->uri();
    $name = $route->getName() ?? 'unnamed';
    $action = $route->getActionName();
    
    echo sprintf("%-8s %-30s %-20s %s\n", $methods, $uri, $name, $action);
}

echo "\n=== CHECKING SPECIFIC ROUTES ===\n\n";

// Check if specific routes exist
$checkRoutes = ['/', 'login', 'dashboard', 'test-laravel', 'test-inertia'];
foreach ($checkRoutes as $checkRoute) {
    try {
        $route = $router->match(
            Illuminate\Http\Request::create($checkRoute, 'GET')
        );
        echo "✓ Route '$checkRoute' exists\n";
    } catch (\Exception $e) {
        echo "✗ Route '$checkRoute' NOT FOUND: " . $e->getMessage() . "\n";
    }
}

echo "\n=== ENVIRONMENT INFO ===\n";
echo "APP_ENV: " . env('APP_ENV') . "\n";
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "APP_URL: " . env('APP_URL') . "\n";

echo "\n=== WEB ROUTES FILE CHECK ===\n";
$webRoutesPath = base_path('routes/web.php');
echo "routes/web.php exists: " . (file_exists($webRoutesPath) ? 'YES' : 'NO') . "\n";
if (file_exists($webRoutesPath)) {
    echo "routes/web.php size: " . filesize($webRoutesPath) . " bytes\n";
    echo "routes/web.php first line: " . trim(fgets(fopen($webRoutesPath, 'r'))) . "\n";
}