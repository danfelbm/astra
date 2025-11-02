<?php

use Modules\Rbac\Http\Controllers\Admin\RoleController;
use Modules\Rbac\Http\Controllers\Admin\SegmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RBAC Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de roles y segmentos (RBAC).
| Sistema de control de acceso basado en roles con Spatie Permission.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

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
    });
