<?php

namespace Modules\Core\Providers;

use Modules\Elecciones\Models\Candidatura;
use Modules\Elecciones\Observers\CandidaturaObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        
        // Implicitly grant "Super Admin" role all permissions
        // This works in combination with the Gate::before() callback below
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
