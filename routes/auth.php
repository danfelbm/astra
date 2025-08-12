<?php

use App\Http\Controllers\Auth\OTPAuthController;
use Illuminate\Support\Facades\Route;

// Rutas OTP Authentication (nuevas)
Route::middleware('guest')->group(function () {
    Route::get('login', [OTPAuthController::class, 'create'])
        ->name('login');

    Route::post('auth/request-otp', [OTPAuthController::class, 'requestOTP'])
        ->name('auth.request-otp')
        ->middleware('throttle:5,1'); // 5 intentos por minuto

    Route::post('auth/verify-otp', [OTPAuthController::class, 'verifyOTP'])
        ->name('auth.verify-otp')
        ->middleware('throttle:10,1'); // 10 intentos por minuto

    Route::post('auth/resend-otp', [OTPAuthController::class, 'resendOTP'])
        ->name('auth.resend-otp')
        ->middleware('throttle:3,1'); // 3 intentos por minuto
    
    // Endpoint público para obtener configuración de login
    Route::get('auth/login-config', [OTPAuthController::class, 'getLoginConfig'])
        ->name('auth.login-config');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [OTPAuthController::class, 'destroy'])
        ->name('logout');
});
