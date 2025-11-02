<?php

use Modules\Users\Http\Controllers\Guest\RegistrationConfirmationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users Guest Routes
|--------------------------------------------------------------------------
|
| Rutas públicas para confirmación de registro.
| No requieren autenticación.
|
*/

// Confirmación de registro público
Route::prefix('confirmar-registro')->name('registro.confirmacion.')->controller(RegistrationConfirmationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/buscar', 'search')->name('search');
    Route::post('/enviar-verificacion', 'sendVerification')->name('send-verification');
    Route::post('/verificar-codigo', 'verifyCode')->name('verify-code');
    Route::get('/actualizar-datos', 'showUpdateForm')->name('update-form');
    Route::post('/actualizar-datos', 'submitUpdate')->name('submit-update');
    Route::post('/check-timeout', 'checkTimeout')->name('check-timeout');
});
