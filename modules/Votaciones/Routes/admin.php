<?php

use Illuminate\Support\Facades\Route;
use Modules\Votaciones\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/votaciones')
    ->name('admin.votaciones.')
    ->group(function () {
        // Agregar rutas aquí
    });
