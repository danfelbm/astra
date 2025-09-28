<?php

use Modules\Elecciones\Http\Controllers\User\CandidaturaController;
use Modules\Core\Http\Controllers\FileUploadController;
use Modules\Elecciones\Http\Controllers\User\PostulacionController;
use Modules\Votaciones\Http\Controllers\User\ResultadosController;
use Modules\Votaciones\Http\Controllers\User\VotoController;
use Modules\Asamblea\Http\Controllers\User\AsambleaPublicController;
use Modules\Asamblea\Http\Controllers\User\ZoomAuthController;
use Modules\Asamblea\Http\Controllers\User\ZoomRegistrantController;
use Modules\Geografico\Http\Controllers\Admin\GeographicController;
use Modules\Users\Http\Controllers\User\DashboardController;
use Modules\Proyectos\Http\Controllers\User\MisProyectosController;
use Modules\Proyectos\Http\Controllers\User\MisContratosController;
use Modules\Proyectos\Http\Controllers\User\MisObligacionesController;
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
    Route::post('autosave', [\Modules\Formularios\Http\Controllers\User\FormularioController::class, 'autosave'])->name('autosave');
    Route::post('{respuesta}/autosave', [\Modules\Formularios\Http\Controllers\User\FormularioController::class, 'autosaveExisting'])->name('autosave.existing');
});

