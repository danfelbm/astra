<?php

use Modules\Asamblea\Http\Controllers\Guest\FrontendAsambleaController;
use Modules\Asamblea\Http\Controllers\Guest\AsambleaPublicParticipantsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Asamblea Public Routes
|--------------------------------------------------------------------------
|
| Rutas públicas para consulta de asambleas y participantes.
| No requieren autenticación.
|
*/

// Ruta pública de consulta de participantes (frontend sin autenticación)
Route::get('consulta-participantes', [FrontendAsambleaController::class, 'consultaParticipantes'])
    ->name('frontend.asambleas.consulta-participantes');

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

// Rutas públicas para redirección de enlaces Zoom enmascarados
Route::get('/videoconferencia/{masked_id}', [\Modules\Asamblea\Http\Controllers\User\ZoomRedirectController::class, 'redirect'])
    ->name('zoom.redirect');
Route::get('/videoconferencia/{masked_id}/verify', [\Modules\Asamblea\Http\Controllers\User\ZoomRedirectController::class, 'verify'])
    ->name('zoom.verify');
