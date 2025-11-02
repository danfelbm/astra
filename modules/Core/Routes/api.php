<?php

use Modules\Core\Http\Controllers\FileUploadController;
use Modules\Core\Http\Controllers\Api\QueueStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core API Routes
|--------------------------------------------------------------------------
|
| Rutas API del módulo Core para usuarios autenticados.
| Incluye file upload y queue status.
|
*/

Route::middleware(['auth', 'verified', 'user'])->prefix('miembro')->name('user.')->group(function () {

    // File upload routes
    Route::prefix('api/files')->name('api.files.')->group(function () {
        Route::post('upload', [FileUploadController::class, 'upload'])->name('upload');
        Route::delete('delete', [FileUploadController::class, 'delete'])->name('delete');
        Route::get('download', [FileUploadController::class, 'download'])->name('download');
        Route::get('info', [FileUploadController::class, 'info'])->name('info');
    });

    // Queue Status API routes
    Route::prefix('api/queue')->name('api.queue.')->group(function () {
        Route::get('status', [QueueStatusController::class, 'status'])->name('status');
        Route::get('otp/estimate', [QueueStatusController::class, 'estimate'])->name('otp.estimate');
        Route::get('otp/position/{identifier}', [QueueStatusController::class, 'position'])->name('otp.position');
        Route::get('metrics', [QueueStatusController::class, 'metrics'])->name('metrics');
    });
});
