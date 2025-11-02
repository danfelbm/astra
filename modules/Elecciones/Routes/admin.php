<?php

use Illuminate\Support\Facades\Route;
use Modules\Elecciones\Http\Controllers\Admin\CandidaturaController as AdminCandidaturaController;
use Modules\Elecciones\Http\Controllers\Admin\CargoController;
use Modules\Elecciones\Http\Controllers\Admin\ConvocatoriaController;
use Modules\Elecciones\Http\Controllers\Admin\PeriodoElectoralController;
use Modules\Elecciones\Http\Controllers\Admin\PostulacionController as AdminPostulacionController;
use Modules\Imports\Http\Controllers\Admin\ImportController;

/*
|--------------------------------------------------------------------------
| Admin Routes del Módulo Elecciones
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de elecciones.
| Incluye Cargos, Periodos, Convocatorias, Candidaturas y Postulaciones.
|
*/

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

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
});
