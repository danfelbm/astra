<?php

use Illuminate\Support\Facades\Route;
use Modules\Asamblea\Http\Controllers\User\AsambleaPublicController;
use Modules\Asamblea\Http\Controllers\User\ZoomAuthController;
use Modules\Asamblea\Http\Controllers\User\ZoomRegistrantController;

Route::middleware(['auth', 'verified'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        // Asambleas routes for regular users
        Route::get('asambleas', [AsambleaPublicController::class, 'index'])
            ->name('asambleas.index');
        
        Route::get('asambleas/{asamblea}', [AsambleaPublicController::class, 'show'])
            ->name('asambleas.show');
        
        Route::get('asambleas/{asamblea}/participantes', [AsambleaPublicController::class, 'getParticipantes'])
            ->name('asambleas.participantes');
        
        Route::post('asambleas/{asamblea}/marcar-asistencia', [AsambleaPublicController::class, 'marcarAsistencia'])
            ->name('asambleas.marcar-asistencia');
        
        Route::put('asambleas/{asamblea}/participantes/{participante}/asistencia', [AsambleaPublicController::class, 'marcarAsistenciaParticipante'])
            ->name('asambleas.marcar-asistencia-participante');
        
        // Zoom routes
        Route::prefix('zoom')->name('zoom.')->group(function () {
            Route::get('check-access/{asamblea}', [ZoomAuthController::class, 'checkAccess'])
                ->name('check-access');
            
            Route::get('meeting-info/{asamblea}', [ZoomAuthController::class, 'getMeetingInfo'])
                ->name('meeting-info');
            
            Route::post('registrants/{asamblea}', [ZoomRegistrantController::class, 'register'])
                ->name('registrants.register');
            
            Route::get('registrants/{asamblea}/status', [ZoomRegistrantController::class, 'checkStatus'])
                ->name('registrants.status');
        });
    });