<?php

use Modules\Votaciones\Http\Controllers\Guest\TokenVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Votaciones Public Routes
|--------------------------------------------------------------------------
|
| Rutas públicas para verificación de tokens de votación.
| No requieren autenticación.
|
*/

// Public token verification routes
Route::prefix('verificar-token')->name('verificar-token.')->group(function () {
    Route::get('/', [TokenVerificationController::class, 'index'])->name('index');
    Route::get('{token}', [TokenVerificationController::class, 'show'])->name('show');
});

// API routes for token verification
Route::prefix('api/verificar-token')->name('api.verificar-token.')->group(function () {
    Route::get('{token}', [TokenVerificationController::class, 'api'])->name('verify');
    Route::get('public-key', [TokenVerificationController::class, 'publicKey'])->name('public-key');
});
