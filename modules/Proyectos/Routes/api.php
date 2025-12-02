<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\Api\ActividadController;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo Proyectos
|--------------------------------------------------------------------------
|
| Rutas API para el módulo de proyectos.
| Incluye endpoints para actividades de hitos y entregables.
| Todas las rutas requieren autenticación.
|
*/

Route::middleware(['auth'])->prefix('api/proyectos')->name('api.proyectos.')->group(function () {
    // Actividades de un hito (incluye actividades de sus entregables)
    Route::get('/hitos/{hito}/actividades', [ActividadController::class, 'hitoActividades'])
        ->name('hitos.actividades')
        ->where('hito', '[0-9]+');

    // Actividades de un entregable específico
    Route::get('/entregables/{entregable}/actividades', [ActividadController::class, 'entregableActividades'])
        ->name('entregables.actividades')
        ->where('entregable', '[0-9]+');
});
