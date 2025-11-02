<?php

use Illuminate\Support\Facades\Route;
use Modules\Elecciones\Http\Controllers\Guest\PostulacionPublicController;
use Modules\Elecciones\Http\Controllers\Guest\PostulacionPublicApiController;

/*
|--------------------------------------------------------------------------
| Guest Routes del Módulo Elecciones
|--------------------------------------------------------------------------
|
| Rutas públicas para consulta de postulaciones aceptadas.
| No requieren autenticación.
|
*/

// Ruta pública de postulaciones aceptadas
Route::get('postulaciones-aceptadas', [PostulacionPublicController::class, 'index'])
    ->name('postulaciones.publicas');

// Public API route for postulaciones aceptadas (no auth required)
Route::get('public-api/postulaciones-aceptadas', [PostulacionPublicApiController::class, 'index'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('api.postulaciones.publicas');
