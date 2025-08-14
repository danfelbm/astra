<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Verifica si el usuario tiene un permiso específico
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Si es super admin, siempre tiene acceso
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Si no se especifica un permiso, usar el permiso basado en la ruta
        if (!$permission) {
            $permission = $this->getPermissionFromRoute($request);
        }

        // Verificar el permiso específico
        if ($permission && !$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
            abort(403, 'Acceso denegado. No tienes el permiso necesario: ' . $permission);
        }

        return $next($request);
    }

    /**
     * Obtener el permiso basado en la ruta actual
     */
    private function getPermissionFromRoute(Request $request): ?string
    {
        $routeName = $request->route()->getName();
        $method = $request->method();
        
        // Mapear rutas a permisos
        $routePermissionMap = [
            // Usuarios - NOTA: La ruta es 'usuarios' no 'users'
            'admin.usuarios.index' => 'users.view',
            'admin.usuarios.show' => 'users.view',
            'admin.usuarios.create' => 'users.create',
            'admin.usuarios.store' => 'users.create',
            'admin.usuarios.edit' => 'users.edit',
            'admin.usuarios.update' => 'users.edit',
            'admin.usuarios.destroy' => 'users.delete',
            'admin.usuarios.toggle-active' => 'users.edit',
            
            // Votaciones
            'admin.votaciones.index' => 'votaciones.view',
            'admin.votaciones.show' => 'votaciones.view',
            'admin.votaciones.create' => 'votaciones.create',
            'admin.votaciones.store' => 'votaciones.create',
            'admin.votaciones.edit' => 'votaciones.edit',
            'admin.votaciones.update' => 'votaciones.edit',
            'admin.votaciones.destroy' => 'votaciones.delete',
            
            // Convocatorias
            'admin.convocatorias.index' => 'convocatorias.view',
            'admin.convocatorias.show' => 'convocatorias.view',
            'admin.convocatorias.create' => 'convocatorias.create',
            'admin.convocatorias.store' => 'convocatorias.create',
            'admin.convocatorias.edit' => 'convocatorias.edit',
            'admin.convocatorias.update' => 'convocatorias.edit',
            'admin.convocatorias.destroy' => 'convocatorias.delete',
            
            // Postulaciones
            'admin.postulaciones.index' => 'postulaciones.view',
            'admin.postulaciones.show' => 'postulaciones.view',
            'admin.postulaciones.create' => 'postulaciones.create',
            'admin.postulaciones.store' => 'postulaciones.create',
            'admin.postulaciones.edit' => 'postulaciones.review',
            'admin.postulaciones.update' => 'postulaciones.review',
            
            // Candidaturas
            'admin.candidaturas.index' => 'candidaturas.view',
            'admin.candidaturas.show' => 'candidaturas.view',
            'admin.candidaturas.create' => 'candidaturas.create',
            'admin.candidaturas.store' => 'candidaturas.create',
            'admin.candidaturas.approve' => 'candidaturas.approve',
            
            // Cargos
            'admin.cargos.index' => 'cargos.view',
            'admin.cargos.show' => 'cargos.view',
            'admin.cargos.create' => 'cargos.create',
            'admin.cargos.store' => 'cargos.create',
            'admin.cargos.edit' => 'cargos.edit',
            'admin.cargos.update' => 'cargos.edit',
            'admin.cargos.destroy' => 'cargos.delete',
            
            // Periodos Electorales
            'admin.periodos-electorales.index' => 'periodos.view',
            'admin.periodos-electorales.show' => 'periodos.view',
            'admin.periodos-electorales.create' => 'periodos.create',
            'admin.periodos-electorales.store' => 'periodos.create',
            'admin.periodos-electorales.edit' => 'periodos.edit',
            'admin.periodos-electorales.update' => 'periodos.edit',
            'admin.periodos-electorales.destroy' => 'periodos.delete',
            
            // Roles
            'admin.roles.index' => 'roles.view',
            'admin.roles.show' => 'roles.view',
            'admin.roles.create' => 'roles.create',
            'admin.roles.store' => 'roles.create',
            'admin.roles.edit' => 'roles.edit',
            'admin.roles.update' => 'roles.edit',
            'admin.roles.destroy' => 'roles.delete',
            
            // Segmentos
            'admin.segments.index' => 'segments.view',
            'admin.segments.show' => 'segments.view',
            'admin.segments.create' => 'segments.create',
            'admin.segments.store' => 'segments.create',
            'admin.segments.edit' => 'segments.edit',
            'admin.segments.update' => 'segments.edit',
            'admin.segments.destroy' => 'segments.delete',
            
            // Tenants (solo super admin)
            'admin.tenants.index' => 'tenants.view',
            'admin.tenants.show' => 'tenants.view',
            'admin.tenants.create' => 'tenants.create',
            'admin.tenants.store' => 'tenants.create',
            'admin.tenants.edit' => 'tenants.edit',
            'admin.tenants.update' => 'tenants.edit',
            'admin.tenants.destroy' => 'tenants.delete',
            
            // Dashboard
            'admin.dashboard' => 'dashboard.view',
            
            // Configuración
            'admin.settings.index' => 'settings.view',
            'admin.settings.update' => 'settings.edit',
            
            // ===== RUTAS PÚBLICAS (FRONTEND) =====
            
            // Asambleas públicas
            'asambleas.index' => 'asambleas.view_public',
            'asambleas.show' => 'asambleas.view_public',
            
            // Votaciones públicas
            'votaciones.index' => 'votaciones.view_public',
            'votaciones.show' => 'votaciones.view_public',
            'votaciones.votar' => 'votaciones.vote',
            
            // Convocatorias públicas
            'convocatorias.index' => 'convocatorias.view_public',
            'convocatorias.show' => 'convocatorias.view_public',
            'convocatorias.postular' => 'convocatorias.apply',
            
            // Postulaciones propias
            'postulaciones.index' => 'postulaciones.view_own',
            'postulaciones.create' => 'postulaciones.create',
            'postulaciones.store' => 'postulaciones.create',
            'postulaciones.edit' => 'postulaciones.edit_own',
            'postulaciones.update' => 'postulaciones.edit_own',
            'postulaciones.destroy' => 'postulaciones.delete_own',
            
            // Candidaturas propias
            'candidaturas.index' => 'candidaturas.view_own',
            'candidaturas.create' => 'candidaturas.create_own',
            'candidaturas.store' => 'candidaturas.create_own',
            'candidaturas.edit' => 'candidaturas.edit_own',
            'candidaturas.update' => 'candidaturas.edit_own',
            
            // Dashboard personal
            'dashboard' => 'dashboard.view',
            
            // Perfil
            'profile.edit' => 'profile.edit',
            'profile.update' => 'profile.edit',
        ];
        
        if ($routeName && isset($routePermissionMap[$routeName])) {
            return $routePermissionMap[$routeName];
        }
        
        // Si no se encuentra mapeo específico, intentar inferir del nombre de la ruta
        if ($routeName && str_starts_with($routeName, 'admin.')) {
            $parts = explode('.', $routeName);
            if (count($parts) >= 3) {
                $module = $parts[1];
                $action = $parts[2];
                
                // Mapear acciones a permisos
                $actionMap = [
                    'index' => 'view',
                    'show' => 'view',
                    'create' => 'create',
                    'store' => 'create',
                    'edit' => 'edit',
                    'update' => 'edit',
                    'destroy' => 'delete',
                ];
                
                if (isset($actionMap[$action])) {
                    return $module . '.' . $actionMap[$action];
                }
            }
        }
        
        return null;
    }
}