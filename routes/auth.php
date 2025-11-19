<?php

use Modules\Core\Http\Controllers\Auth\OTPAuthController;
use Modules\Core\Http\Controllers\Auth\RegisteredUserController;
use Modules\Core\Http\Middleware\ThrottleOTPRequests;
use Illuminate\Support\Facades\Route;

// Rutas OTP Authentication (nuevas)
Route::middleware('guest')->group(function () {
    Route::get('login', [OTPAuthController::class, 'create'])
        ->name('login');

    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::post('auth/request-otp', [OTPAuthController::class, 'requestOTP'])
        ->name('auth.request-otp')
        ->middleware([ThrottleOTPRequests::class.':request-otp']); // Rate limiting inteligente

    Route::post('auth/verify-otp', [OTPAuthController::class, 'verifyOTP'])
        ->name('auth.verify-otp')
        ->middleware([ThrottleOTPRequests::class.':verify-otp']); // Rate limiting inteligente

    Route::post('auth/resend-otp', [OTPAuthController::class, 'resendOTP'])
        ->name('auth.resend-otp')
        ->middleware([ThrottleOTPRequests::class.':resend-otp']); // Rate limiting inteligente
    
    // Endpoint público para obtener configuración de login
    Route::get('auth/login-config', [OTPAuthController::class, 'getLoginConfig'])
        ->name('auth.login-config');

    // Endpoints públicos para estado de cola OTP (usados por OTPQueueStatus component)
    Route::get('api/queue/otp/position/{identifier}', [OTPAuthController::class, 'getQueuePosition'])
        ->name('api.queue.otp.position');

    Route::get('api/queue/otp/estimate', [OTPAuthController::class, 'getQueueEstimate'])
        ->name('api.queue.otp.estimate');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [OTPAuthController::class, 'destroy'])
        ->name('logout');
});
