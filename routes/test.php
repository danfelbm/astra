<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
        'build_manifest_exists' => file_exists(public_path('build/manifest.json')),
        'app_blade_exists' => file_exists(resource_path('views/app.blade.php')),
        'welcome_vue_exists' => file_exists(resource_path('js/pages/Welcome.vue')),
    ]);
});

// Test Inertia rendering
Route::get('/test-inertia', function () {
    return Inertia::render('Welcome');
});