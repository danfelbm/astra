<?php

use Illuminate\Support\Facades\Route;
use Modules\Elecciones\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/elecciones')
    ->name('admin.elecciones.')
    ->group(function () {
        // Agregar rutas aquí
    });
