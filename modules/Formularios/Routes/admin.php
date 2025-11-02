<?php

use Modules\Formularios\Http\Controllers\Admin\FormularioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Formularios Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de formularios dinámicos.
| CRUD completo + exportación de respuestas.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Formularios admin routes - CRUD + export
        Route::get('formularios', [FormularioController::class, 'index'])
            ->middleware('can:formularios.view')
            ->name('formularios.index');
        Route::get('formularios/create', [FormularioController::class, 'create'])
            ->middleware('can:formularios.create')
            ->name('formularios.create');
        Route::post('formularios', [FormularioController::class, 'store'])
            ->middleware('can:formularios.create')
            ->name('formularios.store');
        Route::get('formularios/{formulario}', [FormularioController::class, 'show'])
            ->middleware('can:formularios.view')
            ->name('formularios.show');
        Route::get('formularios/{formulario}/edit', [FormularioController::class, 'edit'])
            ->middleware('can:formularios.edit')
            ->name('formularios.edit');
        Route::put('formularios/{formulario}', [FormularioController::class, 'update'])
            ->middleware('can:formularios.edit')
            ->name('formularios.update');
        Route::delete('formularios/{formulario}', [FormularioController::class, 'destroy'])
            ->middleware('can:formularios.delete')
            ->name('formularios.destroy');
        Route::get('formularios/{formulario}/exportar', [FormularioController::class, 'exportarRespuestas'])
            ->middleware('can:formularios.export')
            ->name('formularios.exportar');

        // Permisos de formularios
        Route::get('formularios/{formulario}/permisos', [FormularioController::class, 'managePermissions'])
            ->middleware('can:formularios.manage_permissions')
            ->name('formularios.permisos');
        Route::put('formularios/{formulario}/permisos', [FormularioController::class, 'updatePermissions'])
            ->middleware('can:formularios.manage_permissions')
            ->name('formularios.permisos.update');
    });
