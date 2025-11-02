<?php

namespace Modules\Imports\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ImportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Cargar rutas con middleware 'web'
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        });

        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'imports');

        // Cargar vistas
        // $this->loadViewsFrom(__DIR__.'/../Resources/views', 'imports');

        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('imports.php'),
        ], 'config');
    }
}
