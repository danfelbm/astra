<?php
// Ultra minimal test - bypass everything
echo "=== ULTRA MINIMAL TEST ===\n\n";

// Just try to render something with Inertia directly
require __DIR__ . '/../vendor/autoload.php';

// Create a minimal Laravel app
$app = new Illuminate\Foundation\Application(
    realpath(__DIR__ . '/../')
);

// Bind essential services
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Illuminate\Foundation\Exceptions\Handler::class
);

// Create the app
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test if we can access config
echo "1. Config test:\n";
echo "   APP_NAME: " . config('app.name', 'not set') . "\n";
echo "   APP_ENV: " . config('app.env', 'not set') . "\n";

// Test if we can create a response
echo "\n2. Response test:\n";
try {
    $response = response('Hello from Laravel', 200);
    echo "   ✓ Can create response\n";
} catch (\Exception $e) {
    echo "   ✗ Cannot create response: " . $e->getMessage() . "\n";
}

// Test if Inertia works
echo "\n3. Inertia test:\n";
try {
    if (class_exists('Inertia\Inertia')) {
        echo "   ✓ Inertia class exists\n";
        
        // Try to render
        $page = Inertia\Inertia::render('Welcome');
        echo "   ✓ Can create Inertia response\n";
        echo "   Response type: " . get_class($page) . "\n";
    } else {
        echo "   ✗ Inertia class not found\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Inertia error: " . $e->getMessage() . "\n";
}

// Check if routes/web.php has syntax errors
echo "\n4. Routes file syntax check:\n";
try {
    $routesPath = __DIR__ . '/../routes/web.php';
    
    // Use token_get_all to check syntax
    $code = file_get_contents($routesPath);
    $tokens = @token_get_all($code);
    
    if ($tokens === false) {
        echo "   ✗ Syntax error in routes/web.php!\n";
    } else {
        echo "   ✓ Routes file syntax OK\n";
        
        // Try to include it
        $router = new \Illuminate\Routing\Router(
            new \Illuminate\Events\Dispatcher(),
            $app
        );
        
        // Temporarily replace Route facade
        $originalRouter = null;
        if (class_exists('Route')) {
            $originalRouter = \Route::getFacadeRoot();
        }
        \Route::swap($router);
        
        // Include routes
        include $routesPath;
        
        echo "   ✓ Routes file loaded without errors\n";
        echo "   Routes registered: " . count($router->getRoutes()) . "\n";
        
        // Restore original router
        if ($originalRouter) {
            \Route::swap($originalRouter);
        }
    }
} catch (\Exception $e) {
    echo "   ✗ Error loading routes: " . $e->getMessage() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}