<?php

use Illuminate\Support\Facades\Route;
use Modules\Comentarios\Http\Controllers\Api\ComentarioController;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo Comentarios
|--------------------------------------------------------------------------
|
| Rutas API para el sistema de comentarios polimórfico.
| Todas las rutas requieren autenticación.
|
*/

Route::middleware(['auth'])->prefix('api/comentarios')->name('api.comentarios.')->group(function () {
    // Búsqueda de usuarios para menciones (@usuario)
    Route::get('/usuarios/buscar', [ComentarioController::class, 'buscarUsuarios'])
        ->name('usuarios.buscar');

    // Operaciones sobre comentarios existentes (ANTES de rutas dinámicas)
    Route::put('/{comentario}', [ComentarioController::class, 'update'])
        ->name('update')
        ->where('comentario', '[0-9]+');

    Route::delete('/{comentario}', [ComentarioController::class, 'destroy'])
        ->name('destroy')
        ->where('comentario', '[0-9]+');

    // Reacciones (emojis) - ruta específica antes de rutas dinámicas
    Route::post('/{comentario}/reaccion', [ComentarioController::class, 'toggleReaccion'])
        ->name('reaccion')
        ->middleware('can:comentarios.react')
        ->where('comentario', '[0-9]+');

    // CRUD de comentarios para modelos (rutas dinámicas AL FINAL)
    // {type} = 'hitos', 'entregables', etc. (solo letras y guion bajo)
    // {id} = ID del modelo
    Route::get('/{type}/{id}', [ComentarioController::class, 'index'])
        ->name('index')
        ->middleware('can:comentarios.view')
        ->where(['type' => '[a-z_]+', 'id' => '[0-9]+']);

    Route::post('/{type}/{id}', [ComentarioController::class, 'store'])
        ->name('store')
        ->middleware('can:comentarios.create')
        ->where(['type' => '[a-z_]+', 'id' => '[0-9]+']);
});
