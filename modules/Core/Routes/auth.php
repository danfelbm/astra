<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Auth;

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('login', [Auth\AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [Auth\AuthenticatedSessionController::class, 'store']);
    Route::post('request-otp', [Auth\OTPAuthController::class, 'requestOTP'])
        ->name('auth.request-otp');
    Route::post('verify-otp', [Auth\OTPAuthController::class, 'verifyOTP'])
        ->name('auth.verify-otp');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [Auth\AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
