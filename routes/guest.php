<?php

use App\Http\Controllers\Votaciones\Guest\TokenVerificationController;
use App\Http\Controllers\Asamblea\Guest\FrontendAsambleaController;
use App\Http\Controllers\Elecciones\Guest\PostulacionPublicController;
use App\Http\Controllers\Asamblea\Guest\AsambleaPublicParticipantsController;
use App\Http\Controllers\Geografico\Admin\GeographicController;
use App\Http\Controllers\Elecciones\User\PostulacionPublicApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes (Public - No Authentication Required)
|--------------------------------------------------------------------------
|
| Rutas públicas que no requieren autenticación. Disponibles para 
| visitantes anónimos del sistema.
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

// Rutas públicas de formularios (con autenticación opcional)
Route::get('formularios/{slug}', [\App\Http\Controllers\Formularios\Guest\FormularioGuestController::class, 'show'])->name('formularios.show');
Route::post('formularios/{slug}/responder', [\App\Http\Controllers\Formularios\Guest\FormularioGuestController::class, 'store'])->name('formularios.store');
Route::get('formularios/{slug}/success', [\App\Http\Controllers\Formularios\Guest\FormularioGuestController::class, 'success'])->name('formularios.success');

// Ruta pública de consulta de participantes (frontend sin autenticación)
Route::get('consulta-participantes', [FrontendAsambleaController::class, 'consultaParticipantes'])
    ->name('frontend.asambleas.consulta-participantes');

// Ruta pública de postulaciones aceptadas
Route::get('postulaciones-aceptadas', [PostulacionPublicController::class, 'index'])
    ->name('postulaciones.publicas');

// Rutas públicas de consulta de participantes de asambleas (sin autenticación, con rate limiting)
Route::get('asambleas/{asamblea}/participantes-publico', [AsambleaPublicParticipantsController::class, 'show'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('asambleas.public.participants');

Route::get('public-api/asambleas/{asamblea}/participantes', [AsambleaPublicParticipantsController::class, 'getParticipants'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('api.asambleas.public.participants');

Route::post('public-api/asambleas/{asamblea}/buscar-participante', [AsambleaPublicParticipantsController::class, 'search'])
    ->middleware('throttle:30,1') // 30 búsquedas por minuto (más restrictivo)
    ->name('api.asambleas.public.search');

// Public Geographic routes for registration (no auth required)
Route::prefix('api/public/geographic')->name('api.public.geographic.')->group(function () {
    Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
    Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
    Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
    Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
});

// Public API route for postulaciones aceptadas (no auth required)
Route::get('public-api/postulaciones-aceptadas', [PostulacionPublicApiController::class, 'index'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('api.postulaciones.publicas');

// Confirmación de registro público
use App\Http\Controllers\Users\Guest\RegistrationConfirmationController;

Route::prefix('confirmar-registro')->name('registro.confirmacion.')->controller(RegistrationConfirmationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/buscar', 'search')->name('search');
    Route::post('/enviar-verificacion', 'sendVerification')->name('send-verification');
    Route::post('/verificar-codigo', 'verifyCode')->name('verify-code');
    Route::get('/actualizar-datos', 'showUpdateForm')->name('update-form');
    Route::post('/actualizar-datos', 'submitUpdate')->name('submit-update');
    Route::post('/check-timeout', 'checkTimeout')->name('check-timeout');
});