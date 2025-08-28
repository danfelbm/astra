<?php

use App\Http\Controllers\Elecciones\Admin\CandidaturaController as AdminCandidaturaController;
use App\Http\Controllers\Elecciones\Admin\CargoController;
use App\Http\Controllers\Configuration\Admin\ConfiguracionController;
use App\Http\Controllers\Elecciones\Admin\ConvocatoriaController;
use App\Http\Controllers\Geografico\Admin\GeographicController;
use App\Http\Controllers\Imports\Admin\ImportController;
use App\Http\Controllers\Configuration\Admin\OTPDashboardController;
use App\Http\Controllers\Elecciones\Admin\PeriodoElectoralController;
use App\Http\Controllers\Elecciones\Admin\PostulacionController as AdminPostulacionController;
use App\Http\Controllers\Asamblea\Admin\AsambleaController;
use App\Http\Controllers\Asamblea\User\ZoomRedirectController;
use App\Http\Controllers\Votaciones\Admin\VotacionController;
use App\Http\Controllers\Users\Admin\UserController;
use App\Http\Controllers\Tenant\Admin\TenantController;
use App\Http\Controllers\Rbac\Admin\RoleController;
use App\Http\Controllers\Rbac\Admin\SegmentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes (Administrative Panel)
|--------------------------------------------------------------------------
|
| Rutas administrativas que requieren permisos de administración.
| Solo accesibles para usuarios con roles administrativos.
|
*/Route::get('admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->middleware(['auth', 'verified', 'admin'])->name('admin.dashboard');

// Admin routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Tenants routes (solo super admin) - Expandido para usar Spatie
    Route::get('tenants', [TenantController::class, 'index'])
        ->middleware('can:tenants.view')
        ->name('tenants.index');
    Route::get('tenants/create', [TenantController::class, 'create'])
        ->middleware('can:tenants.create')
        ->name('tenants.create');
    Route::post('tenants', [TenantController::class, 'store'])
        ->middleware('can:tenants.create')
        ->name('tenants.store');
    Route::get('tenants/{tenant}', [TenantController::class, 'show'])
        ->middleware('can:tenants.view')
        ->name('tenants.show');
    Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])
        ->middleware('can:tenants.edit')
        ->name('tenants.edit');
    Route::put('tenants/{tenant}', [TenantController::class, 'update'])
        ->middleware('can:tenants.edit')
        ->name('tenants.update');
    Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
        ->middleware('can:tenants.delete')
        ->name('tenants.destroy');
    Route::post('tenants/switch', [TenantController::class, 'switch'])
        ->middleware('can:tenants.switch')
        ->name('tenants.switch');
    
    // Roles routes - Expandido para usar Spatie
    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('can:roles.view')
        ->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])
        ->middleware('can:roles.create')
        ->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('can:roles.create')
        ->name('roles.store');
    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('can:roles.view')
        ->name('roles.show');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('can:roles.edit')
        ->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('can:roles.edit')
        ->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('can:roles.delete')
        ->name('roles.destroy');
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])
        ->middleware('can:roles.view')
        ->name('roles.permissions');
    Route::post('roles/{role}/segments', [RoleController::class, 'attachSegments'])
        ->middleware('can:roles.edit')
        ->name('roles.attach-segments');
    
    // Segments routes - Expandido para usar Spatie
    Route::get('segments', [SegmentController::class, 'index'])
        ->middleware('can:segments.view')
        ->name('segments.index');
    Route::get('segments/create', [SegmentController::class, 'create'])
        ->middleware('can:segments.create')
        ->name('segments.create');
    Route::post('segments', [SegmentController::class, 'store'])
        ->middleware('can:segments.create')
        ->name('segments.store');
    Route::get('segments/{segment}', [SegmentController::class, 'show'])
        ->middleware('can:segments.view')
        ->name('segments.show');
    Route::get('segments/{segment}/edit', [SegmentController::class, 'edit'])
        ->middleware('can:segments.edit')
        ->name('segments.edit');
    Route::put('segments/{segment}', [SegmentController::class, 'update'])
        ->middleware('can:segments.edit')
        ->name('segments.update');
    Route::delete('segments/{segment}', [SegmentController::class, 'destroy'])
        ->middleware('can:segments.delete')
        ->name('segments.destroy');
    Route::post('segments/{segment}/evaluate', [SegmentController::class, 'evaluate'])
        ->middleware('can:segments.edit')
        ->name('segments.evaluate');
    Route::post('segments/{segment}/clear-cache', [SegmentController::class, 'clearCache'])
        ->middleware('can:segments.edit')
        ->name('segments.clear-cache');
    
    // OTP Dashboard routes
    Route::get('otp-dashboard', [\App\Http\Controllers\Configuration\Admin\OTPDashboardController::class, 'index'])
        ->middleware('can:dashboard.admin')
        ->name('otp-dashboard');
    Route::get('api/otp-dashboard/queue-status', [\App\Http\Controllers\Configuration\Admin\OTPDashboardController::class, 'queueStatus'])
        ->middleware('can:dashboard.admin')
        ->name('api.otp-dashboard.queue-status');
    Route::get('api/otp-dashboard/otp-stats', [\App\Http\Controllers\Configuration\Admin\OTPDashboardController::class, 'otpStats'])
        ->middleware('can:dashboard.admin')
        ->name('api.otp-dashboard.otp-stats');
    Route::get('api/otp-dashboard/queue/{queueName}/details', [\App\Http\Controllers\Configuration\Admin\OTPDashboardController::class, 'queueDetails'])
        ->middleware('can:queues.manage')
        ->name('api.otp-dashboard.queue-details');
    Route::post('api/otp-dashboard/retry-failed-jobs', [\App\Http\Controllers\Configuration\Admin\OTPDashboardController::class, 'retryFailedJobs'])
        ->middleware('can:queues.manage')
        ->name('api.otp-dashboard.retry-failed');
    Route::post('api/otp-dashboard/clean-failed-jobs', [\App\Http\Controllers\Configuration\Admin\OTPDashboardController::class, 'cleanFailedJobs'])
        ->middleware('can:queues.manage')
        ->name('api.otp-dashboard.clean-failed');
    
    // Rutas de votaciones con middlewares específicos de Spatie
    Route::get('votaciones', [VotacionController::class, 'index'])
        ->middleware('can:votaciones.view')
        ->name('votaciones.index');
    Route::get('votaciones/create', [VotacionController::class, 'create'])
        ->middleware('can:votaciones.create')
        ->name('votaciones.create');
    Route::post('votaciones', [VotacionController::class, 'store'])
        ->middleware('can:votaciones.create')
        ->name('votaciones.store');
    Route::get('votaciones/{votacione}/edit', [VotacionController::class, 'edit'])
        ->middleware('can:votaciones.edit')
        ->name('votaciones.edit');
    Route::put('votaciones/{votacione}', [VotacionController::class, 'update'])
        ->middleware('can:votaciones.edit')
        ->name('votaciones.update');
    Route::delete('votaciones/{votacione}', [VotacionController::class, 'destroy'])
        ->middleware('can:votaciones.delete')
        ->name('votaciones.destroy');
    Route::post('votaciones/{votacione}/toggle-status', [VotacionController::class, 'toggleStatus'])
        ->middleware('can:votaciones.edit')
        ->name('votaciones.toggle-status');
    Route::match(['GET', 'POST', 'DELETE'], 'votaciones/{votacione}/votantes', [VotacionController::class, 'manageVotantes'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.manage-votantes');
    Route::get('votaciones/{votacione}/search-users', [VotacionController::class, 'searchUsers'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.search-users');
    Route::post('votaciones/{votacione}/importar-votantes', [VotacionController::class, 'importarVotantes'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.importar-votantes');
    
    // Cargos routes - Expandido para usar Spatie
    Route::get('cargos', [CargoController::class, 'index'])
        ->middleware('can:cargos.view')
        ->name('cargos.index');
    Route::get('cargos/create', [CargoController::class, 'create'])
        ->middleware('can:cargos.create')
        ->name('cargos.create');
    Route::post('cargos', [CargoController::class, 'store'])
        ->middleware('can:cargos.create')
        ->name('cargos.store');
    Route::get('cargos/{cargo}/edit', [CargoController::class, 'edit'])
        ->middleware('can:cargos.edit')
        ->name('cargos.edit');
    Route::put('cargos/{cargo}', [CargoController::class, 'update'])
        ->middleware('can:cargos.edit')
        ->name('cargos.update');
    Route::delete('cargos/{cargo}', [CargoController::class, 'destroy'])
        ->middleware('can:cargos.delete')
        ->name('cargos.destroy');
    Route::get('cargos-tree', [CargoController::class, 'getTree'])
        ->middleware('can:cargos.view')
        ->name('cargos.tree');
    Route::get('cargos-for-convocatorias', [CargoController::class, 'getCargosForConvocatorias'])
        ->middleware('can:cargos.view')
        ->name('cargos.for-convocatorias');
    
    // Periodos Electorales routes
    // Aplicar middlewares específicos por acción del resource
    Route::get('periodos-electorales', [PeriodoElectoralController::class, 'index'])
        ->middleware(['auth', 'can:periodos.view'])
        ->name('periodos-electorales.index');
    Route::get('periodos-electorales/create', [PeriodoElectoralController::class, 'create'])
        ->middleware(['auth', 'can:periodos.create'])
        ->name('periodos-electorales.create');
    Route::post('periodos-electorales', [PeriodoElectoralController::class, 'store'])
        ->middleware(['auth', 'can:periodos.create'])
        ->name('periodos-electorales.store');
    Route::get('periodos-electorales/{periodosElectorale}/edit', [PeriodoElectoralController::class, 'edit'])
        ->middleware(['auth', 'can:periodos.edit'])
        ->name('periodos-electorales.edit');
    Route::put('periodos-electorales/{periodosElectorale}', [PeriodoElectoralController::class, 'update'])
        ->middleware(['auth', 'can:periodos.edit'])
        ->name('periodos-electorales.update');
    Route::delete('periodos-electorales/{periodosElectorale}', [PeriodoElectoralController::class, 'destroy'])
        ->middleware(['auth', 'can:periodos.delete'])
        ->name('periodos-electorales.destroy');
    Route::get('periodos-disponibles', [PeriodoElectoralController::class, 'getPeriodosDisponibles'])
        ->middleware(['auth', 'can:periodos.view'])
        ->name('periodos.disponibles');
    Route::get('periodos-por-estado/{estado}', [PeriodoElectoralController::class, 'getPeriodosPorEstado'])
        ->middleware(['auth', 'can:periodos.view'])
        ->name('periodos.por-estado');
    
    // Asambleas routes - Expandido para usar Spatie
    Route::get('asambleas', [AsambleaController::class, 'index'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.index');
    Route::get('asambleas/create', [AsambleaController::class, 'create'])
        ->middleware('can:asambleas.create')
        ->name('asambleas.create');
    Route::post('asambleas', [AsambleaController::class, 'store'])
        ->middleware('can:asambleas.create')
        ->name('asambleas.store');
    Route::get('asambleas/{asamblea}', [AsambleaController::class, 'show'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.show');
    Route::get('asambleas/{asamblea}/edit', [AsambleaController::class, 'edit'])
        ->middleware('can:asambleas.edit')
        ->name('asambleas.edit');
    Route::put('asambleas/{asamblea}', [AsambleaController::class, 'update'])
        ->middleware('can:asambleas.edit')
        ->name('asambleas.update');
    Route::delete('asambleas/{asamblea}', [AsambleaController::class, 'destroy'])
        ->middleware('can:asambleas.delete')
        ->name('asambleas.destroy');
    
    Route::match(['GET', 'POST', 'DELETE', 'PUT'], 'asambleas/{asamblea}/participantes', [AsambleaController::class, 'manageParticipantes'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.manage-participantes');
    Route::get('asambleas/{asamblea}/participantes-list', [AsambleaController::class, 'getParticipantes'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.participantes-list');
    
    // Rutas de importación para asambleas
    Route::get('asambleas/{asamblea}/imports', [ImportController::class, 'indexForAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.index');
    Route::get('asambleas/{asamblea}/imports/recent', [ImportController::class, 'recentForAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.recent');
    Route::get('asambleas/{asamblea}/imports/active', [ImportController::class, 'activeForAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.active');
    Route::post('asambleas/{asamblea}/imports/store', [ImportController::class, 'storeWithAsamblea'])
        ->middleware('can:asambleas.manage_participants')
        ->name('asambleas.imports.store');
    
    // Convocatorias routes
    Route::get('convocatorias', [ConvocatoriaController::class, 'index'])
        ->middleware('can:convocatorias.view')
        ->name('convocatorias.index');
    Route::get('convocatorias/create', [ConvocatoriaController::class, 'create'])
        ->middleware('can:convocatorias.create')
        ->name('convocatorias.create');
    Route::post('convocatorias', [ConvocatoriaController::class, 'store'])
        ->middleware('can:convocatorias.create')
        ->name('convocatorias.store');
    Route::get('convocatorias/{convocatoria}', [ConvocatoriaController::class, 'show'])
        ->middleware('can:convocatorias.view')
        ->name('convocatorias.show');
    Route::get('convocatorias/{convocatoria}/edit', [ConvocatoriaController::class, 'edit'])
        ->middleware('can:convocatorias.edit')
        ->name('convocatorias.edit');
    Route::put('convocatorias/{convocatoria}', [ConvocatoriaController::class, 'update'])
        ->middleware('can:convocatorias.edit')
        ->name('convocatorias.update');
    Route::delete('convocatorias/{convocatoria}', [ConvocatoriaController::class, 'destroy'])
        ->middleware('can:convocatorias.delete')
        ->name('convocatorias.destroy');
    Route::get('convocatorias-disponibles', [ConvocatoriaController::class, 'getConvocatoriasDisponibles'])
        ->middleware('can:convocatorias.view')
        ->name('convocatorias.disponibles');
    Route::get('convocatorias-por-estado/{estado}', [ConvocatoriaController::class, 'getConvocatoriasPorEstado'])
        ->middleware('can:convocatorias.view')
        ->name('convocatorias.por-estado');
    
    // Dashboard de Candidaturas
    Route::get('candidaturas-dashboard', function () {
        return Inertia::render('Admin/CandidaturasDashboard');
    })->middleware('can:candidaturas.view')
      ->name('candidaturas.dashboard');

    // Candidaturas admin routes - Rutas específicas ANTES del resource
    Route::get('candidaturas/configuracion', [AdminCandidaturaController::class, 'configuracion'])
        ->middleware('can:candidaturas.configuracion')
        ->name('candidaturas.configuracion');
    Route::post('candidaturas/configuracion', [AdminCandidaturaController::class, 'guardarConfiguracion'])
        ->middleware('can:candidaturas.configuracion')
        ->name('candidaturas.guardar-configuracion');
    Route::post('candidaturas/configuracion/{configuracion}/activar', [AdminCandidaturaController::class, 'activarConfiguracion'])
        ->middleware('can:candidaturas.configuracion')
        ->name('candidaturas.activar-configuracion');
    Route::get('candidaturas-por-estado/{estado}', [AdminCandidaturaController::class, 'getCandidaturasPorEstado'])
        ->middleware('can:candidaturas.view')
        ->name('candidaturas.por-estado');
    Route::get('candidaturas-configuracion-activa', [AdminCandidaturaController::class, 'getConfiguracionActiva'])
        ->middleware('can:candidaturas.view')
        ->name('candidaturas.configuracion-activa');
    Route::get('candidaturas-estadisticas', [AdminCandidaturaController::class, 'getEstadisticas'])
        ->middleware('can:candidaturas.view')
        ->name('candidaturas.estadisticas');
    
    // Resource routes después de las rutas específicas
    Route::resource('candidaturas', AdminCandidaturaController::class)
        ->only(['index', 'show'])
        ->middleware('can:candidaturas.view');
    Route::get('candidaturas/{candidatura}/historial', [AdminCandidaturaController::class, 'historial'])
        ->middleware('can:candidaturas.view')
        ->name('candidaturas.historial');
    Route::post('candidaturas/{candidatura}/aprobar', [AdminCandidaturaController::class, 'aprobar'])
        ->middleware('can:candidaturas.approve')
        ->name('candidaturas.aprobar');
    Route::post('candidaturas/{candidatura}/rechazar', [AdminCandidaturaController::class, 'rechazar'])
        ->middleware('can:candidaturas.reject')
        ->name('candidaturas.rechazar');
    Route::post('candidaturas/{candidatura}/volver-borrador', [AdminCandidaturaController::class, 'volverABorrador'])
        ->middleware('can:candidaturas.approve')
        ->name('candidaturas.volver-borrador');
    Route::post('candidaturas/{candidatura}/toggle-subsanar', [AdminCandidaturaController::class, 'toggleSubsanar'])
        ->middleware('can:candidaturas.approve')
        ->name('candidaturas.toggle-subsanar');
    
    // Rutas para comentarios
    Route::post('candidaturas/{candidatura}/comentarios', [AdminCandidaturaController::class, 'updateComentarios'])
        ->middleware('can:candidaturas.comment')
        ->name('candidaturas.comentarios.store');
    Route::get('candidaturas/{candidatura}/comentarios', [AdminCandidaturaController::class, 'getComentarios'])
        ->middleware('can:candidaturas.view')
        ->name('candidaturas.comentarios.index');
    
    // Rutas para aprobación de campos individuales
    Route::post('candidaturas/{candidatura}/campos/{campoId}/aprobar', [AdminCandidaturaController::class, 'aprobarCampo'])
        ->middleware('can:candidaturas.aprobar_campos')
        ->name('candidaturas.aprobar-campo');
    Route::post('candidaturas/{candidatura}/campos/{campoId}/rechazar', [AdminCandidaturaController::class, 'rechazarCampo'])
        ->middleware('can:candidaturas.aprobar_campos')
        ->name('candidaturas.rechazar-campo');
    Route::get('candidaturas/{candidatura}/estado-aprobacion-campos', [AdminCandidaturaController::class, 'getEstadoAprobacionCampos'])
        ->middleware('can:candidaturas.view')
        ->name('candidaturas.estado-campos');
    Route::post('candidaturas/{candidatura}/aprobar-global', [AdminCandidaturaController::class, 'aprobarGlobal'])
        ->middleware('can:candidaturas.approve')
        ->name('candidaturas.aprobar-global');
    
    // Rutas para recordatorios masivos de candidaturas
    Route::post('candidaturas/recordatorios/enviar', [AdminCandidaturaController::class, 'enviarRecordatoriosBorrador'])
        ->middleware('can:candidaturas.recordatorios')
        ->name('candidaturas.enviar-recordatorios');
    Route::get('candidaturas/recordatorios/estadisticas', [AdminCandidaturaController::class, 'getEstadisticasBorrador'])
        ->middleware('can:candidaturas.recordatorios')
        ->name('candidaturas.estadisticas-borradores');
    
    // Rutas para notificaciones de candidaturas pendientes
    Route::post('candidaturas/notificaciones/enviar', [AdminCandidaturaController::class, 'enviarNotificacionesPendientes'])
        ->middleware('can:candidaturas.notificaciones')
        ->name('candidaturas.enviar-notificaciones');
    Route::get('candidaturas/notificaciones/estadisticas', [AdminCandidaturaController::class, 'getEstadisticasPendientes'])
        ->middleware('can:candidaturas.notificaciones')
        ->name('candidaturas.estadisticas-pendientes');
    
    // Postulaciones admin routes  
    // Resource expandido de postulaciones
    Route::get('postulaciones', [AdminPostulacionController::class, 'index'])
        ->middleware('can:postulaciones.view')
        ->name('postulaciones.index');
    Route::get('postulaciones/{postulacion}', [AdminPostulacionController::class, 'show'])
        ->middleware('can:postulaciones.view')
        ->name('postulaciones.show');
    
    // Rutas adicionales de postulaciones
    Route::post('postulaciones/{postulacion}/cambiar-estado', [AdminPostulacionController::class, 'cambiarEstado'])
        ->middleware('can:postulaciones.review')
        ->name('postulaciones.cambiar-estado');
    Route::get('postulaciones-reportes', [AdminPostulacionController::class, 'reportes'])
        ->middleware('can:postulaciones.view')
        ->name('postulaciones.reportes');
    Route::get('postulaciones-estadisticas', [AdminPostulacionController::class, 'estadisticas'])
        ->middleware('can:postulaciones.view')
        ->name('postulaciones.estadisticas');
    Route::get('postulaciones-por-estado/{estado}', [AdminPostulacionController::class, 'porEstado'])
        ->middleware('can:postulaciones.view')
        ->name('postulaciones.por-estado');
    Route::get('postulaciones-exportar', [AdminPostulacionController::class, 'exportar'])
        ->middleware('can:postulaciones.view')
        ->name('postulaciones.exportar');
    
    // Formularios admin routes - 8 rutas (7 CRUD + 1 export)
    Route::get('formularios', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'index'])
        ->middleware('can:formularios.view')
        ->name('formularios.index');
    Route::get('formularios/create', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'create'])
        ->middleware('can:formularios.create')
        ->name('formularios.create');
    Route::post('formularios', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'store'])
        ->middleware('can:formularios.create')
        ->name('formularios.store');
    Route::get('formularios/{formulario}', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'show'])
        ->middleware('can:formularios.view')
        ->name('formularios.show');
    Route::get('formularios/{formulario}/edit', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'edit'])
        ->middleware('can:formularios.edit')
        ->name('formularios.edit');
    Route::put('formularios/{formulario}', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'update'])
        ->middleware('can:formularios.edit')
        ->name('formularios.update');
    Route::delete('formularios/{formulario}', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'destroy'])
        ->middleware('can:formularios.delete')
        ->name('formularios.destroy');
    
    // Ruta adicional de exportación
    Route::get('formularios/{formulario}/exportar', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'exportarRespuestas'])
        ->middleware('can:formularios.export')
        ->name('formularios.exportar');
    
    // Gestión de permisos de formularios
    Route::get('formularios/{formulario}/permisos', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'managePermissions'])
        ->middleware('can:formularios.manage_permissions')
        ->name('formularios.permisos');
    Route::put('formularios/{formulario}/permisos', [\App\Http\Controllers\Formularios\Admin\FormularioController::class, 'updatePermissions'])
        ->middleware('can:formularios.manage_permissions')
        ->name('formularios.permisos.update');
    
    // Categorías de formularios (pendiente de implementar)
    // Route::resource('formulario-categorias', \App\Http\Controllers\Admin\FormularioCategoriaController::class)
    //     ->middleware('permission'); // El middleware inferirá el permiso de la acción
    
    // Import routes - General (usuarios)
    Route::get('imports', [ImportController::class, 'indexGeneral'])
        ->middleware('can:users.import')
        ->name('imports.index');
    Route::get('imports/create', [ImportController::class, 'create'])
        ->middleware('can:users.import')
        ->name('imports.create');
    Route::post('imports', [ImportController::class, 'store'])
        ->middleware('can:users.import')
        ->name('imports.store');
    Route::post('imports/analyze', [ImportController::class, 'analyze'])
        ->middleware('can:users.import')
        ->name('imports.analyze');
    Route::post('imports/{import}/resolve-conflict', [ImportController::class, 'resolveConflict'])
        ->middleware('can:users.import')
        ->name('imports.resolve-conflict');
    Route::post('imports/{import}/refresh-conflict-data', [ImportController::class, 'refreshConflictData'])
        ->middleware('can:users.import')
        ->name('imports.refresh-conflict-data');
    
    // Import routes - Específicas
    Route::get('imports/{import}', [ImportController::class, 'show'])
        ->middleware('can:users.import')
        ->name('imports.show');
    Route::get('imports/{import}/status', [ImportController::class, 'status'])
        ->middleware('can:users.import')
        ->name('imports.status');
    Route::get('votaciones/{votacion}/imports', [ImportController::class, 'index'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.imports');
    Route::get('votaciones/{votacion}/imports/recent', [ImportController::class, 'recent'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.imports.recent');
    Route::get('votaciones/{votacion}/imports/active', [ImportController::class, 'active'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.imports.active');
    Route::post('votaciones/{votacion}/imports/store', [ImportController::class, 'storeWithVotacion'])
        ->middleware('can:votaciones.manage_voters')
        ->name('votaciones.imports.store');
    
    // Configuration routes - Actualizado para usar Spatie
    Route::get('configuracion', [ConfiguracionController::class, 'index'])
        ->middleware('can:settings.view')
        ->name('configuracion.index');
    Route::post('configuracion', [ConfiguracionController::class, 'update'])
        ->middleware('can:settings.edit')
        ->name('configuracion.update');
    Route::post('configuracion/candidaturas', [ConfiguracionController::class, 'updateCandidaturas'])
        ->middleware('can:settings.edit')
        ->name('configuracion.update.candidaturas');
    
    // Users management routes - Expandido para usar Spatie
    Route::get('usuarios', [UserController::class, 'index'])
        ->middleware('can:users.view')
        ->name('usuarios.index');
    Route::get('usuarios/create', [UserController::class, 'create'])
        ->middleware('can:users.create')
        ->name('usuarios.create');
    Route::post('usuarios', [UserController::class, 'store'])
        ->middleware('can:users.create')
        ->name('usuarios.store');
    Route::get('usuarios/{usuario}/edit', [UserController::class, 'edit'])
        ->middleware('can:users.edit')
        ->name('usuarios.edit');
    Route::put('usuarios/{usuario}', [UserController::class, 'update'])
        ->middleware('can:users.edit')
        ->name('usuarios.update');
    Route::delete('usuarios/{usuario}', [UserController::class, 'destroy'])
        ->middleware('can:users.delete')
        ->name('usuarios.destroy');
    Route::post('usuarios/{usuario}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('can:users.edit')
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
