<?php

use Modules\Core\Http\Controllers\Settings\UpdateDataController;
use Modules\Core\Http\Controllers\Settings\PasswordController;
use Modules\Core\Http\Controllers\Settings\ProfileController;
use Modules\Core\Http\Controllers\Settings\ProfileLocationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rutas para manejo de avatar
    Route::post('settings/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('settings/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    
    // Ruta para actualizar ubicación del usuario
    Route::patch('settings/profile/location', [ProfileLocationController::class, 'update'])->name('profile.location.update');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    // Rutas para actualización de datos con solicitud de aprobación
    Route::get('settings/update-data', [UpdateDataController::class, 'edit'])->name('update-data.edit');
    Route::post('settings/update-data', [UpdateDataController::class, 'update'])->name('update-data.update');
    Route::delete('settings/update-data/cancel', [UpdateDataController::class, 'cancel'])->name('update-data.cancel');

    Route::get('settings/appearance', function () {
        return Inertia::render('User/Settings/Appearance');
    })->name('appearance');
});
