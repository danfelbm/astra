<?php
// Emergency debugging - why are routes not loading?
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== EMERGENCY ROUTE DEBUG ===\n\n";

// Step 1: Check if routes file exists
$routesPath = __DIR__ . '/../routes/web.php';
echo "1. Routes file check:\n";
echo "   Path: $routesPath\n";
echo "   Exists: " . (file_exists($routesPath) ? 'YES' : 'NO') . "\n";
echo "   Readable: " . (is_readable($routesPath) ? 'YES' : 'NO') . "\n";
if (file_exists($routesPath)) {
    echo "   Size: " . filesize($routesPath) . " bytes\n";
    echo "   First 100 chars: " . substr(file_get_contents($routesPath), 0, 100) . "...\n";
}

echo "\n2. Bootstrap check:\n";
require __DIR__ . '/../vendor/autoload.php';

// Try to load the app
try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "   App loaded: YES\n";
    echo "   Base path: " . $app->basePath() . "\n";
    
    // Check if routing was configured
    echo "\n3. Routing configuration:\n";
    
    // Get the kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "   Kernel created: YES\n";
    
    // Force bootstrap
    $app->bootstrapWith([
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ]);
    echo "   App bootstrapped: YES\n";
    
    // Get router
    $router = $app->make('router');
    echo "   Router instance: " . get_class($router) . "\n";
    
    // Check routes before loading
    $routesBefore = count($router->getRoutes());
    echo "   Routes before manual load: $routesBefore\n";
    
    // Try to manually load routes
    echo "\n4. Manual route loading:\n";
    try {
        // Load web routes directly
        $router->middleware('web')->group(function ($router) {
            require __DIR__ . '/../routes/web.php';
        });
        
        $routesAfter = count($router->getRoutes());
        echo "   Routes after manual load: $routesAfter\n";
        
        if ($routesAfter > 0) {
            echo "   ✓ Routes loaded successfully!\n";
            echo "\n   First 5 routes:\n";
            $count = 0;
            foreach ($router->getRoutes() as $route) {
                if ($count++ >= 5) break;
                $methods = implode('|', $route->methods());
                $uri = $route->uri();
                echo "     $methods $uri\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "   ERROR loading routes: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . "\n";
        echo "   Line: " . $e->getLine() . "\n";
    }
    
    // Check for route cache
    echo "\n5. Cache check:\n";
    $routeCache = $app->getCachedRoutesPath();
    echo "   Route cache path: $routeCache\n";
    echo "   Route cache exists: " . (file_exists($routeCache) ? 'YES - THIS MIGHT BE THE PROBLEM!' : 'NO') . "\n";
    
    if (file_exists($routeCache)) {
        echo "   !!! ROUTE CACHE FOUND - Routes are loaded from cache, not from files!\n";
        echo "   Cache modified: " . date('Y-m-d H:i:s', filemtime($routeCache)) . "\n";
    }
    
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n6. Environment variables:\n";
echo "   PWD: " . ($_SERVER['PWD'] ?? 'not set') . "\n";
echo "   LARAVEL_CLOUD: " . ($_SERVER['LARAVEL_CLOUD'] ?? 'not set') . "\n";
echo "   APP_ENV: " . ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'not set') . "\n";