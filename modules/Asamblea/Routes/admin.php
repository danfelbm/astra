<?php

use Modules\Asamblea\Http\Controllers\Admin\AsambleaController;
use Modules\Imports\Http\Controllers\Admin\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Asamblea Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de asambleas.
| Incluye CRUD, participantes, imports y votaciones asociadas.
|
*/

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Asambleas routes - Expandido para usar Spatie
    Route::get('asambleas', [AsambleaController::class, 'index'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.index');
    Route::get('asambleas/create', [AsambleaController::class, 'create'])
        ->middleware('can:asambleas.create')
        ->name('asambleas.create');
    Route::post('asambleas', [AsambleaController::class, 'store'])
        ->middleware('can:asambleas.create')
        ->name('asambleas.store');
    Route::get('asambleas/{asamblea}', [AsambleaController::class, 'show'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.show');
    Route::get('asambleas/{asamblea}/edit', [AsambleaController::class, 'edit'])
        ->middleware('can:asambleas.edit')
        ->name('asambleas.edit');
    Route::put('asambleas/{asamblea}', [AsambleaController::class, 'update'])
        ->middleware('can:asambleas.edit')
        ->name('asambleas.update');
    Route::delete('asambleas/{asamblea}', [AsambleaController::class, 'destroy'])
        ->middleware('can:asambleas.delete')
        ->name('asambleas.destroy');

    Route::match(['GET', 'POST', 'DELETE', 'PUT'], 'asambleas/{asamblea}/participantes', [AsambleaController::class, 'manageParticipantes'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.manage-participantes');
    Route::get('asambleas/{asamblea}/participantes-list', [AsambleaController::class, 'getParticipantes'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.participantes-list');

    // Rutas de importación para asambleas
    Route::get('asambleas/{asamblea}/imports', [ImportController::class, 'indexForAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.index');
    Route::get('asambleas/{asamblea}/imports/recent', [ImportController::class, 'recentForAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.recent');
    Route::get('asambleas/{asamblea}/imports/active', [ImportController::class, 'activeForAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.active');
    Route::post('asambleas/{asamblea}/imports/store', [ImportController::class, 'storeWithAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.store');

    // Rutas de votaciones asociadas a asambleas
    Route::get('asambleas/{asamblea}/votaciones', [AsambleaController::class, 'getVotaciones'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.votaciones');
    Route::post('asambleas/{asamblea}/votaciones/{votacion}/sync', [AsambleaController::class, 'syncParticipantsToVotacion'])
        ->middleware('can:asambleas.sync_participants')
        ->name('asambleas.sync-participants');
    Route::get('sync-job/{jobId}/status', [AsambleaController::class, 'getSyncJobStatus'])
        ->middleware('can:asambleas.view')
        ->name('sync-job.status');
});
