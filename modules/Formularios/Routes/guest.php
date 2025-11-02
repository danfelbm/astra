<?php

use Modules\Formularios\Http\Controllers\Guest\FormularioGuestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Formularios Guest Routes
|--------------------------------------------------------------------------
|
| Rutas públicas para responder formularios sin autenticación.
|
*/

// Rutas públicas de formularios (con autenticación opcional)
Route::get('formularios/{slug}', [FormularioGuestController::class, 'show'])->name('formularios.show');
Route::post('formularios/{slug}/responder', [FormularioGuestController::class, 'store'])->name('formularios.store');
Route::get('formularios/{slug}/success', [FormularioGuestController::class, 'success'])->name('formularios.success');
