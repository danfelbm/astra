<?php

use Illuminate\Support\Facades\Route;
use Modules\Asamblea\Http\Controllers\Admin\AsambleaController;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/asambleas')
    ->name('admin.asambleas.')
    ->group(function () {
        // Index
        Route::get('/', [AsambleaController::class, 'index'])
            ->name('index')
            ->middleware('permission:asambleas.view');
        
        // Create
        Route::get('/create', [AsambleaController::class, 'create'])
            ->name('create')
            ->middleware('permission:asambleas.create');
        
        // Store
        Route::post('/', [AsambleaController::class, 'store'])
            ->name('store')
            ->middleware('permission:asambleas.create');
        
        // Show
        Route::get('/{asamblea}', [AsambleaController::class, 'show'])
            ->name('show')
            ->middleware('permission:asambleas.view');
        
        // Edit
        Route::get('/{asamblea}/edit', [AsambleaController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:asambleas.edit');
        
        // Update
        Route::put('/{asamblea}', [AsambleaController::class, 'update'])
            ->name('update')
            ->middleware('permission:asambleas.edit');
        
        // Delete
        Route::delete('/{asamblea}', [AsambleaController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:asambleas.delete');
        
        // Manage participants
        Route::get('/{asamblea}/participantes', [AsambleaController::class, 'getParticipantes'])
            ->name('participantes')
            ->middleware('permission:asambleas.manage_participants');
        
        Route::post('/{asamblea}/participantes', [AsambleaController::class, 'updateParticipantes'])
            ->name('participantes.update')
            ->middleware('permission:asambleas.manage_participants');
        
        Route::delete('/{asamblea}/participantes/{usuario}', [AsambleaController::class, 'removeParticipante'])
            ->name('participantes.remove')
            ->middleware('permission:asambleas.manage_participants');
        
        // Zoom redirect
        Route::get('/zoom/redirect', [\Modules\Asamblea\Http\Controllers\User\ZoomRedirectController::class, 'redirect'])
            ->name('zoom.redirect');
    });