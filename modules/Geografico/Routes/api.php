<?php

use Modules\Geografico\Http\Controllers\Admin\GeographicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Geografico API Routes (Authenticated Users)
|--------------------------------------------------------------------------
|
| Rutas API para todos los usuarios autenticados.
| Utilizadas en modales de ubicación y formularios de usuario.
|
*/

// Geographic routes for all authenticated users (for location modal)
Route::middleware(['auth'])->prefix('api/geographic')->name('api.geographic.')->group(function () {
    Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
    Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
    Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
    Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
});
