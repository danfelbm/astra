<?php

use Illuminate\Support\Facades\Route;
use Modules\Asamblea\Http\Controllers\Guest\FrontendAsambleaController;
use Modules\Asamblea\Http\Controllers\Guest\AsambleaPublicParticipantsController;

// Rutas públicas sin autenticación
Route::name('public.')
    ->group(function () {
        // Consulta de participantes
        Route::get('consulta-participantes', [FrontendAsambleaController::class, 'consultaParticipantes'])
            ->name('consulta-participantes');
        
        // Vista de participantes públicos
        Route::get('asambleas/{asamblea}/participantes-publico', [AsambleaPublicParticipantsController::class, 'show'])
            ->name('asambleas.participantes');
        
        // API pública
        Route::prefix('public-api')->name('api.')->group(function () {
            Route::get('asambleas/{asamblea}/participantes', [AsambleaPublicParticipantsController::class, 'getParticipants'])
                ->name('asambleas.participantes');
            
            Route::post('asambleas/{asamblea}/buscar-participante', [AsambleaPublicParticipantsController::class, 'search'])
                ->name('asambleas.buscar');
        });
    });