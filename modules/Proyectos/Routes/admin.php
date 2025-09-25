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

    // Rutas para gestión de campos personalizados de contratos
    // COMENTADO: Ahora se usa el controlador unificado CampoPersonalizadoController
    /*
    Route::prefix('campos-personalizados-contrato')->name('campos-personalizados-contrato.')->group(function () {
        Route::get('/', [CampoPersonalizadoContratoController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.manage_fields');

        Route::get('/create', [CampoPersonalizadoContratoController::class, 'create'])
            ->name('create')
            ->middleware('can:contratos.manage_fields');

        Route::post('/', [CampoPersonalizadoContratoController::class, 'store'])
            ->name('store')
            ->middleware('can:contratos.manage_fields');

        Route::get('/{campo}', [CampoPersonalizadoContratoController::class, 'show'])
            ->name('show')
            ->middleware('can:contratos.manage_fields');

        Route::get('/{campo}/edit', [CampoPersonalizadoContratoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:contratos.manage_fields');

        Route::put('/{campo}', [CampoPersonalizadoContratoController::class, 'update'])
            ->name('update')
            ->middleware('can:contratos.manage_fields');

        Route::delete('/{campo}', [CampoPersonalizadoContratoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:contratos.manage_fields');

        Route::post('/reordenar', [CampoPersonalizadoContratoController::class, 'reordenar'])
            ->name('reordenar')
            ->middleware('can:contratos.manage_fields');

        Route::post('/{campo}/toggle-activo', [CampoPersonalizadoContratoController::class, 'toggleActivo'])
            ->name('toggle-activo')
            ->middleware('can:contratos.manage_fields');

        Route::post('/{campo}/duplicar', [CampoPersonalizadoContratoController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:contratos.manage_fields');
    });
    */
});