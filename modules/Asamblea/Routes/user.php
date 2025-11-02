<?php

use Modules\Asamblea\Http\Controllers\User\AsambleaPublicController;
use Modules\Asamblea\Http\Controllers\User\ZoomAuthController;
use Modules\Asamblea\Http\Controllers\User\ZoomRegistrantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Asamblea User Routes
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados.
| Incluye acceso a asambleas, asistencia y Zoom.
|
*/

Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {

    // Asambleas routes for regular users (con verificación de permisos)
    Route::get('asambleas', [AsambleaPublicController::class, 'index'])
        ->middleware('can:asambleas.view_public')
        ->name('asambleas.index');
    Route::get('asambleas/{asamblea}', [AsambleaPublicController::class, 'show'])
        ->middleware('can:asambleas.view_public')
        ->name('asambleas.show');
    Route::get('asambleas/{asamblea}/participantes', [AsambleaPublicController::class, 'getParticipantes'])
        ->middleware('can:asambleas.view_public')
        ->name('asambleas.participantes');
    Route::post('asambleas/{asamblea}/marcar-asistencia', [AsambleaPublicController::class, 'marcarAsistencia'])
        ->middleware('can:asambleas.view_public')
        ->name('asambleas.marcar-asistencia');
    Route::put('asambleas/{asamblea}/participantes/{participante}/asistencia', [AsambleaPublicController::class, 'marcarAsistenciaParticipante'])
        ->middleware('can:asambleas.view_public')
        ->name('asambleas.marcar-asistencia-participante');

    // API routes para Zoom (dentro del grupo auth)
    Route::prefix('api/zoom')->name('api.zoom.')->group(function () {
        Route::post('auth', [ZoomAuthController::class, 'generateSignature'])
            ->middleware('can:asambleas.join_video')
            ->name('signature');
        Route::get('asambleas/{asamblea}/info', [ZoomAuthController::class, 'getMeetingInfo'])
            ->middleware('can:asambleas.join_video')
            ->name('meeting-info');
        Route::get('asambleas/{asamblea}/access', [ZoomAuthController::class, 'checkAccess'])
            ->middleware('can:asambleas.view_public')
            ->name('check-access');

        // Rutas para registro de participantes (API mode)
        Route::post('registrants/register', [ZoomRegistrantController::class, 'register'])
            ->middleware('can:asambleas.participate')
            ->name('registrants.register');
        Route::get('registrants/{asamblea}/status', [ZoomRegistrantController::class, 'status'])
            ->middleware('can:asambleas.view_public')
            ->name('registrants.status');
        Route::delete('registrants/{asamblea}', [ZoomRegistrantController::class, 'destroy'])
            ->middleware('can:asambleas.participate')
            ->name('registrants.destroy');
        Route::get('registrants/{registrant}/check-status', [ZoomRegistrantController::class, 'checkStatus'])
            ->middleware('can:asambleas.view_public')
            ->name('registrants.check-status');
    });
});
