<?php

namespace Modules\Geografico\Providers;

use Illuminate\Support\ServiceProvider;

class GeograficoServiceProvider extends ServiceProvider
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
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'geografico');
        
        // Cargar vistas (si usara Blade)
        // $this->loadViewsFrom(__DIR__.'/../Resources/views', 'geografico');
        
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('geografico.php'),
        ], 'config');
    
        // Cargar comandos
        if ($this->app->runningInConsole()) {
        }
    }
}
