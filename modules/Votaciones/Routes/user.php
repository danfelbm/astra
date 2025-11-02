<?php

use Modules\Votaciones\Http\Controllers\User\VotoController;
use Modules\Votaciones\Http\Controllers\User\ResultadosController;
use Modules\Votaciones\Http\Controllers\User\VoteStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Votaciones User Routes
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados para votar y ver resultados.
| Incluye gestión de votos y verificación de sesiones de urna.
|
*/

Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {

    Route::get('votaciones', [VotoController::class, 'index'])
        ->middleware('can:votaciones.view_public')
        ->name('votaciones.index');
    Route::get('votaciones/{votacion}/votar', [VotoController::class, 'show'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.votar');
    Route::post('votaciones/{votacion}/votar', [VotoController::class, 'store'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.store');
    Route::post('votaciones/{votacion}/reset-urna', [VotoController::class, 'resetUrna'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.reset-urna');
    Route::get('votaciones/{votacion}/check-urna-session', [VotoController::class, 'checkUrnaSession'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.check-urna-session');
    Route::get('votaciones/{votacion}/check-status', [VoteStatusController::class, 'check'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.check-status');
    Route::get('votaciones/{votacion}/mi-voto', [VotoController::class, 'miVoto'])
        ->middleware('can:votaciones.view_public')
        ->name('votaciones.mi-voto');
    Route::get('votaciones/{votacion}/resultados', [ResultadosController::class, 'show'])
        ->middleware('can:votaciones.view_results')
        ->name('votaciones.resultados');
});
