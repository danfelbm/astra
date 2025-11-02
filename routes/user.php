<?php

use Modules\Users\Http\Controllers\User\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| User Routes (Authenticated - Non-Admin)
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados regulares. Requieren login pero no 
| permisos administrativos específicos.
|
*/

// API de formularios para autoguardado (requiere autenticación)
// Rutas principales para usuarios autenticados con prefijo /miembro
Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {
    
    // Dashboard principal para usuarios autenticados
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');


});
