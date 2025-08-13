<?php
// Try to fix routes by clearing cache
echo "=== ATTEMPTING ROUTE FIX ===\n\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Clear route cache if it exists
$routeCachePath = $app->getCachedRoutesPath();
if (file_exists($routeCachePath)) {
    echo "Route cache found at: $routeCachePath\n";
    echo "Attempting to delete...\n";
    
    if (is_writable($routeCachePath)) {
        unlink($routeCachePath);
        echo "✓ Route cache deleted!\n";
    } else {
        echo "✗ Cannot delete - not writable\n";
    }
} else {
    echo "No route cache found\n";
}

// Clear config cache if it exists
$configCachePath = $app->getCachedConfigPath();
if (file_exists($configCachePath)) {
    echo "\nConfig cache found at: $configCachePath\n";
    echo "Attempting to delete...\n";
    
    if (is_writable($configCachePath)) {
        unlink($configCachePath);
        echo "✓ Config cache deleted!\n";
    } else {
        echo "✗ Cannot delete - not writable\n";
    }
} else {
    echo "No config cache found\n";
}

// Now test if routes work
echo "\n=== TESTING ROUTES AFTER CACHE CLEAR ===\n";

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$router = $app->make('router');

// Load routes
$router->middleware('web')->group(function ($router) {
    require __DIR__ . '/../routes/web.php';
});

$routes = $router->getRoutes();
echo "Routes registered: " . count($routes) . "\n";

// Test root route
$request = Illuminate\Http\Request::create('/', 'GET');
$response = $kernel->handle($request);
echo "Root route status: " . $response->getStatusCode() . "\n";

if ($response->getStatusCode() == 200) {
    echo "✓ SUCCESS! Routes are working after cache clear!\n";
    echo "\nRECOMMENDATION: Add 'php artisan route:clear' to your deployment script\n";
} else {
    echo "✗ Still not working. There's a deeper issue.\n";
}