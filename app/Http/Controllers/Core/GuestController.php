<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;

abstract class GuestController extends Controller
{
    protected function getLayout(): string
    {
        return 'GuestLayout';
    }

    protected function getPublicNavigation(): array
    {
        return [
            ['label' => 'Inicio', 'route' => 'welcome'],
            ['label' => 'Votaciones Públicas', 'route' => 'public.votaciones'],
            ['label' => 'Resultados', 'route' => 'public.resultados'],
        ];
    }
}
