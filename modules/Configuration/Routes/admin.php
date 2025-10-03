<?php

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/configuration')
    ->name('admin.configuration.')
    ->group(function () {
        // Ruta para actualizar mensaje de login
        Route::post('login', [Admin\ConfiguracionController::class, 'updateLogin'])
            ->middleware('can:settings.edit')
            ->name('update.login');

        // Ruta para actualizar dashboard de usuarios
        Route::post('dashboard-user', [Admin\ConfiguracionController::class, 'updateDashboardUser'])
            ->middleware('can:settings.edit')
            ->name('update.dashboard-user');

        // Ruta para actualizar página principal (Welcome)
        Route::post('welcome', [Admin\ConfiguracionController::class, 'updateWelcome'])
            ->middleware('can:settings.edit')
            ->name('update.welcome');
    });
