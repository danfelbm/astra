<?php

use Modules\Geografico\Http\Controllers\Admin\GeographicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Geografico Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para selección en cascada de ubicaciones geográficas.
| Utilizadas en formularios administrativos.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Geographic routes for cascade selection
        Route::prefix('geographic')->name('geographic.')->group(function () {
            Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
            Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
            Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
            Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
            Route::get('entidades-por-ids', [GeographicController::class, 'entidadesPorIds'])->name('entidades-por-ids');
        });
    });
