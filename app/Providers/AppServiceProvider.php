<?php

namespace App\Providers;

use App\Models\Candidatura;
use App\Observers\CandidaturaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar Observer para capturar historial de candidaturas
        Candidatura::observe(CandidaturaObserver::class);
    }
}
