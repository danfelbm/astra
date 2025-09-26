<?php

use Illuminate\Support\Facades\Route;
use Modules\Proyectos\Http\Controllers\Admin\ProyectoController;
use Modules\Proyectos\Http\Controllers\Admin\CampoPersonalizadoController;
use Modules\Proyectos\Http\Controllers\Admin\ContratoController;
// use Modules\Proyectos\Http\Controllers\Admin\CampoPersonalizadoContratoController; // Modelo unificado

/*
|--------------------------------------------------------------------------
| Admin Routes del Módulo Proyectos
|--------------------------------------------------------------------------
|
| Rutas administrativas que requieren permisos de administración.
| Solo accesibles para usuarios con roles administrativos.
|
*/

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Rutas para gestión de proyectos
    Route::prefix('proyectos')->name('proyectos.')->group(function () {
        Route::get('/', [ProyectoController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.view');

        Route::get('/create', [ProyectoController::class, 'create'])
            ->name('create')
            ->middleware('can:proyectos.create');

        Route::post('/', [ProyectoController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.create');

        Route::get('/{proyecto}', [ProyectoController::class, 'show'])
            ->name('show')
            ->middleware('can:proyectos.view');

        Route::get('/{proyecto}/edit', [ProyectoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:proyectos.edit');

        Route::put('/{proyecto}', [ProyectoController::class, 'update'])
            ->name('update')
            ->middleware('can:proyectos.edit');

        Route::delete('/{proyecto}', [ProyectoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:proyectos.delete');

        // Rutas adicionales
        Route::post('/{proyecto}/toggle-status', [ProyectoController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('can:proyectos.edit');

        Route::post('/{proyecto}/asignar-responsable', [ProyectoController::class, 'asignarResponsable'])
            ->name('asignar-responsable')
            ->middleware('can:proyectos.edit');
    });

    // Rutas para gestión de campos personalizados
    Route::prefix('campos-personalizados')->name('campos-personalizados.')->group(function () {
        Route::get('/', [CampoPersonalizadoController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.manage_fields');

        Route::get('/create', [CampoPersonalizadoController::class, 'create'])
            ->name('create')
            ->middleware('can:proyectos.manage_fields');

        Route::post('/', [CampoPersonalizadoController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.manage_fields');

        Route::get('/{campo}', [CampoPersonalizadoController::class, 'show'])
            ->name('show')
            ->middleware('can:proyectos.manage_fields');

        Route::get('/{campo}/edit', [CampoPersonalizadoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:proyectos.manage_fields');

        Route::put('/{campo}', [CampoPersonalizadoController::class, 'update'])
            ->name('update')
            ->middleware('can:proyectos.manage_fields');

        Route::delete('/{campo}', [CampoPersonalizadoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:proyectos.manage_fields');

        Route::post('/reordenar', [CampoPersonalizadoController::class, 'reordenar'])
            ->name('reordenar')
            ->middleware('can:proyectos.manage_fields');
    });

    // Rutas para gestión de contratos
    Route::prefix('contratos')->name('contratos.')->group(function () {
        Route::get('/', [ContratoController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.view');

        Route::get('/create', [ContratoController::class, 'create'])
            ->name('create')
            ->middleware('can:contratos.create');

        Route::post('/', [ContratoController::class, 'store'])
            ->name('store')
            ->middleware('can:contratos.create');

        Route::get('/proximos-vencer', [ContratoController::class, 'proximosVencer'])
            ->name('proximos-vencer')
            ->middleware('can:contratos.view');

        Route::get('/vencidos', [ContratoController::class, 'vencidos'])
            ->name('vencidos')
            ->middleware('can:contratos.view');

        Route::get('/{contrato}', [ContratoController::class, 'show'])
            ->name('show')
            ->middleware('can:contratos.view');

        Route::get('/{contrato}/edit', [ContratoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:contratos.edit');

        Route::put('/{contrato}', [ContratoController::class, 'update'])
            ->name('update')
            ->middleware('can:contratos.edit');

        Route::delete('/{contrato}', [ContratoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:contratos.delete');

        // Acciones adicionales
        Route::post('/{contrato}/cambiar-estado', [ContratoController::class, 'cambiarEstado'])
            ->name('cambiar-estado')
            ->middleware('can:contratos.change_status');

        Route::post('/{contrato}/duplicar', [ContratoController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:contratos.create');
    });

    // Rutas para contratos dentro del contexto de un proyecto
    Route::prefix('proyectos/{proyecto}/contratos')->name('proyectos.contratos.')->group(function () {
        Route::get('/', [ContratoController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.view');

        Route::get('/create', [ContratoController::class, 'create'])
            ->name('create')
            ->middleware('can:contratos.create');
    });

    // Ruta para búsqueda de usuarios (DEBE estar ANTES del grupo para evitar conflictos con route model binding)
    Route::get('/proyectos-search-users', [ProyectoController::class, 'searchUsers'])
        ->name('proyectos.search-users')
        ->middleware('can:proyectos.view');

    // Rutas para hitos dentro del contexto de un proyecto
    Route::prefix('proyectos/{proyecto}/hitos')->name('proyectos.hitos.')->group(function () {
        Route::get('/', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'index'])
            ->name('index')
            ->middleware('can:hitos.view');

        Route::get('/create', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'create'])
            ->name('create')
            ->middleware('can:hitos.create');

        Route::post('/', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'store'])
            ->name('store')
            ->middleware('can:hitos.create');

        Route::get('/{hito}', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'show'])
            ->name('show')
            ->middleware('can:hitos.view');

        Route::get('/{hito}/edit', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:hitos.edit');

        Route::put('/{hito}', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'update'])
            ->name('update')
            ->middleware('can:hitos.edit');

        Route::delete('/{hito}', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:hitos.delete');

        Route::post('/{hito}/duplicar', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:hitos.create');

        // Rutas para entregables dentro del contexto de un hito
        Route::prefix('{hito}/entregables')->name('entregables.')->group(function () {
            Route::get('/', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'index'])
                ->name('index')
                ->middleware('can:entregables.view');

            Route::get('/create', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'create'])
                ->name('create')
                ->middleware('can:entregables.create');

            Route::post('/', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'store'])
                ->name('store')
                ->middleware('can:entregables.create');

            Route::get('/{entregable}', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'show'])
                ->name('show')
                ->middleware('can:entregables.view');

            Route::get('/{entregable}/edit', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'edit'])
                ->name('edit')
                ->middleware('can:entregables.edit');

            Route::put('/{entregable}', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'update'])
                ->name('update')
                ->middleware('can:entregables.edit');

            Route::delete('/{entregable}', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:entregables.delete');

            Route::post('/{entregable}/completar', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'completar'])
                ->name('completar')
                ->middleware('can:entregables.complete');
        });
    });
});