<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\User\MisProyectosController;
use Modules\Proyectos\Http\Controllers\User\MisContratosController;
use Modules\Proyectos\Http\Controllers\User\MisObligacionesController;

/*
|--------------------------------------------------------------------------
| User Routes del Módulo Proyectos
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados regulares.
| Acceso a proyectos propios o asignados.
|
*/

Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('miembro.')->group(function () {
    // Rutas para mis proyectos
    Route::prefix('mis-proyectos')->name('mis-proyectos.')->group(function () {
        Route::get('/', [MisProyectosController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.view_own');

        Route::get('/create', [MisProyectosController::class, 'create'])
            ->name('create')
            ->middleware('can:proyectos.create_own');

        Route::post('/', [MisProyectosController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.create_own');

        Route::get('/{proyecto}', [MisProyectosController::class, 'show'])
            ->name('show')
            ->middleware('can:proyectos.view_own');

        Route::get('/{proyecto}/edit', [MisProyectosController::class, 'edit'])
            ->name('edit')
            ->middleware('can:proyectos.edit_own');

        Route::put('/{proyecto}', [MisProyectosController::class, 'update'])
            ->name('update')
            ->middleware('can:proyectos.edit_own');

        // Actualizar estado del proyecto
        Route::post('/{proyecto}/cambiar-estado', [MisProyectosController::class, 'cambiarEstado'])
            ->name('cambiar-estado')
            ->middleware('can:proyectos.edit_own');

        // Ver/editar campos personalizados
        Route::get('/{proyecto}/campos-personalizados', [MisProyectosController::class, 'camposPersonalizados'])
            ->name('campos-personalizados')
            ->middleware('can:proyectos.view_own');

        Route::post('/{proyecto}/campos-personalizados', [MisProyectosController::class, 'guardarCamposPersonalizados'])
            ->name('guardar-campos-personalizados')
            ->middleware('can:proyectos.edit_own');
    });

    // Rutas para mis contratos
    Route::prefix('mis-contratos')->name('mis-contratos.')->group(function () {
        Route::get('/', [MisContratosController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.view_own');

        Route::get('/proximos-vencer', [MisContratosController::class, 'proximosVencer'])
            ->name('proximos-vencer')
            ->middleware('can:contratos.view_own');

        Route::get('/vencidos', [MisContratosController::class, 'vencidos'])
            ->name('vencidos')
            ->middleware('can:contratos.view_own');

        Route::get('/{contrato}', [MisContratosController::class, 'show'])
            ->name('show')
            ->middleware('can:contratos.view_own');

        Route::get('/{contrato}/descargar-pdf', [MisContratosController::class, 'descargarPDF'])
            ->name('descargar-pdf')
            ->middleware('can:contratos.view_own');
    });

    // Rutas para Mis Hitos
    Route::prefix('mis-hitos')->name('mis-hitos.')->group(function () {
        Route::get('/', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'index'])
            ->name('index')
            ->middleware('can:hitos.view_own');

        Route::get('/timeline', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'timeline'])
            ->name('timeline')
            ->middleware('can:hitos.view_own');

        Route::get('/{hito}', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'show'])
            ->name('show')
            ->middleware('can:hitos.view_own');

        Route::post('/{hito}/entregables/{entregable}/completar', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'completarEntregable'])
            ->name('completar-entregable')
            ->middleware('can:hitos.complete_own');

        Route::put('/{hito}/entregables/{entregable}/estado', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'actualizarEstadoEntregable'])
            ->name('actualizar-estado-entregable')
            ->middleware('can:hitos.update_progress');
    });

    // Rutas para Mis Obligaciones
    Route::prefix('mis-obligaciones')->name('mis-obligaciones.')->group(function () {
        Route::get('/', [MisObligacionesController::class, 'index'])
            ->name('index')
            ->middleware('can:obligaciones.view_own');

        Route::get('/calendario', [MisObligacionesController::class, 'calendario'])
            ->name('calendario')
            ->middleware('can:obligaciones.view_own');

        Route::get('/{obligacion}', [MisObligacionesController::class, 'show'])
            ->name('show')
            ->middleware('can:obligaciones.view_own');

        Route::post('/{obligacion}/completar', [MisObligacionesController::class, 'completar'])
            ->name('completar')
            ->middleware('can:obligaciones.complete_own');

        Route::put('/{obligacion}/progreso', [MisObligacionesController::class, 'actualizarProgreso'])
            ->name('actualizar-progreso')
            ->middleware('can:obligaciones.view_own');
    });
});