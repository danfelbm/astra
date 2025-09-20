<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\Guest\ProyectoPublicoController;

/*
|--------------------------------------------------------------------------
| Guest Routes del Módulo Proyectos
|--------------------------------------------------------------------------
|
| Rutas públicas accesibles sin autenticación.
| Vista pública de proyectos si está habilitada.
|
*/

Route::prefix('proyectos-publicos')->name('proyectos-publicos.')->group(function () {
    // Lista de proyectos públicos
    Route::get('/', [ProyectoPublicoController::class, 'index'])
        ->name('index');

    // Detalle de proyecto público
    Route::get('/{proyecto:id}', [ProyectoPublicoController::class, 'show'])
        ->name('show');
});