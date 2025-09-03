<?php

use Illuminate\Support\Facades\Route;
use Modules\Imports\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/imports')
    ->name('admin.imports.')
    ->group(function () {
        // Agregar rutas aquí
    });
