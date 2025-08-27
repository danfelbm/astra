<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Mostrar dashboard principal del usuario
     */
    public function index(Request $request): Response
    {
        // Verificar permisos de usuario para ver dashboard personal
        abort_unless(auth()->user()->can('dashboard.view'), 403, 'No tienes permisos para ver tu dashboard personal');
        
        $hasAssemblyAccess = DB::table('asamblea_usuario')
            ->where('usuario_id', auth()->id())
            ->where('asamblea_id', 1)
            ->exists();
        
        return Inertia::render('User/Dashboard', [
            'hasAssemblyAccess' => $hasAssemblyAccess,
            // Props de permisos de usuario
            'canViewDashboard' => auth()->user()->can('dashboard.view'),
        ]);
    }
}