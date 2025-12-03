<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\Api\ActividadController;
use Modules\Proyectos\Http\Controllers\Api\HitoApiController;
use Modules\Proyectos\Http\Controllers\Api\EntregableApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo Proyectos
|--------------------------------------------------------------------------
|
| Rutas API para el módulo de proyectos.
| Incluye endpoints para actividades y detalles de hitos y entregables.
| Todas las rutas requieren autenticación.
|
*/

Route::middleware(['auth'])->prefix('api/proyectos')->name('api.proyectos.')->group(function () {
    // Detalles completos de un hito (para modal)
    Route::get('/hitos/{hito}/detalles', [HitoApiController::class, 'detalles'])
        ->name('hitos.detalles')
        ->where('hito', '[0-9]+');

    // Actividades de un hito (incluye actividades de sus entregables)
    Route::get('/hitos/{hito}/actividades', [ActividadController::class, 'hitoActividades'])
        ->name('hitos.actividades')
        ->where('hito', '[0-9]+');

    // Detalles completos de un entregable (para modal)
    Route::get('/entregables/{entregable}/detalles', [EntregableApiController::class, 'detalles'])
        ->name('entregables.detalles')
        ->where('entregable', '[0-9]+');

    // Actividades de un entregable específico
    Route::get('/entregables/{entregable}/actividades', [ActividadController::class, 'entregableActividades'])
        ->name('entregables.actividades')
        ->where('entregable', '[0-9]+');

    // Actualizar campo individual de un hito (edición inline)
    // Acepta POST para file uploads con FormData (method spoofing con _method=PATCH)
    Route::match(['patch', 'post'], '/hitos/{hito}/campo', [HitoApiController::class, 'updateField'])
        ->name('hitos.update-field')
        ->where('hito', '[0-9]+');

    // Actualizar campo individual de un entregable (edición inline)
    // Acepta POST para file uploads con FormData (method spoofing con _method=PATCH)
    Route::match(['patch', 'post'], '/entregables/{entregable}/campo', [EntregableApiController::class, 'updateField'])
        ->name('entregables.update-field')
        ->where('entregable', '[0-9]+');
});
