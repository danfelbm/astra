<?php

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/configuration')
    ->name('admin.configuration.')
    ->group(function () {
        // Agregar rutas aquí
    });
