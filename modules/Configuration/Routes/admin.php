<?php

use Modules\Configuration\Http\Controllers\Admin\ConfiguracionController;
use Modules\Configuration\Http\Controllers\Admin\OTPDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Configuration Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de configuración del sistema.
| Incluye OTP Dashboard y configuración general.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // OTP Dashboard routes
        Route::get('otp-dashboard', [OTPDashboardController::class, 'index'])
            ->middleware('can:dashboard.admin')
            ->name('otp-dashboard');
        Route::get('api/otp-dashboard/queue-status', [OTPDashboardController::class, 'queueStatus'])
            ->middleware('can:dashboard.admin')
            ->name('api.otp-dashboard.queue-status');
        Route::get('api/otp-dashboard/otp-stats', [OTPDashboardController::class, 'otpStats'])
            ->middleware('can:dashboard.admin')
            ->name('api.otp-dashboard.otp-stats');
        Route::get('api/otp-dashboard/queue/{queueName}/details', [OTPDashboardController::class, 'queueDetails'])
            ->middleware('can:queues.manage')
            ->name('api.otp-dashboard.queue-details');
        Route::post('api/otp-dashboard/retry-failed-jobs', [OTPDashboardController::class, 'retryFailedJobs'])
            ->middleware('can:queues.manage')
            ->name('api.otp-dashboard.retry-failed');
        Route::post('api/otp-dashboard/clean-failed-jobs', [OTPDashboardController::class, 'cleanFailedJobs'])
            ->middleware('can:queues.manage')
            ->name('api.otp-dashboard.clean-failed');

        // Configuration routes
        Route::get('configuracion', [ConfiguracionController::class, 'index'])
            ->middleware('can:settings.view')
            ->name('configuracion.index');
        Route::post('configuracion', [ConfiguracionController::class, 'update'])
            ->middleware('can:settings.edit')
            ->name('configuracion.update');
        Route::post('configuracion/candidaturas', [ConfiguracionController::class, 'updateCandidaturas'])
            ->middleware('can:settings.edit')
            ->name('configuracion.update.candidaturas');
        Route::post('configuracion/login', [ConfiguracionController::class, 'updateLogin'])
            ->middleware('can:settings.edit')
            ->name('configuracion.update.login');
        Route::post('configuracion/dashboard-user', [ConfiguracionController::class, 'updateDashboardUser'])
            ->middleware('can:settings.edit')
            ->name('configuracion.update.dashboard-user');
        Route::post('configuracion/welcome', [ConfiguracionController::class, 'updateWelcome'])
            ->middleware('can:settings.edit')
            ->name('configuracion.update.welcome');
    });
