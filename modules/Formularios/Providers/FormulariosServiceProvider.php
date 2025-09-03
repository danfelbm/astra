<?php

namespace Modules\Formularios\Providers;

use Illuminate\Support\ServiceProvider;

class FormulariosServiceProvider extends ServiceProvider
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
        // Cargar rutas
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/user.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/guest.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'formularios');
        
        // Cargar vistas (si usara Blade)
        // $this->loadViewsFrom(__DIR__.'/../Resources/views', 'formularios');
        
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('formularios.php'),
        ], 'config');
    }
}
