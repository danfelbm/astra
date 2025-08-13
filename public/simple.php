<?php
header('Content-Type: text/plain');
echo "Simple PHP test\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "\nNow trying to access Laravel route '/':\n\n";

// Try to manually handle the root route
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response type: " . get_class($response) . "\n";
if ($response->getStatusCode() == 200) {
    echo "\nLaravel is working! The route exists.\n";
} else {
    echo "\nLaravel returned: " . $response->getStatusCode() . "\n";
}