// Rutas principales para usuarios autenticados con prefijo /miembro
Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {
    
    // Dashboard principal para usuarios autenticados
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Formularios para usuarios autenticados  
    Route::get('formularios', [\Modules\Formularios\Http\Controllers\User\FormularioUserController::class, 'index'])
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
    Route::post('votaciones/{votacion}/reset-urna', [VotoController::class, 'resetUrna'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.reset-urna');
    Route::get('votaciones/{votacion}/check-urna-session', [VotoController::class, 'checkUrnaSession'])
        ->middleware('can:votaciones.vote')
        ->name('votaciones.check-urna-session');
    Route::get('votaciones/{votacion}/check-status', [\Modules\Votaciones\Http\Controllers\User\VoteStatusController::class, 'check'])
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
    Route::get('api/convocatorias/disponibles', [\Modules\Elecciones\Http\Controllers\User\ConvocatoriaController::class, 'disponibles'])->name('api.convocatorias.selector.disponibles');
    Route::get('api/convocatorias/{convocatoria}/verificar-disponibilidad', [\Modules\Elecciones\Http\Controllers\User\ConvocatoriaController::class, 'verificarDisponibilidad'])->name('api.convocatorias.verificar');
    
    // File upload routes
    Route::prefix('api/files')->name('api.files.')->group(function () {
        Route::post('upload', [FileUploadController::class, 'upload'])->name('upload');
        Route::delete('delete', [FileUploadController::class, 'delete'])->name('delete');
        Route::get('download', [FileUploadController::class, 'download'])->name('download');
        Route::get('info', [FileUploadController::class, 'info'])->name('info');
    });
    
    // Queue Status API routes
    Route::prefix('api/queue')->name('api.queue.')->group(function () {
        Route::get('status', [\Modules\Core\Http\Controllers\Api\QueueStatusController::class, 'status'])->name('status');
        Route::get('otp/estimate', [\Modules\Core\Http\Controllers\Api\QueueStatusController::class, 'estimate'])->name('otp.estimate');
        Route::get('otp/position/{identifier}', [\Modules\Core\Http\Controllers\Api\QueueStatusController::class, 'position'])->name('otp.position');
        Route::get('metrics', [\Modules\Core\Http\Controllers\Api\QueueStatusController::class, 'metrics'])->name('metrics');
    });

    // MÓDULO PROYECTOS - Rutas para usuarios
    Route::prefix('mis-proyectos')->name('mis-proyectos.')->group(function () {
        Route::get('/', [MisProyectosController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.view_own');

        Route::get('/create', [MisProyectosController::class, 'create'])
            ->name('create')
            ->middleware('can:proyectos.create_own');

        Route::post('/', [MisProyectosController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.create_own');

        Route::get('/{proyecto}', [MisProyectosController::class, 'show'])
            ->name('show')
            ->middleware('can:proyectos.view_own');

        Route::get('/{proyecto}/edit', [MisProyectosController::class, 'edit'])
            ->name('edit')
            ->middleware('can:proyectos.edit_own');

        Route::put('/{proyecto}', [MisProyectosController::class, 'update'])
            ->name('update')
            ->middleware('can:proyectos.edit_own');

        // Actualizar estado del proyecto
        Route::post('/{proyecto}/cambiar-estado', [MisProyectosController::class, 'cambiarEstado'])
            ->name('cambiar-estado')
            ->middleware('can:proyectos.edit_own');

        // Ver/editar campos personalizados
        Route::get('/{proyecto}/campos-personalizados', [MisProyectosController::class, 'camposPersonalizados'])
            ->name('campos-personalizados')
            ->middleware('can:proyectos.view_own');

        Route::post('/{proyecto}/campos-personalizados', [MisProyectosController::class, 'guardarCamposPersonalizados'])
            ->name('guardar-campos-personalizados')
            ->middleware('can:proyectos.edit_own');
    });

    // Rutas para Mis Hitos
    Route::prefix('mis-hitos')->name('mis-hitos.')->group(function () {
        Route::get('/', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'index'])
            ->name('index')
            ->middleware('can:hitos.view_own');

        Route::get('/timeline', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'timeline'])
            ->name('timeline')
            ->middleware('can:hitos.view_own');

        Route::get('/{hito}', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'show'])
            ->name('show')
            ->middleware('can:hitos.view_own');

        Route::post('/{hito}/entregables/{entregable}/completar', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'completarEntregable'])
            ->name('completar-entregable')
            ->middleware('can:hitos.complete_own');

        Route::put('/{hito}/entregables/{entregable}/estado', [\Modules\Proyectos\Http\Controllers\User\MisHitosController::class, 'actualizarEstadoEntregable'])
            ->name('actualizar-estado-entregable')
            ->middleware('can:hitos.update_progress');
    });

    // Rutas para mis contratos
    Route::prefix('mis-contratos')->name('mis-contratos.')->group(function () {
        Route::get('/', [MisContratosController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.view_own');

        Route::get('/proximos-vencer', [MisContratosController::class, 'proximosVencer'])
            ->name('proximos-vencer')
            ->middleware('can:contratos.view_own');

        Route::get('/vencidos', [MisContratosController::class, 'vencidos'])
            ->name('vencidos')
            ->middleware('can:contratos.view_own');

        Route::get('/{contrato}', [MisContratosController::class, 'show'])
            ->name('show')
            ->middleware('can:contratos.view_own');

        Route::get('/{contrato}/descargar-pdf', [MisContratosController::class, 'descargarPDF'])
            ->name('descargar-pdf')
            ->middleware('can:contratos.view_own');
    });

    // Rutas para Mis Obligaciones
    Route::prefix('mis-obligaciones')->name('mis-obligaciones.')->group(function () {
        Route::get('/', [MisObligacionesController::class, 'index'])
            ->name('index')
            ->middleware('can:obligaciones.view_own');

        Route::get('/calendario', [MisObligacionesController::class, 'calendario'])
            ->name('calendario')
            ->middleware('can:obligaciones.view_own');

        Route::get('/{obligacion}', [MisObligacionesController::class, 'show'])
            ->name('show')
            ->middleware('can:obligaciones.view_own');

        Route::post('/{obligacion}/completar', [MisObligacionesController::class, 'completar'])
            ->name('completar')
            ->middleware('can:obligaciones.complete_own');

        Route::put('/{obligacion}/progreso', [MisObligacionesController::class, 'actualizarProgreso'])
            ->name('actualizar-progreso')
            ->middleware('can:obligaciones.view_own');
    });
});

// API routes for results data (authenticated)
Route::middleware(['auth', 'verified'])->prefix('api/votaciones')->name('api.votaciones.')->group(function () {
    Route::get('{votacion}/resultados/consolidado', [ResultadosController::class, 'consolidado'])->name('resultados.consolidado');
    Route::get('{votacion}/resultados/territorio', [ResultadosController::class, 'territorio'])->name('resultados.territorio');
    Route::get('{votacion}/resultados/tokens', [ResultadosController::class, 'tokens'])->name('resultados.tokens');
    Route::get('{votacion}/resultados/tokens/download', [ResultadosController::class, 'downloadTokensCsv'])->name('resultados.tokens.download');
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