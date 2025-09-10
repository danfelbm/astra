<?php

use Modules\Votaciones\Http\Controllers\Guest\TokenVerificationController;
use Modules\Asamblea\Http\Controllers\Guest\FrontendAsambleaController;
use Modules\Elecciones\Http\Controllers\Guest\PostulacionPublicController;
use Modules\Asamblea\Http\Controllers\Guest\AsambleaPublicParticipantsController;
use Modules\Geografico\Http\Controllers\Admin\GeographicController;
use Modules\Elecciones\Http\Controllers\Guest\PostulacionPublicApiController;
use Modules\Campanas\Http\Controllers\Guest\CampanaTrackingController;
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
Route::get('formularios/{slug}', [\Modules\Formularios\Http\Controllers\Guest\FormularioGuestController::class, 'show'])->name('formularios.show');
Route::post('formularios/{slug}/responder', [\Modules\Formularios\Http\Controllers\Guest\FormularioGuestController::class, 'store'])->name('formularios.store');
Route::get('formularios/{slug}/success', [\Modules\Formularios\Http\Controllers\Guest\FormularioGuestController::class, 'success'])->name('formularios.success');

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
use Modules\Users\Http\Controllers\Guest\RegistrationConfirmationController;

Route::prefix('confirmar-registro')->name('registro.confirmacion.')->controller(RegistrationConfirmationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/buscar', 'search')->name('search');
    Route::post('/enviar-verificacion', 'sendVerification')->name('send-verification');
    Route::post('/verificar-codigo', 'verifyCode')->name('verify-code');
    Route::get('/actualizar-datos', 'showUpdateForm')->name('update-form');
    Route::post('/actualizar-datos', 'submitUpdate')->name('submit-update');
    Route::post('/check-timeout', 'checkTimeout')->name('check-timeout');
});

// MÓDULO CAMPAÑAS - Rutas públicas de tracking
Route::prefix('t')->name('campanas.tracking.')->group(function () {
    
    // Pixel de tracking para apertura de emails
    Route::get('/p/{trackingId}', [CampanaTrackingController::class, 'pixel'])
        ->name('pixel');
    
    // Tracking de clicks en enlaces
    Route::get('/c/{trackingId}/{url}', [CampanaTrackingController::class, 'click'])
        ->name('click')
        ->where('url', '.*'); // Permitir cualquier URL codificada
    
    // Tracking de descargas
    Route::get('/d/{trackingId}/{fileId}', [CampanaTrackingController::class, 'download'])
        ->name('download');
    
    // Desuscripción
    Route::get('/u/{trackingId}', [CampanaTrackingController::class, 'unsubscribe'])
        ->name('unsubscribe');
    
    // Vista web del email
    Route::get('/w/{trackingId}', [CampanaTrackingController::class, 'webView'])
        ->name('webview');
    
    // Tracking específico de WhatsApp (opcional)
    Route::get('/wa/{trackingId}', [CampanaTrackingController::class, 'whatsapp'])
        ->name('whatsapp');
});

// Webhooks de proveedores de email (sin prefijo para compatibilidad)
Route::prefix('webhooks/email')->name('campanas.webhooks.')->group(function () {
    
    Route::post('/resend', [CampanaTrackingController::class, 'webhook'])
        ->name('resend')
        ->defaults('provider', 'resend');
    
    Route::post('/sendgrid', [CampanaTrackingController::class, 'webhook'])
        ->name('sendgrid')
        ->defaults('provider', 'sendgrid');
    
    Route::post('/mailgun', [CampanaTrackingController::class, 'webhook'])
        ->name('mailgun')
        ->defaults('provider', 'mailgun');
});