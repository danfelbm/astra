<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\Guest\ProyectoPublicoController;

/*
|--------------------------------------------------------------------------
| Guest Routes del Módulo Proyectos
|--------------------------------------------------------------------------
|
| Rutas públicas para consulta de proyectos.
| No requieren autenticación.
|
*/

// MÓDULO PROYECTOS - Rutas públicas
Route::prefix('proyectos-publicos')->name('proyectos-publicos.')->group(function () {
    // Lista de proyectos públicos
    Route::get('/', [ProyectoPublicoController::class, 'index'])
        ->name('index');

    // Detalle de proyecto público
    Route::get('/{proyecto}', [ProyectoPublicoController::class, 'show'])
        ->name('show');
});