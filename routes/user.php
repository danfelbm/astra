<?php

use App\Http\Controllers\Elecciones\User\CandidaturaController;
use App\Http\Controllers\Core\FileUploadController;
use App\Http\Controllers\Elecciones\User\PostulacionController;
use App\Http\Controllers\Votaciones\User\ResultadosController;
use App\Http\Controllers\Votaciones\User\VotoController;
use App\Http\Controllers\Asamblea\User\AsambleaPublicController;
use App\Http\Controllers\Asamblea\User\ZoomAuthController;
use App\Http\Controllers\Asamblea\User\ZoomRegistrantController;
use App\Http\Controllers\Geografico\Admin\GeographicController;
use App\Http\Controllers\Users\User\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| User Routes (Authenticated - Non-Admin)
|--------------------------------------------------------------------------
|
| Rutas para usuarios autenticados regulares. Requieren login pero no 
| permisos administrativos específicos.
|
*/

// API de formularios para autoguardado (requiere autenticación)
Route::middleware(['auth'])->prefix('api/formularios')->name('api.formularios.')->group(function () {
    Route::post('autosave', [\App\Http\Controllers\Formularios\User\FormularioController::class, 'autosave'])->name('autosave');
    Route::post('{respuesta}/autosave', [\App\Http\Controllers\Formularios\User\FormularioController::class, 'autosaveExisting'])->name('autosave.existing');
});

// Rutas principales para usuarios autenticados con prefijo /miembro
Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {
    
    // Dashboard principal para usuarios autenticados
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Formularios para usuarios autenticados  
    Route::get('formularios', [\App\Http\Controllers\Formularios\User\FormularioUserController::class, 'index'])
        ->middleware('can:formularios.view_public')
        ->name('formularios.index');
    
    // Votaciones routes for regular users
    Route::get('votaciones', [VotoController::class, 'index'])
        ->middleware('can:votaciones.view_public')
        ->name('votaciones.index');
    Route::get('votaciones/{votacion}/votar', [VotoController::class, 'show'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.votar');
    Route::post('votaciones/{votacion}/votar', [VotoController::class, 'store'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.store');
    Route::get('votaciones/{votacion}/check-status', [\App\Http\Controllers\Votaciones\User\VoteStatusController::class, 'check'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.check-status');
    Route::get('votaciones/{votacion}/mi-voto', [VotoController::class, 'miVoto'])
        ->middleware('can:votaciones.view_public')
        ->name('votaciones.mi-voto');
    Route::get('votaciones/{votacion}/resultados', [ResultadosController::class, 'show'])
        ->middleware('can:votaciones.view_results')
        ->name('votaciones.resultados');
    
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
    
    // APIs for postulaciones
    Route::get('api/convocatorias-disponibles', [PostulacionController::class, 'convocatoriasDisponibles'])->name('api.convocatorias.disponibles');
    Route::get('api/mis-candidaturas-aprobadas', [PostulacionController::class, 'misCandidaturasAprobadas'])->name('api.candidaturas.aprobadas');
    
    // API routes for convocatorias (usado por ConvocatoriaSelector)
    Route::get('api/convocatorias/disponibles', [\App\Http\Controllers\Elecciones\User\ConvocatoriaController::class, 'disponibles'])->name('api.convocatorias.selector.disponibles');
    Route::get('api/convocatorias/{convocatoria}/verificar-disponibilidad', [\App\Http\Controllers\Elecciones\User\ConvocatoriaController::class, 'verificarDisponibilidad'])->name('api.convocatorias.verificar');
    
    // File upload routes
    Route::prefix('api/files')->name('api.files.')->group(function () {
        Route::post('upload', [FileUploadController::class, 'upload'])->name('upload');
        Route::delete('delete', [FileUploadController::class, 'delete'])->name('delete');
        Route::get('download', [FileUploadController::class, 'download'])->name('download');
        Route::get('info', [FileUploadController::class, 'info'])->name('info');
    });
    
    // Queue Status API routes
    Route::prefix('api/queue')->name('api.queue.')->group(function () {
        Route::get('status', [\App\Http\Controllers\Core\Api\QueueStatusController::class, 'status'])->name('status');
        Route::get('otp/estimate', [\App\Http\Controllers\Core\Api\QueueStatusController::class, 'estimate'])->name('otp.estimate');
        Route::get('otp/position/{identifier}', [\App\Http\Controllers\Core\Api\QueueStatusController::class, 'position'])->name('otp.position');
        Route::get('metrics', [\App\Http\Controllers\Core\Api\QueueStatusController::class, 'metrics'])->name('metrics');
    });
});

// API routes for results data (authenticated)
Route::middleware(['auth', 'verified'])->prefix('api/votaciones')->name('api.votaciones.')->group(function () {
    Route::get('{votacion}/resultados/consolidado', [ResultadosController::class, 'consolidado'])->name('resultados.consolidado');
    Route::get('{votacion}/resultados/territorio', [ResultadosController::class, 'territorio'])->name('resultados.territorio');
    Route::get('{votacion}/resultados/tokens', [ResultadosController::class, 'tokens'])->name('resultados.tokens');
    Route::get('{votacion}/resultados/ranking-territorio', [ResultadosController::class, 'rankingPorTerritorio'])->name('resultados.ranking-territorio');
    Route::get('{votacion}/resultados/distribucion-opcion', [ResultadosController::class, 'distribucionGeograficaPorOpcion'])->name('resultados.distribucion-opcion');
});

// Geographic routes for all authenticated users (for location modal)
Route::middleware(['auth'])->prefix('api/geographic')->name('api.geographic.')->group(function () {
    Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
    Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
    Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
    Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
});