<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;

abstract class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:access-admin']);
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
