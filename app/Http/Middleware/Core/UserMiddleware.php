<?php

namespace App\Http\Middleware\Core;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Permitir acceso si el usuario tiene rol 'user'
        if (!$user->hasRole('user')) {
            // Si es admin sin rol user, ofrecer agregarlo
            if ($user->hasAdministrativeAccess()) {
                return redirect()->route('admin.dashboard')
                    ->with('warning', 'No tienes rol de usuario. Contacta al administrador para agregarlo.');
            }
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado. Se requiere rol de usuario.'], 403);
            }
            
            abort(403, 'Acceso denegado. Se requiere rol de usuario.');
        }
        
        return $next($request);
    }
}