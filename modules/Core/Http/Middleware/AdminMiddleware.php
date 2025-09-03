<?php

namespace Modules\Core\Http\Middleware;

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
        
        // Verificar si el usuario tiene acceso administrativo
        // Puede ser por rol con is_administrative = true o por permisos específicos
        if (!$user->hasAdministrativeAccess()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado. Se requiere acceso administrativo.'], 403);
            }
            
            abort(403, 'Acceso denegado. Se requiere acceso administrativo para acceder a esta área.');
        }
        
        return $next($request);
    }
}
