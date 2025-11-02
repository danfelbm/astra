<?php

use Modules\Votaciones\Http\Controllers\User\ResultadosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Votaciones API Routes
|--------------------------------------------------------------------------
|
| Rutas API para datos de resultados de votaciones.
| Requieren autenticación.
|
*/

// API routes for results data (authenticated)
Route::middleware(['auth', 'verified'])->prefix('api/votaciones')->name('api.votaciones.')->group(function () {
    Route::get('{votacion}/resultados/consolidado', [ResultadosController::class, 'consolidado'])->name('resultados.consolidado');
    Route::get('{votacion}/resultados/territorio', [ResultadosController::class, 'territorio'])->name('resultados.territorio');
    Route::get('{votacion}/resultados/tokens', [ResultadosController::class, 'tokens'])->name('resultados.tokens');
    Route::get('{votacion}/resultados/tokens/download', [ResultadosController::class, 'downloadTokensCsv'])->name('resultados.tokens.download');
    Route::get('{votacion}/resultados/ranking-territorio', [ResultadosController::class, 'rankingPorTerritorio'])->name('resultados.ranking-territorio');
    Route::get('{votacion}/resultados/distribucion-opcion', [ResultadosController::class, 'distribucionGeograficaPorOpcion'])->name('resultados.distribucion-opcion');
});
