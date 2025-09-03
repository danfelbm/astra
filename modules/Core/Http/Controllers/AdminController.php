<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Http\Controllers\Base\Controller;

abstract class AdminController extends Controller
{
    public function __construct()
    {
        // Los middlewares se manejan en las rutas específicas
        // No se requiere middleware adicional aquí
    }

    protected function getLayout(): string
    {
        return 'AdminLayout';
    }

    protected function getNavigationItems(): array
    {
        // Navegación específica de admin
        return config('navigation.admin', []);
    }
}
