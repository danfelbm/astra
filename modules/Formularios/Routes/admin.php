<?php

use Illuminate\Support\Facades\Route;
use Modules\Formularios\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/formularios')
    ->name('admin.formularios.')
    ->group(function () {
        // Agregar rutas aquí
    });
