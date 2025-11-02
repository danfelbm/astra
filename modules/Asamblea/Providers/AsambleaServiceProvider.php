<?php

namespace Modules\Asamblea\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AsambleaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Cargar rutas con middleware 'web'
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
            $this->loadRoutesFrom(__DIR__.'/../Routes/user.php');
            $this->loadRoutesFrom(__DIR__.'/../Routes/guest.php');
        });
        
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'asamblea');
        
        // Cargar vistas (para componentes Blade si los hay)
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'asamblea');
        
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/asamblea.php' => config_path('asamblea.php'),
        ], 'asamblea-config');
    }
    
    public function register()
    {
        // Registrar configuración
        $this->mergeConfigFrom(__DIR__.'/../Config/asamblea.php', 'asamblea');
    }
}
