<?php

namespace Modules\Campanas\Providers;

use Illuminate\Support\ServiceProvider;

class CampanasServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Cargar rutas
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/guest.php');
        
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'campanas');
        
        // Cargar vistas (para componentes Blade si los hay)
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'campanas');
        
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/campanas.php' => config_path('campanas.php'),
        ], 'campanas-config');
        
        // Registrar comandos
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Campanas\Console\Commands\RefreshCampanaMetricsCommand::class,
                \Modules\Campanas\Console\Commands\RecuperarClicksCampana::class,
            ]);
        }
    }
    
    public function register()
    {
        // Registrar configuración
        $this->mergeConfigFrom(__DIR__.'/../Config/campanas.php', 'campanas');
    }
}