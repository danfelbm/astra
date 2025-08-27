<?php

use App\Http\Middleware\Core\HandleInertiaRequests;
use App\Http\Middleware\Core\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middlewares = [];
        
        // Solo cargar TenantMiddleware si está habilitado
        if (env('ENABLE_TENANT_MIDDLEWARE', true)) {
            //$middlewares[] = TenantMiddleware::class;
        }
        
        // Siempre cargar estos
        $middlewares[] = HandleInertiaRequests::class;
        $middlewares[] = AddLinkHeadersForPreloadedAssets::class;
        
        $middleware->web(append: $middlewares);

        $middleware->alias([
            'admin' => \App\Http\Middleware\Core\AdminMiddleware::class,
            'user' => \App\Http\Middleware\Core\UserMiddleware::class,
            'tenant' => TenantMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
