<?php

use Modules\Imports\Http\Controllers\Admin\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Imports Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para sistema de importaciones CSV.
| Gestión de importaciones generales de usuarios.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Import routes for general user imports
        Route::get('imports', [ImportController::class, 'indexGeneral'])
            ->middleware('can:users.import')
            ->name('imports.index');
        Route::get('imports/create', [ImportController::class, 'create'])
            ->middleware('can:users.import')
            ->name('imports.create');
        Route::post('imports', [ImportController::class, 'store'])
            ->middleware('can:users.import')
            ->name('imports.store');
        Route::post('imports/analyze', [ImportController::class, 'analyze'])
            ->middleware('can:users.import')
            ->name('imports.analyze');
        Route::post('imports/{import}/resolve-conflict', [ImportController::class, 'resolveConflict'])
            ->middleware('can:users.import')
            ->name('imports.resolve-conflict');
        Route::post('imports/{import}/refresh-conflict-data', [ImportController::class, 'refreshConflictData'])
            ->middleware('can:users.import')
            ->name('imports.refresh-conflict-data');
        Route::get('imports/{import}', [ImportController::class, 'show'])
            ->middleware('can:users.import')
            ->name('imports.show');
        Route::get('imports/{import}/status', [ImportController::class, 'status'])
            ->middleware('can:users.import')
            ->name('imports.status');
    });
