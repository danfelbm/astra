<?php

namespace Modules\Proyectos\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ProyectosServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
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
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'proyectos');

        // Cargar vistas (para componentes Blade si los hay)
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'proyectos');

        // Publicar configuración
        $this->publishes([
            __DIR__.'/../Config/proyectos.php' => config_path('proyectos.php'),
        ], 'proyectos-config');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Registrar configuración
        $this->mergeConfigFrom(__DIR__.'/../Config/proyectos.php', 'proyectos');

        // Registrar servicios en el contenedor
        $this->app->singleton('proyectos.service', function ($app) {
            return new \Modules\Proyectos\Services\ProyectoService();
        });

        $this->app->singleton('campos-personalizados.service', function ($app) {
            return new \Modules\Proyectos\Services\CampoPersonalizadoService();
        });

        $this->app->singleton('evidencias.service', function ($app) {
            return new \Modules\Proyectos\Services\EvidenciaService(
                new \Modules\Proyectos\Repositories\EvidenciaRepository()
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'proyectos.service',
            'campos-personalizados.service',
            'evidencias.service'
        ];
    }
}