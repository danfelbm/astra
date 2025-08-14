<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Trait para autorización de acciones en controladores
 * Proporciona métodos helper para verificar permisos granulares
 */
trait AuthorizesActions
{
    /**
     * Autorizar una acción específica basada en permisos
     * 
     * @param string $permission El permiso requerido (ej: 'users.create')
     * @param string|null $message Mensaje personalizado de error
     * @throws AuthorizationException
     * @return void
     */
    protected function authorizeAction(string $permission, ?string $message = null): void
    {
        $user = Auth::user();
        
        if (!$user) {
            $this->handleUnauthorized('Usuario no autenticado');
        }
        
        // Si es super admin, siempre tiene permiso
        if ($user->isSuperAdmin()) {
            return;
        }
        
        // Verificar el permiso específico
        if (!$user->hasPermission($permission)) {
            $message = $message ?? "No tienes permiso para realizar esta acción. Permiso requerido: {$permission}";
            $this->handleUnauthorized($message);
        }
    }
    
    /**
     * Autorizar si el usuario tiene alguno de los permisos especificados
     * 
     * @param array $permissions Array de permisos
     * @param string|null $message Mensaje personalizado de error
     * @throws AuthorizationException
     * @return void
     */
    protected function authorizeAny(array $permissions, ?string $message = null): void
    {
        $user = Auth::user();
        
        if (!$user) {
            $this->handleUnauthorized('Usuario no autenticado');
        }
        
        // Si es super admin, siempre tiene permiso
        if ($user->isSuperAdmin()) {
            return;
        }
        
        // Verificar si tiene alguno de los permisos
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return;
            }
        }
        
        $permissionsList = implode(', ', $permissions);
        $message = $message ?? "No tienes ninguno de los permisos requeridos: {$permissionsList}";
        $this->handleUnauthorized($message);
    }
    
    /**
     * Verificar si el usuario puede realizar una acción (sin lanzar excepción)
     * 
     * @param string $permission El permiso a verificar
     * @return bool
     */
    protected function canPerform(string $permission): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        return $user->isSuperAdmin() || $user->hasPermission($permission);
    }
    
    /**
     * Verificar si el usuario puede realizar alguna de las acciones
     * 
     * @param array $permissions Array de permisos
     * @return bool
     */
    protected function canPerformAny(array $permissions): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Autorizar acceso a un módulo específico
     * 
     * @param string $module El módulo requerido (ej: 'users', 'votaciones')
     * @param string|null $message Mensaje personalizado de error
     * @throws AuthorizationException
     * @return void
     */
    protected function authorizeModule(string $module, ?string $message = null): void
    {
        $user = Auth::user();
        
        if (!$user) {
            $this->handleUnauthorized('Usuario no autenticado');
        }
        
        // Si es super admin, siempre tiene acceso
        if ($user->isSuperAdmin()) {
            return;
        }
        
        // Verificar acceso al módulo
        if (!$user->hasModuleAccess($module)) {
            $message = $message ?? "No tienes acceso al módulo: {$module}";
            $this->handleUnauthorized($message);
        }
    }
    
    /**
     * Manejar acceso no autorizado
     * 
     * @param string $message
     * @throws AuthorizationException
     * @return never
     */
    private function handleUnauthorized(string $message): never
    {
        if (request()->expectsJson()) {
            response()->json([
                'message' => $message,
                'error' => 'Unauthorized'
            ], 403)->send();
            exit;
        }
        
        abort(403, $message);
    }
    
    /**
     * Obtener permisos basados en la acción del controlador
     * Útil para mapear acciones CRUD estándar a permisos
     * 
     * @param string $action La acción del controlador (index, create, store, etc.)
     * @param string $resource El recurso base (users, votaciones, etc.)
     * @return string
     */
    protected function getPermissionForAction(string $action, string $resource): string
    {
        $actionMap = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete',
        ];
        
        $permission = $actionMap[$action] ?? $action;
        
        return "{$resource}.{$permission}";
    }
    
    /**
     * Registrar intento de acceso no autorizado
     * Útil para auditoría y monitoreo de seguridad
     * 
     * @param string $permission El permiso que se intentó usar
     * @param mixed $resource Recurso opcional al que se intentó acceder
     * @return void
     */
    protected function logUnauthorizedAttempt(string $permission, $resource = null): void
    {
        $user = Auth::user();
        $userId = $user ? $user->id : 'guest';
        $userName = $user ? $user->name : 'Guest User';
        
        // Log del intento no autorizado
        \Log::warning('Unauthorized access attempt', [
            'user_id' => $userId,
            'user_name' => $userName,
            'permission' => $permission,
            'resource' => $resource,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toIso8601String(),
        ]);
        
        // Si tienes Spatie Activity Log instalado, también puedes usarlo
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'permission' => $permission,
                    'resource' => $resource,
                    'ip' => request()->ip(),
                ])
                ->log('Unauthorized access attempt');
        }
    }
}