<?php

namespace Modules\Elecciones\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class EleccionesServiceProvider extends ServiceProvider
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
            $this->loadRoutesFrom(__DIR__.'/../Routes/user.php');
            $this->loadRoutesFrom(__DIR__.'/../Routes/guest.php');
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
        
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'elecciones');
        
        // Cargar vistas (si usara Blade)
        // $this->loadViewsFrom(__DIR__.'/../Resources/views', 'elecciones');
        
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('elecciones.php'),
        ], 'config');
    
        // Cargar comandos
        if ($this->app->runningInConsole()) {
        }
    }
}
