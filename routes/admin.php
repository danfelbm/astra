<?php

use Modules\Elecciones\Http\Controllers\Admin\CandidaturaController as AdminCandidaturaController;
use Modules\Elecciones\Http\Controllers\Admin\CargoController;
use Modules\Configuration\Http\Controllers\Admin\ConfiguracionController;
use Modules\Elecciones\Http\Controllers\Admin\ConvocatoriaController;
use Modules\Geografico\Http\Controllers\Admin\GeographicController;
use Modules\Imports\Http\Controllers\Admin\ImportController;
use Modules\Configuration\Http\Controllers\Admin\OTPDashboardController;
use Modules\Elecciones\Http\Controllers\Admin\PeriodoElectoralController;
use Modules\Elecciones\Http\Controllers\Admin\PostulacionController as AdminPostulacionController;
use Modules\Asamblea\Http\Controllers\Admin\AsambleaController;
use Modules\Asamblea\Http\Controllers\User\ZoomRedirectController;
use Modules\Votaciones\Http\Controllers\Admin\VotacionController;
use Modules\Users\Http\Controllers\Admin\UserController;
use Modules\Tenant\Http\Controllers\Admin\TenantController;
use Modules\Rbac\Http\Controllers\Admin\RoleController;
use Modules\Rbac\Http\Controllers\Admin\SegmentController;
use Modules\Campanas\Http\Controllers\Admin\PlantillaEmailController;
use Modules\Campanas\Http\Controllers\Admin\PlantillaWhatsAppController;
use Modules\Campanas\Http\Controllers\Admin\CampanaController;
use Modules\Proyectos\Http\Controllers\Admin\ProyectoController;
use Modules\Proyectos\Http\Controllers\Admin\CampoPersonalizadoController;
use Modules\Proyectos\Http\Controllers\Admin\CategoriaEtiquetaController;
use Modules\Proyectos\Http\Controllers\Admin\EtiquetaController;
use Modules\Proyectos\Http\Controllers\Admin\ProyectoEtiquetaController;
use Modules\Proyectos\Http\Controllers\Admin\ContratoController;
use Modules\Proyectos\Http\Controllers\Admin\ObligacionContratoController;
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
    
    // Segments routes
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
    Route::get('otp-dashboard', [\Modules\Configuration\Http\Controllers\Admin\OTPDashboardController::class, 'index'])
        ->middleware('can:dashboard.admin')
        ->name('otp-dashboard');
    Route::get('api/otp-dashboard/queue-status', [\Modules\Configuration\Http\Controllers\Admin\OTPDashboardController::class, 'queueStatus'])
        ->middleware('can:dashboard.admin')
        ->name('api.otp-dashboard.queue-status');
    Route::get('api/otp-dashboard/otp-stats', [\Modules\Configuration\Http\Controllers\Admin\OTPDashboardController::class, 'otpStats'])
        ->middleware('can:dashboard.admin')
        ->name('api.otp-dashboard.otp-stats');
    Route::get('api/otp-dashboard/queue/{queueName}/details', [\Modules\Configuration\Http\Controllers\Admin\OTPDashboardController::class, 'queueDetails'])
        ->middleware('can:queues.manage')
        ->name('api.otp-dashboard.queue-details');
    Route::post('api/otp-dashboard/retry-failed-jobs', [\Modules\Configuration\Http\Controllers\Admin\OTPDashboardController::class, 'retryFailedJobs'])
        ->middleware('can:queues.manage')
        ->name('api.otp-dashboard.retry-failed');
    Route::post('api/otp-dashboard/clean-failed-jobs', [\Modules\Configuration\Http\Controllers\Admin\OTPDashboardController::class, 'cleanFailedJobs'])
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
    Route::get('votaciones/{votacione}/assigned-voters', [VotacionController::class, 'getAssignedVoters'])
        ->middleware('can:votaciones.view')
        ->name('votaciones.assigned-voters');
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
    
    // Rutas de votaciones asociadas a asambleas
    Route::get('asambleas/{asamblea}/votaciones', [AsambleaController::class, 'getVotaciones'])
        ->middleware('can:asambleas.view')
        ->name('asambleas.votaciones');
    Route::post('asambleas/{asamblea}/votaciones/{votacion}/sync', [AsambleaController::class, 'syncParticipantsToVotacion'])
        ->middleware('can:asambleas.sync_participants')
        ->name('asambleas.sync-participants');
    Route::get('sync-job/{jobId}/status', [AsambleaController::class, 'getSyncJobStatus'])
        ->middleware('can:asambleas.view')
        ->name('sync-job.status');
    
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
    Route::get('formularios', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'index'])
        ->middleware('can:formularios.view')
        ->name('formularios.index');
    Route::get('formularios/create', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'create'])
        ->middleware('can:formularios.create')
        ->name('formularios.create');
    Route::post('formularios', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'store'])
        ->middleware('can:formularios.create')
        ->name('formularios.store');
    Route::get('formularios/{formulario}', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'show'])
        ->middleware('can:formularios.view')
        ->name('formularios.show');
    Route::get('formularios/{formulario}/edit', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'edit'])
        ->middleware('can:formularios.edit')
        ->name('formularios.edit');
    Route::put('formularios/{formulario}', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'update'])
        ->middleware('can:formularios.edit')
        ->name('formularios.update');
    Route::delete('formularios/{formulario}', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'destroy'])
        ->middleware('can:formularios.delete')
        ->name('formularios.destroy');
    
    // Ruta adicional de exportación
    Route::get('formularios/{formulario}/exportar', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'exportarRespuestas'])
        ->middleware('can:formularios.export')
        ->name('formularios.exportar');
    
    // Gestión de permisos de formularios
    Route::get('formularios/{formulario}/permisos', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'managePermissions'])
        ->middleware('can:formularios.manage_permissions')
        ->name('formularios.permisos');
    Route::put('formularios/{formulario}/permisos', [\Modules\Formularios\Http\Controllers\Admin\FormularioController::class, 'updatePermissions'])
        ->middleware('can:formularios.manage_permissions')
        ->name('formularios.permisos.update');
    
    // Categorías de formularios (pendiente de implementar)
    // Route::resource('formulario-categorias', \Modules\Admin\Http\Controllers\FormularioCategoriaController::class)
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
    
    // Avatar management routes for admin
    Route::post('usuarios/{usuario}/avatar', [UserController::class, 'uploadAvatar'])
        ->middleware('can:users.edit')
        ->name('usuarios.avatar.upload');
    Route::delete('usuarios/{usuario}/avatar', [UserController::class, 'deleteAvatar'])
        ->middleware('can:users.edit')
        ->name('usuarios.avatar.delete');
    
    // User Update Requests management
    Route::prefix('solicitudes-actualizacion')->name('update-requests.')->controller(\Modules\Users\Http\Controllers\Admin\UserUpdateRequestController::class)->group(function () {
        Route::get('/', 'index')
            ->middleware('can:users.update_requests')
            ->name('index');
        Route::get('/{updateRequest}', 'show')
            ->middleware('can:users.update_requests')
            ->name('show');
        Route::post('/{updateRequest}/approve', 'approve')
            ->middleware('can:users.approve_updates')
            ->name('approve');
        Route::post('/{updateRequest}/reject', 'reject')
            ->middleware('can:users.approve_updates')
            ->name('reject');
        Route::get('/{updateRequest}/download', 'downloadDocument')
            ->middleware('can:users.update_requests')
            ->name('download');
        Route::get('/export/csv', 'export')
            ->middleware('can:users.export')
            ->name('export');
    });
    
    // Geographic routes for cascade selection
    Route::prefix('geographic')->name('geographic.')->group(function () {
        Route::get('territorios', [GeographicController::class, 'territorios'])->name('territorios');
        Route::get('departamentos', [GeographicController::class, 'departamentos'])->name('departamentos');
        Route::get('municipios', [GeographicController::class, 'municipios'])->name('municipios');
        Route::get('localidades', [GeographicController::class, 'localidades'])->name('localidades');
        Route::get('entidades-por-ids', [GeographicController::class, 'entidadesPorIds'])->name('entidades-por-ids');
    });
    
    // MÓDULO CAMPAÑAS - Rutas administrativas
    Route::prefix('campanas')->name('campanas.')->group(function () {
        
        // Rutas de Plantillas de Email
        Route::prefix('plantillas-email')->name('plantillas-email.')->group(function () {
            Route::get('/', [PlantillaEmailController::class, 'index'])->name('index')
                ->middleware('can:campanas.plantillas.view');
            
            Route::get('/create', [PlantillaEmailController::class, 'create'])->name('create')
                ->middleware('can:campanas.plantillas.create');
            
            Route::post('/', [PlantillaEmailController::class, 'store'])->name('store')
                ->middleware('can:campanas.plantillas.create');
            
            Route::get('/{plantillaEmail}', [PlantillaEmailController::class, 'show'])->name('show')
                ->middleware('can:campanas.plantillas.view');
            
            Route::get('/{plantillaEmail}/edit', [PlantillaEmailController::class, 'edit'])->name('edit')
                ->middleware('can:campanas.plantillas.edit');
            
            Route::put('/{plantillaEmail}', [PlantillaEmailController::class, 'update'])->name('update')
                ->middleware('can:campanas.plantillas.edit');
            
            Route::delete('/{plantillaEmail}', [PlantillaEmailController::class, 'destroy'])->name('destroy')
                ->middleware('can:campanas.plantillas.delete');
            
            Route::post('/{plantillaEmail}/duplicate', [PlantillaEmailController::class, 'duplicate'])->name('duplicate')
                ->middleware('can:campanas.plantillas.create');
            
            Route::post('/{plantillaEmail}/preview', [PlantillaEmailController::class, 'preview'])->name('preview')
                ->middleware('can:campanas.plantillas.view');
            
            Route::post('/validate', [PlantillaEmailController::class, 'validateTemplate'])->name('validate')
                ->middleware('can:campanas.plantillas.view');
            
            Route::get('/active/list', [PlantillaEmailController::class, 'getActive'])->name('active')
                ->middleware('can:campanas.plantillas.view');
        });
        
        // Rutas de Plantillas de WhatsApp
        Route::prefix('plantillas-whatsapp')->name('plantillas-whatsapp.')->group(function () {
            Route::get('/', [PlantillaWhatsAppController::class, 'index'])->name('index')
                ->middleware('can:campanas.plantillas.view');
            
            Route::get('/create', [PlantillaWhatsAppController::class, 'create'])->name('create')
                ->middleware('can:campanas.plantillas.create');
            
            Route::post('/', [PlantillaWhatsAppController::class, 'store'])->name('store')
                ->middleware('can:campanas.plantillas.create');
            
            Route::get('/{plantillaWhatsApp}', [PlantillaWhatsAppController::class, 'show'])->name('show')
                ->middleware('can:campanas.plantillas.view');
            
            Route::get('/{plantillaWhatsApp}/edit', [PlantillaWhatsAppController::class, 'edit'])->name('edit')
                ->middleware('can:campanas.plantillas.edit');
            
            Route::put('/{plantillaWhatsApp}', [PlantillaWhatsAppController::class, 'update'])->name('update')
                ->middleware('can:campanas.plantillas.edit');
            
            Route::delete('/{plantillaWhatsApp}', [PlantillaWhatsAppController::class, 'destroy'])->name('destroy')
                ->middleware('can:campanas.plantillas.delete');
            
            Route::post('/{plantillaWhatsApp}/duplicate', [PlantillaWhatsAppController::class, 'duplicate'])->name('duplicate')
                ->middleware('can:campanas.plantillas.create');
            
            Route::post('/{plantillaWhatsApp}/preview', [PlantillaWhatsAppController::class, 'preview'])->name('preview')
                ->middleware('can:campanas.plantillas.view');
            
            Route::post('/validate', [PlantillaWhatsAppController::class, 'validateTemplate'])->name('validate')
                ->middleware('can:campanas.plantillas.view');
            
            Route::get('/active/list', [PlantillaWhatsAppController::class, 'getActive'])->name('active')
                ->middleware('can:campanas.plantillas.view');
        });
        
        
        // Rutas de Campañas
        Route::get('/', [CampanaController::class, 'index'])->name('index')
            ->middleware('can:campanas.view');
        
        Route::get('/create', [CampanaController::class, 'create'])->name('create')
            ->middleware('can:campanas.create');
        
        Route::post('/', [CampanaController::class, 'store'])->name('store')
            ->middleware('can:campanas.create');
        
        Route::get('/stats', [CampanaController::class, 'stats'])->name('stats')
            ->middleware('can:campanas.view');
        
        Route::get('/{campana}', [CampanaController::class, 'show'])->name('show')
            ->middleware('can:campanas.view');
        
        Route::get('/{campana}/edit', [CampanaController::class, 'edit'])->name('edit')
            ->middleware('can:campanas.edit');
        
        Route::put('/{campana}', [CampanaController::class, 'update'])->name('update')
            ->middleware('can:campanas.edit');
        
        Route::delete('/{campana}', [CampanaController::class, 'destroy'])->name('destroy')
            ->middleware('can:campanas.delete');
        
        // Acciones de campaña
        Route::post('/{campana}/send', [CampanaController::class, 'send'])->name('send')
            ->middleware('can:campanas.send');
        
        Route::post('/{campana}/pause', [CampanaController::class, 'pause'])->name('pause')
            ->middleware('can:campanas.pause');
        
        Route::post('/{campana}/resume', [CampanaController::class, 'resume'])->name('resume')
            ->middleware('can:campanas.resume');
        
        Route::post('/{campana}/cancel', [CampanaController::class, 'cancel'])->name('cancel')
            ->middleware('can:campanas.cancel');
        
        Route::post('/{campana}/duplicate', [CampanaController::class, 'duplicate'])->name('duplicate')
            ->middleware('can:campanas.create');
        
        // Métricas y reportes
        Route::get('/{campana}/metrics', [CampanaController::class, 'metrics'])->name('metrics')
            ->middleware('can:campanas.view');
        
        Route::post('/{campana}/export', [CampanaController::class, 'export'])->name('export')
            ->middleware('can:campanas.export');
        
        Route::post('/{campana}/preview', [CampanaController::class, 'preview'])->name('preview')
            ->middleware('can:campanas.view');
    });

    // MÓDULO PROYECTOS - Rutas administrativas

    // Ruta para búsqueda de usuarios (DEBE estar ANTES del grupo para evitar conflictos con route model binding)
    Route::get('/proyectos-search-users', [ProyectoController::class, 'searchUsers'])
        ->name('proyectos.search-users')
        ->middleware('can:proyectos.view');

    Route::prefix('proyectos')->name('proyectos.')->group(function () {
        Route::get('/', [ProyectoController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.view');

        Route::get('/create', [ProyectoController::class, 'create'])
            ->name('create')
            ->middleware('can:proyectos.create');

        Route::post('/', [ProyectoController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.create');

        Route::get('/{proyecto}', [ProyectoController::class, 'show'])
            ->name('show')
            ->middleware('can:proyectos.view');

        Route::get('/{proyecto}/edit', [ProyectoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:proyectos.edit');

        Route::put('/{proyecto}', [ProyectoController::class, 'update'])
            ->name('update')
            ->middleware('can:proyectos.edit');

        Route::delete('/{proyecto}', [ProyectoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:proyectos.delete');

        // Rutas adicionales
        Route::post('/{proyecto}/toggle-status', [ProyectoController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('can:proyectos.edit');

        Route::post('/{proyecto}/asignar-responsable', [ProyectoController::class, 'asignarResponsable'])
            ->name('asignar-responsable')
            ->middleware('can:proyectos.edit');
    });

    // Rutas para gestión de campos personalizados
    Route::prefix('campos-personalizados')->name('campos-personalizados.')->group(function () {
        Route::get('/', [CampoPersonalizadoController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.manage_fields');

        Route::get('/create', [CampoPersonalizadoController::class, 'create'])
            ->name('create')
            ->middleware('can:proyectos.manage_fields');

        Route::post('/', [CampoPersonalizadoController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.manage_fields');

        Route::get('/{campo}', [CampoPersonalizadoController::class, 'show'])
            ->name('show')
            ->middleware('can:proyectos.manage_fields');

        Route::get('/{campo}/edit', [CampoPersonalizadoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:proyectos.manage_fields');

        Route::put('/{campo}', [CampoPersonalizadoController::class, 'update'])
            ->name('update')
            ->middleware('can:proyectos.manage_fields');

        Route::delete('/{campo}', [CampoPersonalizadoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:proyectos.manage_fields');

        Route::patch('/{campo}/toggle-activo', [CampoPersonalizadoController::class, 'toggleActivo'])
            ->name('toggle-activo')
            ->middleware('can:proyectos.manage_fields');

        Route::post('/reordenar', [CampoPersonalizadoController::class, 'reordenar'])
            ->name('reordenar')
            ->middleware('can:proyectos.manage_fields');
    });

    // Rutas para gestión de categorías de etiquetas
    Route::prefix('categorias-etiquetas')->name('categorias-etiquetas.')->group(function () {
        Route::get('/', [CategoriaEtiquetaController::class, 'index'])
            ->name('index')
            ->middleware('can:categorias_etiquetas.view');

        Route::get('/create', [CategoriaEtiquetaController::class, 'create'])
            ->name('create')
            ->middleware('can:categorias_etiquetas.create');

        Route::post('/', [CategoriaEtiquetaController::class, 'store'])
            ->name('store')
            ->middleware('can:categorias_etiquetas.create');

        Route::get('/{categoriaEtiqueta}', [CategoriaEtiquetaController::class, 'show'])
            ->name('show')
            ->middleware('can:categorias_etiquetas.view');

        Route::get('/{categoriaEtiqueta}/edit', [CategoriaEtiquetaController::class, 'edit'])
            ->name('edit')
            ->middleware('can:categorias_etiquetas.edit');

        Route::put('/{categoriaEtiqueta}', [CategoriaEtiquetaController::class, 'update'])
            ->name('update')
            ->middleware('can:categorias_etiquetas.edit');

        Route::delete('/{categoriaEtiqueta}', [CategoriaEtiquetaController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:categorias_etiquetas.delete');

        Route::patch('/{categoriaEtiqueta}/toggle-active', [CategoriaEtiquetaController::class, 'toggleActive'])
            ->name('toggle-active')
            ->middleware('can:categorias_etiquetas.edit');

        Route::post('/reorder', [CategoriaEtiquetaController::class, 'reorder'])
            ->name('reorder')
            ->middleware('can:categorias_etiquetas.edit');

        Route::post('/{categoriaEtiqueta}/merge', [CategoriaEtiquetaController::class, 'merge'])
            ->name('merge')
            ->middleware('can:categorias_etiquetas.delete');
    });

    // Rutas para gestión de etiquetas
    Route::prefix('etiquetas')->name('etiquetas.')->group(function () {
        Route::post('/', [EtiquetaController::class, 'store'])
            ->name('store')
            ->middleware('can:etiquetas.create');
        Route::put('/{etiqueta}', [EtiquetaController::class, 'update'])
            ->name('update')
            ->middleware('can:etiquetas.edit');
        Route::delete('/{etiqueta}', [EtiquetaController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:etiquetas.delete');
        Route::get('/search', [EtiquetaController::class, 'search'])
            ->name('search')
            ->middleware('can:etiquetas.view');
        Route::post('/{etiqueta}/increment-uso', [EtiquetaController::class, 'incrementUso'])
            ->name('increment-uso')
            ->middleware('can:etiquetas.view');
        Route::post('/reorder', [EtiquetaController::class, 'reorder'])
            ->name('reorder')
            ->middleware('can:etiquetas.edit');

        // Rutas para jerarquía de etiquetas
        Route::get('/arbol', [EtiquetaController::class, 'obtenerArbol'])
            ->name('arbol')
            ->middleware('can:etiquetas.view');
        Route::post('/{etiqueta}/establecer-padre', [EtiquetaController::class, 'establecerJerarquia'])
            ->name('establecer-padre')
            ->middleware('can:etiquetas.edit');
        Route::post('/{etiqueta}/mover', [EtiquetaController::class, 'mover'])
            ->name('mover')
            ->middleware('can:etiquetas.edit');
        Route::get('/para-selector', [EtiquetaController::class, 'paraSelector'])
            ->name('para-selector')
            ->middleware('can:etiquetas.view');
        Route::get('/{etiqueta}/camino', [EtiquetaController::class, 'camino'])
            ->name('camino')
            ->middleware('can:etiquetas.view');
        Route::get('/estadisticas-jerarquia', [EtiquetaController::class, 'estadisticasJerarquia'])
            ->name('estadisticas-jerarquia')
            ->middleware('can:etiquetas.view');
    });

    // Rutas para gestión de etiquetas en proyectos
    Route::prefix('proyectos/{proyecto}/etiquetas')->name('proyectos.etiquetas.')->group(function () {
        Route::get('/', [ProyectoEtiquetaController::class, 'index'])
            ->name('index')
            ->middleware('can:proyectos.manage_tags');

        Route::post('/', [ProyectoEtiquetaController::class, 'store'])
            ->name('store')
            ->middleware('can:proyectos.manage_tags');

        Route::delete('/{etiqueta}', [ProyectoEtiquetaController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:proyectos.manage_tags');

        Route::put('/sync', [ProyectoEtiquetaController::class, 'sync'])
            ->name('sync')
            ->middleware('can:proyectos.manage_tags');

        Route::get('/suggest', [ProyectoEtiquetaController::class, 'suggest'])
            ->name('suggest')
            ->middleware('can:proyectos.view');

        Route::post('/reorder', [ProyectoEtiquetaController::class, 'reorder'])
            ->name('reorder')
            ->middleware('can:proyectos.manage_tags');
    });

    // Búsqueda global de etiquetas
    Route::get('etiquetas/search', [ProyectoEtiquetaController::class, 'search'])
        ->name('etiquetas.search')
        ->middleware('can:proyectos.view');

    // Rutas para gestión de contratos
    Route::prefix('contratos')->name('contratos.')->group(function () {
        Route::get('/', [ContratoController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.view');

        Route::get('/create', [ContratoController::class, 'create'])
            ->name('create')
            ->middleware('can:contratos.create');

        Route::post('/', [ContratoController::class, 'store'])
            ->name('store')
            ->middleware('can:contratos.create');

        Route::get('/proximos-vencer', [ContratoController::class, 'proximosVencer'])
            ->name('proximos-vencer')
            ->middleware('can:contratos.view');

        Route::get('/vencidos', [ContratoController::class, 'vencidos'])
            ->name('vencidos')
            ->middleware('can:contratos.view');

        Route::get('/{contrato}', [ContratoController::class, 'show'])
            ->name('show')
            ->middleware('can:contratos.view');

        Route::get('/{contrato}/edit', [ContratoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:contratos.edit');

        Route::put('/{contrato}', [ContratoController::class, 'update'])
            ->name('update')
            ->middleware('can:contratos.edit');

        Route::delete('/{contrato}', [ContratoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:contratos.delete');

        // Acciones adicionales
        Route::post('/{contrato}/cambiar-estado', [ContratoController::class, 'cambiarEstado'])
            ->name('cambiar-estado')
            ->middleware('can:contratos.change_status');

        Route::post('/{contrato}/duplicar', [ContratoController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:contratos.create');

        // Rutas anidadas para evidencias (solo lectura)
        Route::prefix('{contrato}/evidencias')->name('evidencias.')->group(function () {
            Route::get('/{evidencia}', [\Modules\Proyectos\Http\Controllers\Admin\EvidenciaController::class, 'show'])
                ->name('show')
                ->middleware('can:evidencias.view');
        });
    });

    // Rutas para contratos dentro del contexto de un proyecto
    Route::prefix('proyectos/{proyecto}/contratos')->name('proyectos.contratos.')->group(function () {
        Route::get('/', [ContratoController::class, 'index'])
            ->name('index')
            ->middleware('can:contratos.view');

        Route::get('/create', [ContratoController::class, 'create'])
            ->name('create')
            ->middleware('can:contratos.create');
    });

    // Rutas para hitos dentro del contexto de un proyecto
    Route::prefix('proyectos/{proyecto}/hitos')->name('proyectos.hitos.')->group(function () {
        Route::get('/', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'index'])
            ->name('index')
            ->middleware('can:hitos.view');

        Route::get('/create', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'create'])
            ->name('create')
            ->middleware('can:hitos.create');

        Route::post('/', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'store'])
            ->name('store')
            ->middleware('can:hitos.create');

        Route::get('/{hito}', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'show'])
            ->name('show')
            ->middleware('can:hitos.view');

        Route::get('/{hito}/edit', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:hitos.edit');

        Route::put('/{hito}', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'update'])
            ->name('update')
            ->middleware('can:hitos.edit');

        Route::delete('/{hito}', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:hitos.delete');

        Route::post('/{hito}/duplicar', [\Modules\Proyectos\Http\Controllers\Admin\HitoController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:hitos.create');

        // Rutas para entregables dentro del contexto de un hito
        Route::prefix('{hito}/entregables')->name('entregables.')->group(function () {
            Route::get('/', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'index'])
                ->name('index')
                ->middleware('can:entregables.view');

            Route::get('/create', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'create'])
                ->name('create')
                ->middleware('can:entregables.create');

            Route::post('/', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'store'])
                ->name('store')
                ->middleware('can:entregables.create');

            Route::get('/{entregable}', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'show'])
                ->name('show')
                ->middleware('can:entregables.view');

            Route::get('/{entregable}/edit', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'edit'])
                ->name('edit')
                ->middleware('can:entregables.edit');

            Route::put('/{entregable}', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'update'])
                ->name('update')
                ->middleware('can:entregables.edit');

            Route::delete('/{entregable}', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:entregables.delete');

            Route::post('/{entregable}/completar', [\Modules\Proyectos\Http\Controllers\Admin\EntregableController::class, 'completar'])
                ->name('completar')
                ->middleware('can:entregables.complete');
        });
    });

    // Rutas para gestión de obligaciones de contratos
    Route::prefix('obligaciones')->name('obligaciones.')->group(function () {
        Route::get('/', [ObligacionContratoController::class, 'index'])
            ->name('index')
            ->middleware('can:obligaciones.view');

        Route::get('/create', [ObligacionContratoController::class, 'create'])
            ->name('create')
            ->middleware('can:obligaciones.create');

        Route::post('/', [ObligacionContratoController::class, 'store'])
            ->name('store')
            ->middleware('can:obligaciones.create');

        Route::get('/{obligacion}', [ObligacionContratoController::class, 'show'])
            ->name('show')
            ->middleware('can:obligaciones.view');

        Route::get('/{obligacion}/edit', [ObligacionContratoController::class, 'edit'])
            ->name('edit')
            ->middleware('can:obligaciones.edit');

        Route::put('/{obligacion}', [ObligacionContratoController::class, 'update'])
            ->name('update')
            ->middleware('can:obligaciones.edit');

        Route::delete('/{obligacion}', [ObligacionContratoController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:obligaciones.delete');

        // Acciones adicionales
        Route::post('/{obligacion}/completar', [ObligacionContratoController::class, 'completar'])
            ->name('completar')
            ->middleware('can:obligaciones.complete');

        Route::post('/{obligacion}/duplicar', [ObligacionContratoController::class, 'duplicar'])
            ->name('duplicar')
            ->middleware('can:obligaciones.create');

        Route::post('/{obligacion}/mover', [ObligacionContratoController::class, 'mover'])
            ->name('mover')
            ->middleware('can:obligaciones.edit');

        Route::post('/reordenar', [ObligacionContratoController::class, 'reordenar'])
            ->name('reordenar')
            ->middleware('can:obligaciones.edit');

        Route::post('/actualizar-estado-masivo', [ObligacionContratoController::class, 'actualizarEstadoMasivo'])
            ->name('actualizar-estado-masivo')
            ->middleware('can:obligaciones.edit');

        Route::get('/buscar/autocompletar', [ObligacionContratoController::class, 'buscar'])
            ->name('buscar')
            ->middleware('can:obligaciones.view');

        Route::get('/exportar', [ObligacionContratoController::class, 'exportar'])
            ->name('exportar')
            ->middleware('can:obligaciones.export');
    });

    // Rutas para obligaciones dentro del contexto de un contrato
    Route::prefix('contratos/{contrato}/obligaciones')->name('contratos.obligaciones.')->group(function () {
        Route::get('/arbol', [ObligacionContratoController::class, 'arbol'])
            ->name('arbol')
            ->middleware('can:obligaciones.view');

        Route::get('/timeline', [ObligacionContratoController::class, 'timeline'])
            ->name('timeline')
            ->middleware('can:obligaciones.view');
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
