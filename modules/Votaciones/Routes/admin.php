<?php

use Modules\Votaciones\Http\Controllers\Admin\VotacionController;
use Modules\Imports\Http\Controllers\Admin\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Votaciones Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de votaciones.
| Incluye CRUD de votaciones y gestión de votantes.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Rutas de votaciones con middlewares específicos de Spatie
        Route::get('votaciones', [VotacionController::class, 'index'])
            ->middleware('can:votaciones.view')
            ->name('votaciones.index');
        Route::get('votaciones/create', [VotacionController::class, 'create'])
            ->middleware('can:votaciones.create')
            ->name('votaciones.create');
        Route::post('votaciones', [VotacionController::class, 'store'])
            ->middleware('can:votaciones.create')
            ->name('votaciones.store');
        Route::get('votaciones/{votacione}/edit', [VotacionController::class, 'edit'])
            ->middleware('can:votaciones.edit')
            ->name('votaciones.edit');
        Route::put('votaciones/{votacione}', [VotacionController::class, 'update'])
            ->middleware('can:votaciones.edit')
            ->name('votaciones.update');
        Route::delete('votaciones/{votacione}', [VotacionController::class, 'destroy'])
            ->middleware('can:votaciones.delete')
            ->name('votaciones.destroy');
        Route::post('votaciones/{votacione}/toggle-status', [VotacionController::class, 'toggleStatus'])
            ->middleware('can:votaciones.edit')
            ->name('votaciones.toggle-status');
        Route::match(['GET', 'POST', 'DELETE'], 'votaciones/{votacione}/votantes', [VotacionController::class, 'manageVotantes'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.manage-votantes');
        Route::get('votaciones/{votacione}/search-users', [VotacionController::class, 'searchUsers'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.search-users');
        Route::get('votaciones/{votacione}/assigned-voters', [VotacionController::class, 'getAssignedVoters'])
            ->middleware('can:votaciones.view')
            ->name('votaciones.assigned-voters');
        Route::post('votaciones/{votacione}/importar-votantes', [VotacionController::class, 'importarVotantes'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.importar-votantes');

        // Import routes para Votaciones
        Route::get('votaciones/{votacion}/imports', [ImportController::class, 'index'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.imports');
        Route::get('votaciones/{votacion}/imports/recent', [ImportController::class, 'recent'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.imports.recent');
        Route::get('votaciones/{votacion}/imports/active', [ImportController::class, 'active'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.imports.active');
        Route::post('votaciones/{votacion}/imports/store', [ImportController::class, 'storeWithVotacion'])
            ->middleware('can:votaciones.manage_voters')
            ->name('votaciones.imports.store');
    });
