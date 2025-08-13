<?php

use Illuminate\Support\Facades\Route;

// Test route for debugging Laravel Cloud
Route::get('/test-laravel', function () {
    return response()->json([
        'status' => 'Laravel is working',
        'env' => app()->environment(),
        'debug' => config('app.debug'),
        'url' => config('app.url'),
        'stateful_domains' => config('sanctum.stateful'),
        'session_domain' => config('session.domain'),
        'routes_loaded' => true,
        'inertia_root_view' => config('inertia.testing.ensure_pages_exist', null),
    ]);
});