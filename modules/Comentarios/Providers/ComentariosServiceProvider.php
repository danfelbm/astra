<?php

namespace Modules\Comentarios\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ComentariosServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios del módulo.
     */
    public function register(): void
    {
        // Registrar configuración del módulo
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/comentarios.php',
            'comentarios'
        );
    }

    /**
     * Bootstrap de los servicios del módulo.
     */
    public function boot(): void
    {
        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // Cargar rutas con middleware web
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        });

        // Publicar configuración (opcional)
        $this->publishes([
            __DIR__ . '/../Config/comentarios.php' => config_path('comentarios.php'),
        ], 'comentarios-config');
    }
}
