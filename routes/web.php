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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Votaciones routes for regular users
Route::middleware(['auth', 'verified'])->group(function () {
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

Route::get('admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->middleware(['auth', 'verified', 'admin'])->name('admin.dashboard');

// Admin routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Tenants routes (solo super admin)
    Route::resource('tenants', TenantController::class);
    Route::post('tenants/switch', [TenantController::class, 'switch'])->name('tenants.switch');
    
    // Roles routes
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::post('roles/{role}/segments', [RoleController::class, 'attachSegments'])->name('roles.attach-segments');
    
    // Segments routes
    Route::resource('segments', SegmentController::class);
    Route::post('segments/{segment}/evaluate', [SegmentController::class, 'evaluate'])->name('segments.evaluate');
    Route::post('segments/{segment}/clear-cache', [SegmentController::class, 'clearCache'])->name('segments.clear-cache');
    
    Route::resource('votaciones', VotacionController::class)->except(['show']);
    Route::post('votaciones/{votacione}/toggle-status', [VotacionController::class, 'toggleStatus'])
        ->name('votaciones.toggle-status');
    Route::match(['GET', 'POST', 'DELETE'], 'votaciones/{votacione}/votantes', [VotacionController::class, 'manageVotantes'])
        ->name('votaciones.manage-votantes');
    Route::post('votaciones/{votacione}/importar-votantes', [VotacionController::class, 'importarVotantes'])
        ->name('votaciones.importar-votantes');
    
    // Cargos routes
    Route::resource('cargos', CargoController::class);
    Route::get('cargos-tree', [CargoController::class, 'getTree'])->name('cargos.tree');
    Route::get('cargos-for-convocatorias', [CargoController::class, 'getCargosForConvocatorias'])->name('cargos.for-convocatorias');
    
    // Periodos Electorales routes
    Route::resource('periodos-electorales', PeriodoElectoralController::class);
    Route::get('periodos-disponibles', [PeriodoElectoralController::class, 'getPeriodosDisponibles'])->name('periodos.disponibles');
    Route::get('periodos-por-estado/{estado}', [PeriodoElectoralController::class, 'getPeriodosPorEstado'])->name('periodos.por-estado');
    
    // Asambleas routes
    Route::resource('asambleas', AsambleaController::class);
    Route::match(['GET', 'POST', 'DELETE', 'PUT'], 'asambleas/{asamblea}/participantes', [AsambleaController::class, 'manageParticipantes'])
        ->name('asambleas.manage-participantes');
    
    // Convocatorias routes
    Route::resource('convocatorias', ConvocatoriaController::class);
    Route::get('convocatorias-disponibles', [ConvocatoriaController::class, 'getConvocatoriasDisponibles'])->name('convocatorias.disponibles');
    Route::get('convocatorias-por-estado/{estado}', [ConvocatoriaController::class, 'getConvocatoriasPorEstado'])->name('convocatorias.por-estado');
    
    // Candidaturas admin routes - Rutas específicas ANTES del resource
    Route::get('candidaturas/configuracion', [AdminCandidaturaController::class, 'configuracion'])->name('candidaturas.configuracion');
    Route::post('candidaturas/configuracion', [AdminCandidaturaController::class, 'guardarConfiguracion'])->name('candidaturas.guardar-configuracion');
    Route::post('candidaturas/configuracion/{configuracion}/activar', [AdminCandidaturaController::class, 'activarConfiguracion'])->name('candidaturas.activar-configuracion');
    Route::get('candidaturas-por-estado/{estado}', [AdminCandidaturaController::class, 'getCandidaturasPorEstado'])->name('candidaturas.por-estado');
    Route::get('candidaturas-configuracion-activa', [AdminCandidaturaController::class, 'getConfiguracionActiva'])->name('candidaturas.configuracion-activa');
    Route::get('candidaturas-estadisticas', [AdminCandidaturaController::class, 'getEstadisticas'])->name('candidaturas.estadisticas');
    
    // Resource routes después de las rutas específicas
    Route::resource('candidaturas', AdminCandidaturaController::class)->only(['index', 'show']);
    Route::get('candidaturas/{candidatura}/historial', [AdminCandidaturaController::class, 'historial'])->name('candidaturas.historial');
    Route::post('candidaturas/{candidatura}/aprobar', [AdminCandidaturaController::class, 'aprobar'])->name('candidaturas.aprobar');
    Route::post('candidaturas/{candidatura}/rechazar', [AdminCandidaturaController::class, 'rechazar'])->name('candidaturas.rechazar');
    Route::post('candidaturas/{candidatura}/volver-borrador', [AdminCandidaturaController::class, 'volverABorrador'])->name('candidaturas.volver-borrador');
    
    // Rutas para aprobación de campos individuales
    Route::post('candidaturas/{candidatura}/campos/{campoId}/aprobar', [AdminCandidaturaController::class, 'aprobarCampo'])->name('candidaturas.aprobar-campo');
    Route::post('candidaturas/{candidatura}/campos/{campoId}/rechazar', [AdminCandidaturaController::class, 'rechazarCampo'])->name('candidaturas.rechazar-campo');
    Route::get('candidaturas/{candidatura}/estado-aprobacion-campos', [AdminCandidaturaController::class, 'getEstadoAprobacionCampos'])->name('candidaturas.estado-campos');
    Route::post('candidaturas/{candidatura}/aprobar-global', [AdminCandidaturaController::class, 'aprobarGlobal'])->name('candidaturas.aprobar-global');
    
    // Postulaciones admin routes  
    Route::resource('postulaciones', AdminPostulacionController::class)->only(['index', 'show'])->parameters([
        'postulaciones' => 'postulacion'
    ]);
    Route::post('postulaciones/{postulacion}/cambiar-estado', [AdminPostulacionController::class, 'cambiarEstado'])->name('postulaciones.cambiar-estado');
    Route::get('postulaciones-reportes', [AdminPostulacionController::class, 'reportes'])->name('postulaciones.reportes');
    Route::get('postulaciones-estadisticas', [AdminPostulacionController::class, 'estadisticas'])->name('postulaciones.estadisticas');
    Route::get('postulaciones-por-estado/{estado}', [AdminPostulacionController::class, 'porEstado'])->name('postulaciones.por-estado');
    Route::get('postulaciones-exportar', [AdminPostulacionController::class, 'exportar'])->name('postulaciones.exportar');
    
    // Import routes
    Route::get('imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::get('imports/{import}/status', [ImportController::class, 'status'])->name('imports.status');
    Route::get('votaciones/{votacion}/imports', [ImportController::class, 'index'])->name('votaciones.imports');
    Route::get('votaciones/{votacion}/imports/recent', [ImportController::class, 'recent'])->name('votaciones.imports.recent');
    Route::get('votaciones/{votacion}/imports/active', [ImportController::class, 'active'])->name('votaciones.imports.active');
    
    // Configuration routes
    Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    
    // Users management routes
    Route::resource('usuarios', UserController::class)->except(['show']);
    Route::post('usuarios/{usuario}/toggle-active', [UserController::class, 'toggleActive'])->name('usuarios.toggle-active');
    
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
