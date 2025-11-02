<?php

use Modules\Formularios\Http\Controllers\User\FormularioController;
use Modules\Formularios\Http\Controllers\User\FormularioUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Formularios User Routes
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados - ver y responder formularios.
| Incluye API de autoguardado.
|
*/

// API de autoguardado de formularios
Route::middleware(['auth'])->prefix('api/formularios')->name('api.formularios.')->group(function () {
    Route::post('autosave', [FormularioController::class, 'autosave'])->name('autosave');
    Route::post('{respuesta}/autosave', [FormularioController::class, 'autosaveExisting'])->name('autosave.existing');
});

// Listado de formularios disponibles para usuarios
Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {
    Route::get('formularios', [FormularioUserController::class, 'index'])
        ->middleware('can:formularios.view_public')
        ->name('formularios.index');
});
