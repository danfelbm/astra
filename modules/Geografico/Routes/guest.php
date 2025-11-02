<?php

use Modules\Geografico\Http\Controllers\Admin\GeographicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Geografico Public Routes
|--------------------------------------------------------------------------
|
| Rutas públicas para selección de ubicaciones geográficas.
| Utilizadas en registro público y formularios sin autenticación.
|
*/

// Public Geographic routes for registration (no auth required)
Route::prefix('api/public/geographic')->name('api.public.geographic.')->group(function () {
    Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
    Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
    Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
    Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
});
