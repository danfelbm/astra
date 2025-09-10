<?php

use Illuminate\Support\Facades\Route;
use Modules\Campanas\Http\Controllers\Guest\CampanaTrackingController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas del Módulo Campañas - Tracking
|--------------------------------------------------------------------------
| Estas rutas son accesibles sin autenticación para el tracking de campañas
*/

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