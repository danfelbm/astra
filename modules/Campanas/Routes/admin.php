<?php

use Illuminate\Support\Facades\Route;
use Modules\Campanas\Http\Controllers\Admin\PlantillaEmailController;
use Modules\Campanas\Http\Controllers\Admin\PlantillaWhatsAppController;
use Modules\Campanas\Http\Controllers\Admin\CampanaController;
use Modules\Campanas\Http\Controllers\Admin\WhatsAppGroupController;

/*
|--------------------------------------------------------------------------
| Rutas Administrativas del Módulo Campañas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // =========================================================================
    // PLANTILLAS - bajo /admin/campanas/plantillas-*
    // =========================================================================
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
    });

    // =========================================================================
    // ENVÍO DE CAMPAÑAS - bajo /admin/envio-campanas
    // =========================================================================
    Route::prefix('envio-campanas')->name('envio-campanas.')->group(function () {

        Route::get('/', [CampanaController::class, 'index'])->name('index')
            ->middleware('can:campanas.view');

        Route::get('/create', [CampanaController::class, 'create'])->name('create')
            ->middleware('can:campanas.create');

        Route::post('/', [CampanaController::class, 'store'])->name('store')
            ->middleware('can:campanas.create');

        Route::get('/stats', [CampanaController::class, 'stats'])->name('stats')
            ->middleware('can:campanas.view');

        // Endpoint para contar usuarios filtrados (modo manual de audiencia)
        Route::post('/count-users', [CampanaController::class, 'countFilteredUsers'])->name('count-users')
            ->middleware('can:campanas.create');

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

        // Métricas, logs y reportes
        Route::get('/{campana}/metrics', [CampanaController::class, 'metrics'])->name('metrics')
            ->middleware('can:campanas.view');

        Route::get('/{campana}/logs', [CampanaController::class, 'logs'])->name('logs')
            ->middleware('can:campanas.view');

        Route::post('/{campana}/export', [CampanaController::class, 'export'])->name('export')
            ->middleware('can:campanas.export');

        Route::post('/{campana}/preview', [CampanaController::class, 'preview'])->name('preview')
            ->middleware('can:campanas.view');
    });

    // =========================================================================
    // GRUPOS DE WHATSAPP - bajo /admin/whatsapp-groups
    // =========================================================================
    Route::prefix('whatsapp-groups')->name('whatsapp-groups.')->group(function () {
        Route::get('/', [WhatsAppGroupController::class, 'index'])->name('index')
            ->middleware('can:campanas.view');

        Route::post('/sync', [WhatsAppGroupController::class, 'sync'])->name('sync')
            ->middleware('can:campanas.edit');

        Route::post('/preview-jid', [WhatsAppGroupController::class, 'previewByJid'])->name('preview-jid')
            ->middleware('can:campanas.edit');

        Route::post('/add-by-jid', [WhatsAppGroupController::class, 'addByJid'])->name('add-by-jid')
            ->middleware('can:campanas.edit');

        Route::get('/list', [WhatsAppGroupController::class, 'list'])->name('list')
            ->middleware('can:campanas.view');

        Route::get('/{whatsappGroup}', [WhatsAppGroupController::class, 'show'])->name('show')
            ->middleware('can:campanas.view');

        Route::get('/{whatsappGroup}/participants', [WhatsAppGroupController::class, 'getParticipants'])->name('participants')
            ->middleware('can:campanas.view');

        Route::post('/{whatsappGroup}/refresh', [WhatsAppGroupController::class, 'refresh'])->name('refresh')
            ->middleware('can:campanas.edit');

        Route::delete('/{whatsappGroup}', [WhatsAppGroupController::class, 'destroy'])->name('destroy')
            ->middleware('can:campanas.delete');
    });
});
