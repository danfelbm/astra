<?php

use App\Http\Controllers\Admin\CandidaturaController as AdminCandidaturaController;
use App\Http\Controllers\Admin\CargoController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\ConvocatoriaController;
use App\Http\Controllers\Admin\GeographicController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\OTPDashboardController;
use App\Http\Controllers\Admin\PeriodoElectoralController;
use App\Http\Controllers\Admin\PostulacionController as AdminPostulacionController;
use App\Http\Controllers\Admin\AsambleaController;
use App\Http\Controllers\AsambleaPublicController;
use App\Http\Controllers\Api\ZoomAuthController;
use App\Http\Controllers\Api\ZoomRegistrantController;
use App\Http\Controllers\Admin\VotacionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SegmentController;
use App\Http\Controllers\CandidaturaController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\PostulacionController;
use App\Http\Controllers\ResultadosController;
use App\Http\Controllers\TokenVerificationController;
use App\Http\Controllers\VotoController;
use App\Http\Controllers\ZoomRedirectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();
    $redirectRoute = 'dashboard'; // Valor por defecto
    
    if ($user) {
        // Obtener el primer rol del usuario
        $userRole = $user->roles()->first();
        
        if ($userRole && method_exists($userRole, 'getRedirectRoute')) {
            $redirectRoute = $userRole->getRedirectRoute();
        } elseif ($user->isAdmin() || $user->isSuperAdmin()) {
            $redirectRoute = 'admin.dashboard';
        }
    }
    
    return Inertia::render('Welcome', [
        'redirectRoute' => $redirectRoute
    ]);
})->name('home');

// Public token verification routes (no authentication required)
Route::prefix('verificar-token')->name('verificar-token.')->group(function () {
    Route::get('/', [TokenVerificationController::class, 'index'])->name('index');
    Route::get('{token}', [TokenVerificationController::class, 'show'])->name('show');
});

// API routes for token verification
Route::prefix('api/verificar-token')->name('api.verificar-token.')->group(function () {
    Route::get('{token}', [TokenVerificationController::class, 'api'])->name('verify');
    Route::get('public-key', [TokenVerificationController::class, 'publicKey'])->name('public-key');
});

// Rutas públicas de formularios (con autenticación opcional)
Route::get('formularios/{slug}', [\App\Http\Controllers\FormularioPublicController::class, 'show'])->name('formularios.show');
Route::post('formularios/{slug}/responder', [\App\Http\Controllers\FormularioPublicController::class, 'store'])->name('formularios.store');
Route::get('formularios/{slug}/success', [\App\Http\Controllers\FormularioPublicController::class, 'success'])->name('formularios.success');

// Ruta pública de consulta de participantes (frontend sin autenticación)
use App\Http\Controllers\FrontendAsambleaController;
Route::get('consulta-participantes', [FrontendAsambleaController::class, 'consultaParticipantes'])
    ->name('frontend.asambleas.consulta-participantes');

// Ruta pública de postulaciones aceptadas
use App\Http\Controllers\PostulacionPublicController;
Route::get('postulaciones-aceptadas', [PostulacionPublicController::class, 'index'])
    ->name('postulaciones.publicas');

