<?php

use App\Http\Controllers\Admin\CandidaturaController as AdminCandidaturaController;
use App\Http\Controllers\Admin\CargoController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\ConvocatoriaController;
use App\Http\Controllers\Admin\GeographicController;
use App\Http\Controllers\Admin\ImportController;
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
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
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

// API de formularios para autoguardado (requiere autenticación)
Route::middleware(['auth'])->prefix('api/formularios')->name('api.formularios.')->group(function () {
    Route::post('autosave', [\App\Http\Controllers\Api\FormularioController::class, 'autosave'])->name('autosave');
    Route::post('{respuesta}/autosave', [\App\Http\Controllers\Api\FormularioController::class, 'autosaveExisting'])->name('autosave.existing');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
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
    
    Route::resource('votaciones', VotacionController::class)
        ->except(['show'])
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::post('votaciones/{votacione}/toggle-status', [VotacionController::class, 'toggleStatus'])
        ->middleware('permission:votaciones.edit')
        ->name('votaciones.toggle-status');
    Route::match(['GET', 'POST', 'DELETE'], 'votaciones/{votacione}/votantes', [VotacionController::class, 'manageVotantes'])
        ->middleware('permission:votaciones.manage_voters')
        ->name('votaciones.manage-votantes');
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
    
    // Convocatorias routes
    Route::resource('convocatorias', ConvocatoriaController::class)
        ->middleware('permission'); // El middleware inferirá el permiso de la acción
    Route::get('convocatorias-disponibles', [ConvocatoriaController::class, 'getConvocatoriasDisponibles'])
        ->middleware('permission:convocatorias.view')
        ->name('convocatorias.disponibles');
    Route::get('convocatorias-por-estado/{estado}', [ConvocatoriaController::class, 'getConvocatoriasPorEstado'])
        ->middleware('permission:convocatorias.view')
        ->name('convocatorias.por-estado');
    
    // Candidaturas admin routes - Rutas específicas ANTES del resource
    Route::get('candidaturas/configuracion', [AdminCandidaturaController::class, 'configuracion'])
        ->middleware('permission:candidaturas.view')
        ->name('candidaturas.configuracion');
    Route::post('candidaturas/configuracion', [AdminCandidaturaController::class, 'guardarConfiguracion'])
        ->middleware('permission:candidaturas.create')
        ->name('candidaturas.guardar-configuracion');
    Route::post('candidaturas/configuracion/{configuracion}/activar', [AdminCandidaturaController::class, 'activarConfiguracion'])
        ->middleware('permission:candidaturas.approve')
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
    
    // Import routes
    Route::get('imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::get('imports/{import}/status', [ImportController::class, 'status'])->name('imports.status');
    Route::get('votaciones/{votacion}/imports', [ImportController::class, 'index'])->name('votaciones.imports');
    Route::get('votaciones/{votacion}/imports/recent', [ImportController::class, 'recent'])->name('votaciones.imports.recent');
    Route::get('votaciones/{votacion}/imports/active', [ImportController::class, 'active'])->name('votaciones.imports.active');
    
    // Configuration routes
    Route::get('configuracion', [ConfiguracionController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('configuracion.index');
    Route::post('configuracion', [ConfiguracionController::class, 'update'])
        ->middleware('permission:settings.edit')
        ->name('configuracion.update');
    
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Test routes for debugging (remove in production)
if (file_exists(__DIR__.'/test.php')) {
    require __DIR__.'/test.php';
}
