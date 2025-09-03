<?php

use Modules\Core\Http\Middleware\HandleInertiaRequests;
use Modules\Core\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

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
            'admin' => \Modules\Core\Http\Middleware\AdminMiddleware::class,
            'user' => \Modules\Core\Http\Middleware\UserMiddleware::class,
            'tenant' => TenantMiddleware::class,
        ]);
        
        // Configurar Trusted Proxies para capturar IP real detrás del load balancer
        $trustedProxies = env('TRUSTED_PROXIES', '*');
        $trustedHeaders = env('TRUSTED_HEADERS', 'x-forwarded-all');
        
        // Mapear headers según configuración
        $headers = match($trustedHeaders) {
            'x-forwarded-all' => Request::HEADER_X_FORWARDED_FOR |
                                 Request::HEADER_X_FORWARDED_HOST |
                                 Request::HEADER_X_FORWARDED_PORT |
                                 Request::HEADER_X_FORWARDED_PROTO |
                                 Request::HEADER_X_FORWARDED_AWS_ELB,
            'x-forwarded-for' => Request::HEADER_X_FORWARDED_FOR,
            'x-forwarded-aws-elb' => Request::HEADER_X_FORWARDED_AWS_ELB,
            default => Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO,
        };
        
        // Aplicar configuración de proxies confiables
        if ($trustedProxies !== 'none') {
            $proxies = $trustedProxies === '*' ? '*' : explode(',', $trustedProxies);
            $middleware->trustProxies(
                at: $proxies,
                headers: $headers
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
