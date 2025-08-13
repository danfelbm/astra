<?php
// Debug why Laravel returns 404
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "=== DEBUGGING 404 ERROR ===\n\n";

// Check bootstrap files
echo "Bootstrap files check:\n";
echo "- bootstrap/app.php exists: " . (file_exists(__DIR__ . '/../bootstrap/app.php') ? 'YES' : 'NO') . "\n";
echo "- bootstrap/providers.php exists: " . (file_exists(__DIR__ . '/../bootstrap/providers.php') ? 'YES' : 'NO') . "\n";

// Check if providers are loaded
echo "\n=== PROVIDERS CHECK ===\n";
$providers = config('app.providers', []);
echo "Total providers: " . count($providers) . "\n";
echo "RouteServiceProvider in list: " . (in_array('App\Providers\RouteServiceProvider', $providers) ? 'YES' : 'NO') . "\n";

// Try to load routes manually
echo "\n=== MANUAL ROUTE LOADING ===\n";
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Force route loading
    $app->make('router')->group([], function ($router) {
        require base_path('routes/web.php');
    });
    
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    echo "Routes loaded manually: " . count($routes) . "\n";
    
    // Test root route
    $request = Illuminate\Http\Request::create('/', 'GET');
    try {
        $route = $router->match($request);
        echo "✓ Root route matched after manual loading\n";
        echo "  Action: " . $route->getActionName() . "\n";
    } catch (\Exception $e) {
        echo "✗ Root route still not found: " . $e->getMessage() . "\n";
    }
    
    // Test the response
    $response = $kernel->handle($request);
    echo "\nResponse after manual loading:\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Type: " . get_class($response) . "\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Check Inertia middleware
echo "\n=== INERTIA CHECK ===\n";
echo "HandleInertiaRequests middleware exists: " . (class_exists('App\Http\Middleware\HandleInertiaRequests') ? 'YES' : 'NO') . "\n";

// Check web middleware group
echo "\n=== MIDDLEWARE CHECK ===\n";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$middlewareGroups = $app->make('router')->getMiddlewareGroups();
echo "Web middleware group exists: " . (isset($middlewareGroups['web']) ? 'YES' : 'NO') . "\n";
if (isset($middlewareGroups['web'])) {
    echo "Web middleware count: " . count($middlewareGroups['web']) . "\n";
}