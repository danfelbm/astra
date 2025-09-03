<?php

use Illuminate\Support\Facades\Route;
use Modules\Geografico\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/geografico')
    ->name('admin.geografico.')
    ->group(function () {
        // Agregar rutas aquí
    });
