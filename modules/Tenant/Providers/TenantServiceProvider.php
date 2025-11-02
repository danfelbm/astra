<?php

namespace Modules\Tenant\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cargar rutas con middleware 'web'
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        });

        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'tenant');

        // Cargar vistas (si usara Blade)
        // $this->loadViewsFrom(__DIR__.'/../Resources/views', 'tenant');

        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('tenant.php'),
        ], 'config');
    }
}
