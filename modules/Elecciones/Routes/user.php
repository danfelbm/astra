<?php

use Illuminate\Support\Facades\Route;
use Modules\Elecciones\Http\Controllers\User\CandidaturaController;
use Modules\Elecciones\Http\Controllers\User\PostulacionController;
use Modules\Elecciones\Http\Controllers\User\ConvocatoriaController;

/*
|--------------------------------------------------------------------------
| User Routes del Módulo Elecciones
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados.
| Incluye Candidaturas, Postulaciones y Convocatorias.
|
*/

Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {
    
    // Candidaturas routes for regular users (con verificación de permisos)
    Route::resource('candidaturas', CandidaturaController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->middleware('auth');
    Route::get('candidaturas/{candidatura}/historial', [CandidaturaController::class, 'historial'])
        ->middleware('auth')
        ->name('candidaturas.historial');
    Route::get('candidaturas-estado', [CandidaturaController::class, 'getEstadoCandidatura'])
        ->middleware('auth')
        ->name('candidaturas.estado');
    
    // Autoguardado de candidaturas
    Route::post('candidaturas/autosave', [CandidaturaController::class, 'autosave'])
        ->middleware('auth')
        ->name('candidaturas.autosave');
    Route::post('candidaturas/{candidatura}/autosave', [CandidaturaController::class, 'autosaveExisting'])
        ->middleware('auth')
        ->name('candidaturas.autosave.existing');
    
    // Postulaciones routes for regular users (con verificación de permisos)
    Route::get('postulaciones', [PostulacionController::class, 'index'])
        ->middleware('can:postulaciones.view_own')
        ->name('postulaciones.index');
    Route::get('convocatorias/{convocatoria}', [PostulacionController::class, 'show'])
        ->middleware('can:convocatorias.view_public')
        ->name('convocatorias.show');
    Route::post('convocatorias/{convocatoria}/postular', [PostulacionController::class, 'store'])
        ->middleware('can:postulaciones.create')
        ->name('postulaciones.store');
    
    
    // APIs for postulaciones
    Route::get('api/convocatorias-disponibles', [PostulacionController::class, 'convocatoriasDisponibles'])->name('api.convocatorias.disponibles');
    Route::get('api/mis-candidaturas-aprobadas', [PostulacionController::class, 'misCandidaturasAprobadas'])->name('api.candidaturas.aprobadas');
    
    // API routes for convocatorias (usado por ConvocatoriaSelector)
    Route::get('api/convocatorias/disponibles', [\Modules\Elecciones\Http\Controllers\User\ConvocatoriaController::class, 'disponibles'])->name('api.convocatorias.selector.disponibles');
    Route::get('api/convocatorias/{convocatoria}/verificar-disponibilidad', [\Modules\Elecciones\Http\Controllers\User\ConvocatoriaController::class, 'verificarDisponibilidad'])->name('api.convocatorias.verificar');

});
