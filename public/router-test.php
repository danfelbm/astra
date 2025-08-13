<?php
// Test if Laravel routing works at all

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request to test routing
$request = Illuminate\Http\Request::create('/test-laravel', 'GET');
$response = $kernel->handle($request);

echo "Testing route /test-laravel:<br>";
echo "Status: " . $response->getStatusCode() . "<br>";
echo "Content: <pre>" . $response->getContent() . "</pre>";

// Test root route
$request2 = Illuminate\Http\Request::create('/', 'GET');
$response2 = $kernel->handle($request2);

echo "<hr>Testing route /:<br>";
echo "Status: " . $response2->getStatusCode() . "<br>";
echo "Headers: <pre>" . json_encode($response2->headers->all(), JSON_PRETTY_PRINT) . "</pre>";

$kernel->terminate($request, $response);