// Rutas públicas de consulta de participantes de asambleas (sin autenticación, con rate limiting)
use App\Http\Controllers\AsambleaPublicParticipantsController;
Route::get('asambleas/{asamblea}/participantes-publico', [AsambleaPublicParticipantsController::class, 'show'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('asambleas.public.participants');
Route::get('public-api/asambleas/{asamblea}/participantes', [AsambleaPublicParticipantsController::class, 'getParticipants'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('api.asambleas.public.participants');
Route::post('public-api/asambleas/{asamblea}/buscar-participante', [AsambleaPublicParticipantsController::class, 'search'])
    ->middleware('throttle:30,1') // 30 búsquedas por minuto (más restrictivo)
    ->name('api.asambleas.public.search');

// API de formularios para autoguardado (requiere autenticación)
Route::middleware(['auth'])->prefix('api/formularios')->name('api.formularios.')->group(function () {
    Route::post('autosave', [\App\Http\Controllers\Api\FormularioController::class, 'autosave'])->name('autosave');
    Route::post('{respuesta}/autosave', [\App\Http\Controllers\Api\FormularioController::class, 'autosaveExisting'])->name('autosave.existing');
});

Route::get('dashboard', function () {
    $hasAssemblyAccess = DB::table('asamblea_usuario')
        ->where('usuario_id', auth()->id())
        ->where('asamblea_id', 1)
        ->exists();
    
    return Inertia::render('Dashboard', [
        'hasAssemblyAccess' => $hasAssemblyAccess
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Votaciones routes for regular users
Route::middleware(['auth', 'verified'])->group(function () {
    // Formularios para usuarios autenticados  
    Route::get('formularios', [\App\Http\Controllers\FormularioPublicController::class, 'index'])
        ->middleware('permission:formularios.view_public')
        ->name('formularios.index');
    
    Route::get('votaciones', [VotoController::class, 'index'])
        ->middleware('permission:votaciones.view_public')
        ->name('votaciones.index');
    Route::get('votaciones/{votacion}/votar', [VotoController::class, 'show'])
        ->middleware('permission:votaciones.vote')
        ->name('votaciones.votar');
    Route::post('votaciones/{votacion}/votar', [VotoController::class, 'store'])
        ->middleware('permission:votaciones.vote')
        ->name('votaciones.store');
    Route::get('votaciones/{votacion}/mi-voto', [VotoController::class, 'miVoto'])
        ->middleware('permission:votaciones.view_public')
        ->name('votaciones.mi-voto');
    Route::get('votaciones/{votacion}/resultados', [ResultadosController::class, 'show'])
        ->middleware('permission:votaciones.view_results')
        ->name('votaciones.resultados');
    
    // Candidaturas routes for regular users (con verificación de permisos)
    Route::resource('candidaturas', CandidaturaController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('candidaturas/{candidatura}/historial', [CandidaturaController::class, 'historial'])
        ->middleware('permission:candidaturas.view_own')
        ->name('candidaturas.historial');
    Route::get('candidaturas-estado', [CandidaturaController::class, 'getEstadoCandidatura'])
        ->middleware('permission:candidaturas.view_own')
        ->name('candidaturas.estado');
    
    // Autoguardado de candidaturas
    Route::post('candidaturas/autosave', [CandidaturaController::class, 'autosave'])
        ->middleware('permission:candidaturas.create_own')
        ->name('candidaturas.autosave');
    Route::post('candidaturas/{candidatura}/autosave', [CandidaturaController::class, 'autosaveExisting'])
        ->middleware('permission:candidaturas.edit_own')
        ->name('candidaturas.autosave.existing');
    
    // Postulaciones routes for regular users (con verificación de permisos)
    Route::get('postulaciones', [PostulacionController::class, 'index'])
        ->middleware('permission:postulaciones.view_own')
        ->name('postulaciones.index');
    Route::get('convocatorias/{convocatoria}', [PostulacionController::class, 'show'])
        ->middleware('permission:convocatorias.view_public')
        ->name('convocatorias.show');
    Route::post('convocatorias/{convocatoria}/postular', [PostulacionController::class, 'store'])
        ->middleware('permission:postulaciones.create')
        ->name('postulaciones.store');
    
    // Asambleas routes for regular users (con verificación de permisos)
    Route::get('asambleas', [AsambleaPublicController::class, 'index'])
        ->middleware('permission:asambleas.view_public')
        ->name('asambleas.index');
    Route::get('asambleas/{asamblea}', [AsambleaPublicController::class, 'show'])
        ->middleware('permission:asambleas.view_public')
        ->name('asambleas.show');
    Route::get('asambleas/{asamblea}/participantes', [AsambleaPublicController::class, 'getParticipantes'])
        ->middleware('permission:asambleas.view_public')
        ->name('asambleas.participantes');
    Route::post('asambleas/{asamblea}/marcar-asistencia', [AsambleaPublicController::class, 'marcarAsistencia'])
        ->middleware('permission:asambleas.view_public')
        ->name('asambleas.marcar-asistencia');
    Route::put('asambleas/{asamblea}/participantes/{participante}/asistencia', [AsambleaPublicController::class, 'marcarAsistenciaParticipante'])
        ->middleware('permission:asambleas.view_public')
        ->name('asambleas.marcar-asistencia-participante');
    
    // API routes para Zoom (dentro del grupo auth)
    Route::prefix('api/zoom')->name('api.zoom.')->group(function () {
        Route::post('auth', [ZoomAuthController::class, 'generateSignature'])
            ->middleware('permission:asambleas.join_video')
            ->name('signature');
        Route::get('asambleas/{asamblea}/info', [ZoomAuthController::class, 'getMeetingInfo'])
            ->middleware('permission:asambleas.join_video')
            ->name('meeting-info');
        Route::get('asambleas/{asamblea}/access', [ZoomAuthController::class, 'checkAccess'])
            ->middleware('permission:asambleas.view_public')
            ->name('check-access');
        
        // Rutas para registro de participantes (API mode)
        Route::post('registrants/register', [ZoomRegistrantController::class, 'register'])
            ->middleware('permission:asambleas.participate')
            ->name('registrants.register');
        Route::get('registrants/{asamblea}/status', [ZoomRegistrantController::class, 'status'])
            ->middleware('permission:asambleas.view_public')
            ->name('registrants.status');
        Route::delete('registrants/{asamblea}', [ZoomRegistrantController::class, 'destroy'])
            ->middleware('permission:asambleas.participate')
            ->name('registrants.destroy');
        Route::get('registrants/{registrant}/check-status', [ZoomRegistrantController::class, 'checkStatus'])
            ->middleware('permission:asambleas.view_public')
            ->name('registrants.check-status');
    });
    
    // APIs for postulaciones
    Route::get('api/convocatorias-disponibles', [PostulacionController::class, 'convocatoriasDisponibles'])->name('api.convocatorias.disponibles');
    Route::get('api/mis-candidaturas-aprobadas', [PostulacionController::class, 'misCandidaturasAprobadas'])->name('api.candidaturas.aprobadas');
    
    // API routes for convocatorias (usado por ConvocatoriaSelector)
    Route::get('api/convocatorias/disponibles', [\App\Http\Controllers\Api\ConvocatoriaController::class, 'disponibles'])->name('api.convocatorias.selector.disponibles');
    Route::get('api/convocatorias/{convocatoria}/verificar-disponibilidad', [\App\Http\Controllers\Api\ConvocatoriaController::class, 'verificarDisponibilidad'])->name('api.convocatorias.verificar');
    
    // File upload routes
    Route::prefix('api/files')->name('api.files.')->group(function () {
        Route::post('upload', [FileUploadController::class, 'upload'])->name('upload');
        Route::delete('delete', [FileUploadController::class, 'delete'])->name('delete');
        Route::get('download', [FileUploadController::class, 'download'])->name('download');
        Route::get('info', [FileUploadController::class, 'info'])->name('info');
    });
    
    // Queue Status API routes
    Route::prefix('api/queue')->name('api.queue.')->group(function () {
        Route::get('status', [\App\Http\Controllers\Api\QueueStatusController::class, 'status'])->name('status');
        Route::get('otp/estimate', [\App\Http\Controllers\Api\QueueStatusController::class, 'estimate'])->name('otp.estimate');
        Route::get('otp/position/{identifier}', [\App\Http\Controllers\Api\QueueStatusController::class, 'position'])->name('otp.position');
        Route::get('metrics', [\App\Http\Controllers\Api\QueueStatusController::class, 'metrics'])->name('metrics');
    });
});

// API routes for results data (authenticated)
Route::middleware(['auth', 'verified'])->prefix('api/votaciones')->name('api.votaciones.')->group(function () {
    Route::get('{votacion}/resultados/consolidado', [ResultadosController::class, 'consolidado'])->name('resultados.consolidado');
    Route::get('{votacion}/resultados/territorio', [ResultadosController::class, 'territorio'])->name('resultados.territorio');
    Route::get('{votacion}/resultados/tokens', [ResultadosController::class, 'tokens'])->name('resultados.tokens');
});

// Public Geographic routes for all authenticated users (for location modal)
Route::middleware(['auth'])->prefix('api/geographic')->name('api.geographic.')->group(function () {
    Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
    Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
    Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
    Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
});

// Public Geographic routes for registration (no auth required)
Route::prefix('api/public/geographic')->name('api.public.geographic.')->group(function () {
    Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
    Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
    Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
    Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
});

// Public API route for postulaciones aceptadas (no auth required)
use App\Http\Controllers\Api\PostulacionPublicApiController;
Route::get('public-api/postulaciones-aceptadas', [PostulacionPublicApiController::class, 'index'])
    ->middleware('throttle:60,1') // 60 requests por minuto
    ->name('api.postulaciones.publicas');

Route::get('admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->middleware(['auth', 'verified', 'admin'])->name('admin.dashboard');

// Admin routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Tenants routes (solo super admin)
    Route::resource('tenants', TenantController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::post('tenants/switch', [TenantController::class, 'switch'])
        ->middleware('permission:tenants.switch')
        ->name('tenants.switch');
    
    // Roles routes
    Route::resource('roles', RoleController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:roles.view')
        ->name('roles.permissions');
    Route::post('roles/{role}/segments', [RoleController::class, 'attachSegments'])
        ->middleware('permission:roles.edit')
        ->name('roles.attach-segments');
    
    // Segments routes
    Route::resource('segments', SegmentController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::post('segments/{segment}/evaluate', [SegmentController::class, 'evaluate'])
        ->middleware('permission:segments.edit')
        ->name('segments.evaluate');
    Route::post('segments/{segment}/clear-cache', [SegmentController::class, 'clearCache'])
        ->middleware('permission:segments.edit')
        ->name('segments.clear-cache');
    
    // OTP Dashboard routes
    Route::get('otp-dashboard', [\App\Http\Controllers\Admin\OTPDashboardController::class, 'index'])
        ->middleware('permission:admin.view_dashboard')
        ->name('otp-dashboard');
    Route::get('api/otp-dashboard/queue-status', [\App\Http\Controllers\Admin\OTPDashboardController::class, 'queueStatus'])
        ->middleware('permission:admin.view_dashboard')
        ->name('api.otp-dashboard.queue-status');
    Route::get('api/otp-dashboard/otp-stats', [\App\Http\Controllers\Admin\OTPDashboardController::class, 'otpStats'])
        ->middleware('permission:admin.view_dashboard')
        ->name('api.otp-dashboard.otp-stats');
    Route::get('api/otp-dashboard/queue/{queueName}/details', [\App\Http\Controllers\Admin\OTPDashboardController::class, 'queueDetails'])
        ->middleware('permission:admin.manage_queues')
        ->name('api.otp-dashboard.queue-details');
    Route::post('api/otp-dashboard/retry-failed-jobs', [\App\Http\Controllers\Admin\OTPDashboardController::class, 'retryFailedJobs'])
        ->middleware('permission:admin.manage_queues')
        ->name('api.otp-dashboard.retry-failed');
    Route::post('api/otp-dashboard/clean-failed-jobs', [\App\Http\Controllers\Admin\OTPDashboardController::class, 'cleanFailedJobs'])
        ->middleware('permission:admin.manage_queues')
        ->name('api.otp-dashboard.clean-failed');
    
    Route::resource('votaciones', VotacionController::class)
        ->except(['show'])
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::post('votaciones/{votacione}/toggle-status', [VotacionController::class, 'toggleStatus'])
        ->middleware('permission:votaciones.edit')
        ->name('votaciones.toggle-status');
    Route::match(['GET', 'POST', 'DELETE'], 'votaciones/{votacione}/votantes', [VotacionController::class, 'manageVotantes'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.manage-votantes');
    Route::get('votaciones/{votacione}/search-users', [VotacionController::class, 'searchUsers'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.search-users');
    Route::post('votaciones/{votacione}/importar-votantes', [VotacionController::class, 'importarVotantes'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.importar-votantes');
    
    // Cargos routes
    Route::resource('cargos', CargoController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('cargos-tree', [CargoController::class, 'getTree'])
        ->middleware('permission:cargos.view')
        ->name('cargos.tree');
    Route::get('cargos-for-convocatorias', [CargoController::class, 'getCargosForConvocatorias'])
        ->middleware('permission:cargos.view')
        ->name('cargos.for-convocatorias');
    
    // Periodos Electorales routes
    Route::resource('periodos-electorales', PeriodoElectoralController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('periodos-disponibles', [PeriodoElectoralController::class, 'getPeriodosDisponibles'])
        ->middleware('permission:periodos.view')
        ->name('periodos.disponibles');
    Route::get('periodos-por-estado/{estado}', [PeriodoElectoralController::class, 'getPeriodosPorEstado'])
        ->middleware('permission:periodos.view')
        ->name('periodos.por-estado');
    
    // Asambleas routes
    Route::resource('asambleas', AsambleaController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::match(['GET', 'POST', 'DELETE', 'PUT'], 'asambleas/{asamblea}/participantes', [AsambleaController::class, 'manageParticipantes'])
        ->middleware('permission:asambleas.manage_participants')
        ->name('asambleas.manage-participantes');
    Route::get('asambleas/{asamblea}/participantes-list', [AsambleaController::class, 'getParticipantes'])
        ->middleware('permission:asambleas.view')
        ->name('asambleas.participantes-list');
    
    // Rutas de importación para asambleas
    Route::get('asambleas/{asamblea}/imports', [ImportController::class, 'indexForAsamblea'])
        ->middleware('permission:asambleas.manage_participants')
        ->name('asambleas.imports.index');
    Route::get('asambleas/{asamblea}/imports/recent', [ImportController::class, 'recentForAsamblea'])
        ->middleware('permission:asambleas.manage_participants')
        ->name('asambleas.imports.recent');
    Route::get('asambleas/{asamblea}/imports/active', [ImportController::class, 'activeForAsamblea'])
        ->middleware('permission:asambleas.manage_participants')
        ->name('asambleas.imports.active');
    Route::post('asambleas/{asamblea}/imports/store', [ImportController::class, 'storeWithAsamblea'])
        ->middleware('permission:asambleas.manage_participants')
        ->name('asambleas.imports.store');
    
    // Convocatorias routes
    Route::resource('convocatorias', ConvocatoriaController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('convocatorias-disponibles', [ConvocatoriaController::class, 'getConvocatoriasDisponibles'])
        ->middleware('permission:convocatorias.view')
        ->name('convocatorias.disponibles');
    Route::get('convocatorias-por-estado/{estado}', [ConvocatoriaController::class, 'getConvocatoriasPorEstado'])
        ->middleware('permission:convocatorias.view')
        ->name('convocatorias.por-estado');
    
    // Dashboard de Candidaturas
    Route::get('candidaturas-dashboard', function () {
        return Inertia::render('Admin/CandidaturasDashboard');
    })->middleware('permission:candidaturas.view')
      ->name('candidaturas.dashboard');

    // Candidaturas admin routes - Rutas específicas ANTES del resource
    Route::get('candidaturas/configuracion', [AdminCandidaturaController::class, 'configuracion'])
        ->middleware('permission:candidaturas.configuracion')
        ->name('candidaturas.configuracion');
    Route::post('candidaturas/configuracion', [AdminCandidaturaController::class, 'guardarConfiguracion'])
        ->middleware('permission:candidaturas.configuracion')
        ->name('candidaturas.guardar-configuracion');
    Route::post('candidaturas/configuracion/{configuracion}/activar', [AdminCandidaturaController::class, 'activarConfiguracion'])
        ->middleware('permission:candidaturas.configuracion')
        ->name('candidaturas.activar-configuracion');
    Route::get('candidaturas-por-estado/{estado}', [AdminCandidaturaController::class, 'getCandidaturasPorEstado'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.por-estado');
    Route::get('candidaturas-configuracion-activa', [AdminCandidaturaController::class, 'getConfiguracionActiva'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.configuracion-activa');
    Route::get('candidaturas-estadisticas', [AdminCandidaturaController::class, 'getEstadisticas'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.estadisticas');
    
    // Resource routes después de las rutas específicas
    Route::resource('candidaturas', AdminCandidaturaController::class)
        ->only(['index', 'show'])
        ->middleware('permission:candidaturas.view');
    Route::get('candidaturas/{candidatura}/historial', [AdminCandidaturaController::class, 'historial'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.historial');
    Route::post('candidaturas/{candidatura}/aprobar', [AdminCandidaturaController::class, 'aprobar'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.aprobar');
    Route::post('candidaturas/{candidatura}/rechazar', [AdminCandidaturaController::class, 'rechazar'])
        ->middleware('permission:candidaturas.reject')
        ->name('candidaturas.rechazar');
    Route::post('candidaturas/{candidatura}/volver-borrador', [AdminCandidaturaController::class, 'volverABorrador'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.volver-borrador');
    Route::post('candidaturas/{candidatura}/toggle-subsanar', [AdminCandidaturaController::class, 'toggleSubsanar'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.toggle-subsanar');
    
    // Rutas para comentarios
    Route::post('candidaturas/{candidatura}/comentarios', [AdminCandidaturaController::class, 'updateComentarios'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.comentarios.store');
    Route::get('candidaturas/{candidatura}/comentarios', [AdminCandidaturaController::class, 'getComentarios'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.comentarios.index');
    
    // Rutas para aprobación de campos individuales
    Route::post('candidaturas/{candidatura}/campos/{campoId}/aprobar', [AdminCandidaturaController::class, 'aprobarCampo'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.aprobar-campo');
    Route::post('candidaturas/{candidatura}/campos/{campoId}/rechazar', [AdminCandidaturaController::class, 'rechazarCampo'])
        ->middleware('permission:candidaturas.reject')
        ->name('candidaturas.rechazar-campo');
    Route::get('candidaturas/{candidatura}/estado-aprobacion-campos', [AdminCandidaturaController::class, 'getEstadoAprobacionCampos'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.estado-campos');
    Route::post('candidaturas/{candidatura}/aprobar-global', [AdminCandidaturaController::class, 'aprobarGlobal'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.aprobar-global');
    
    // Rutas para recordatorios masivos de candidaturas
    Route::post('candidaturas/recordatorios/enviar', [AdminCandidaturaController::class, 'enviarRecordatoriosBorrador'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.enviar-recordatorios');
    Route::get('candidaturas/recordatorios/estadisticas', [AdminCandidaturaController::class, 'getEstadisticasBorrador'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.estadisticas-borradores');
    
    // Rutas para notificaciones de candidaturas pendientes
    Route::post('candidaturas/notificaciones/enviar', [AdminCandidaturaController::class, 'enviarNotificacionesPendientes'])
        ->middleware('permission:candidaturas.approve')
        ->name('candidaturas.enviar-notificaciones');
    Route::get('candidaturas/notificaciones/estadisticas', [AdminCandidaturaController::class, 'getEstadisticasPendientes'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.estadisticas-pendientes');
    
    // Postulaciones admin routes  
    Route::resource('postulaciones', AdminPostulacionController::class)
        ->only(['index', 'show'])
        ->parameters(['postulaciones' => 'postulacion'])
        ->middleware('permission:postulaciones.view');
    Route::post('postulaciones/{postulacion}/cambiar-estado', [AdminPostulacionController::class, 'cambiarEstado'])
        ->middleware('permission:postulaciones.review')
        ->name('postulaciones.cambiar-estado');
    Route::get('postulaciones-reportes', [AdminPostulacionController::class, 'reportes'])
        ->middleware('permission:postulaciones.view')
        ->name('postulaciones.reportes');
    Route::get('postulaciones-estadisticas', [AdminPostulacionController::class, 'estadisticas'])
        ->middleware('permission:postulaciones.view')
        ->name('postulaciones.estadisticas');
    Route::get('postulaciones-por-estado/{estado}', [AdminPostulacionController::class, 'porEstado'])
        ->middleware('permission:postulaciones.view')
        ->name('postulaciones.por-estado');
    Route::get('postulaciones-exportar', [AdminPostulacionController::class, 'exportar'])
        ->middleware('permission:postulaciones.view')
        ->name('postulaciones.exportar');
    
    // Formularios admin routes
    Route::resource('formularios', \App\Http\Controllers\Admin\FormularioController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('formularios/{formulario}/exportar', [\App\Http\Controllers\Admin\FormularioController::class, 'exportarRespuestas'])
        ->middleware('permission:formularios.export')
        ->name('formularios.exportar');
    
    // Categorías de formularios (pendiente de implementar)
    // Route::resource('formulario-categorias', \App\Http\Controllers\Admin\FormularioCategoriaController::class)
    //     ->middleware('permission'); // El middleware inferirá el permiso de la acción
    
    // Import routes - General (usuarios)
    Route::get('imports', [ImportController::class, 'indexGeneral'])
        ->middleware('permission:users.import')
        ->name('imports.index');
    Route::get('imports/create', [ImportController::class, 'create'])
        ->middleware('permission:users.import')
        ->name('imports.create');
    Route::post('imports', [ImportController::class, 'store'])
        ->middleware('permission:users.import')
        ->name('imports.store');
    Route::post('imports/analyze', [ImportController::class, 'analyze'])
        ->middleware('permission:users.import')
        ->name('imports.analyze');
    Route::post('imports/{import}/resolve-conflict', [ImportController::class, 'resolveConflict'])
        ->middleware('permission:users.import')
        ->name('imports.resolve-conflict');
    Route::post('imports/{import}/refresh-conflict-data', [ImportController::class, 'refreshConflictData'])
        ->middleware('permission:users.import')
        ->name('imports.refresh-conflict-data');
    
    // Import routes - Específicas
    Route::get('imports/{import}', [ImportController::class, 'show'])
        ->middleware('permission:users.import')
        ->name('imports.show');
    Route::get('imports/{import}/status', [ImportController::class, 'status'])
        ->middleware('permission:users.import')
        ->name('imports.status');
    Route::get('votaciones/{votacion}/imports', [ImportController::class, 'index'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.imports');
    Route::get('votaciones/{votacion}/imports/recent', [ImportController::class, 'recent'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.imports.recent');
    Route::get('votaciones/{votacion}/imports/active', [ImportController::class, 'active'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.imports.active');
    Route::post('votaciones/{votacion}/imports/store', [ImportController::class, 'storeWithVotacion'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.imports.store');
    
    // Configuration routes
    Route::get('configuracion', [ConfiguracionController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('configuracion.index');
    Route::post('configuracion', [ConfiguracionController::class, 'update'])
        ->middleware('permission:settings.edit')
        ->name('configuracion.update');
    Route::post('configuracion/candidaturas', [ConfiguracionController::class, 'updateCandidaturas'])
        ->middleware('permission:settings.edit')
        ->name('configuracion.update.candidaturas');
    
    // Users management routes
    Route::resource('usuarios', UserController::class)
        ->except(['show'])
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::post('usuarios/{usuario}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('permission:users.edit')
        ->name('usuarios.toggle-active');
    
    // Geographic routes for cascade selection
    Route::prefix('geographic')->name('geographic.')->group(function () {
        Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
        Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
        Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
        Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
        Route::get('entidades-por-ids', [GeographicController::class, 'entidadesPorIds'])->name('entidades-por-ids');
    });
});

// Ruta pública para redirección de enlaces Zoom enmascarados
Route::get('/videoconferencia/{masked_id}', [ZoomRedirectController::class, 'redirect'])
    ->name('zoom.redirect');
Route::get('/videoconferencia/{masked_id}/verify', [ZoomRedirectController::class, 'verify'])
    ->name('zoom.verify');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Test routes for debugging (remove in production)
if (file_exists(__DIR__.'/test.php')) {
    require __DIR__.'/test.php';
}
