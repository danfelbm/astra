<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Cargar rutas
        $this->loadRoutesFrom(__DIR__.'/../Routes/auth.php');
        
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Cargar traducciones
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'core');
        
        // Cargar vistas
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'core');
        
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/core.php' => config_path('core.php'),
        ], 'core-config');
        
        // Registrar comandos
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Core\Console\Commands\AssignUserRoleToAdmins::class,
                \Modules\Core\Console\Commands\NormalizeExistingNames::class,
                \Modules\Core\Console\Commands\TestRateLimitingStress::class,
                \Modules\Core\Console\Commands\GenerateServerKeys::class,
                \Modules\Core\Console\Commands\TestWhatsAppModeCommand::class,
                \Modules\Core\Console\Commands\TestAuditLog::class,
                \Modules\Core\Console\Commands\CreateFakeUsers::class,
            ]);
        }
    }
    
    public function register()
    {
        // Registrar servicios del Core
        $this->mergeConfigFrom(__DIR__.'/../Config/core.php', 'core');
    }
}
