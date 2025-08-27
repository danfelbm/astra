<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;

abstract class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    protected function getLayout(): string
    {
        return 'UserLayout';
    }

    protected function shouldShowAdminLink(): bool
    {
        return auth()->user()->hasRole(['admin', 'super_admin']);
    }
}
