<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\User\MisProyectosController;

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
});