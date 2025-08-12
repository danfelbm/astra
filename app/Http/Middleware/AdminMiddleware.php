<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
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
        
        // Verificar si el usuario tiene algún rol administrativo
        // Solo usuarios con roles administrativos pueden acceder a /admin
        if (!$user->hasAdministrativeRole()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado. Se requiere un rol administrativo.'], 403);
            }
            
            abort(403, 'Acceso denegado. Se requiere un rol administrativo para acceder a esta área.');
        }
        
        return $next($request);
    }
}
