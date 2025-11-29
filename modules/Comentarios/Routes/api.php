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

    // CRUD de comentarios
    // {type} = 'hitos', 'entregables', etc.
    // {id} = ID del modelo
    Route::get('/{type}/{id}', [ComentarioController::class, 'index'])
        ->name('index')
        ->middleware('can:comentarios.view');

    Route::post('/{type}/{id}', [ComentarioController::class, 'store'])
        ->name('store')
        ->middleware('can:comentarios.create');

    Route::put('/{comentario}', [ComentarioController::class, 'update'])
        ->name('update');

    Route::delete('/{comentario}', [ComentarioController::class, 'destroy'])
        ->name('destroy');

    // Reacciones (emojis)
    Route::post('/{comentario}/reaccion', [ComentarioController::class, 'toggleReaccion'])
        ->name('reaccion')
        ->middleware('can:comentarios.react');
});
