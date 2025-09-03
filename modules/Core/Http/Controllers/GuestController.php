<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Http\Controllers\Base\Controller;

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
