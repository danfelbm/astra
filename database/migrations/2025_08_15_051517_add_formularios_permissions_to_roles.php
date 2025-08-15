<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar permisos del rol admin
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        
        if ($adminRole) {
            $permissions = json_decode($adminRole->permissions, true) ?? [];
            $allowedModules = json_decode($adminRole->allowed_modules, true) ?? [];
            
            // Añadir permisos de formularios
            $newPermissions = [
                'formularios.view',
                'formularios.create', 
                'formularios.edit',
                'formularios.delete',
                'formularios.view_responses',
                'formularios.export',
                'formularios.manage_permissions',
            ];
            
            foreach ($newPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            // Añadir módulo formularios
            if (!in_array('formularios', $allowedModules)) {
                $allowedModules[] = 'formularios';
            }
            
            DB::table('roles')
                ->where('id', $adminRole->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'allowed_modules' => json_encode($allowedModules),
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol manager
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        
        if ($managerRole) {
            $permissions = json_decode($managerRole->permissions, true) ?? [];
            $allowedModules = json_decode($managerRole->allowed_modules, true) ?? [];
            
            // Añadir permisos limitados de formularios para manager
            $newPermissions = [
                'formularios.view',
                'formularios.create',
                'formularios.edit',
                'formularios.view_responses',
                'formularios.export',
            ];
            
            foreach ($newPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            // Añadir módulo formularios
            if (!in_array('formularios', $allowedModules)) {
                $allowedModules[] = 'formularios';
            }
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'allowed_modules' => json_encode($allowedModules),
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol user (usuarios finales)
        $userRole = DB::table('roles')->where('name', 'user')->first();
        
        if ($userRole) {
            $permissions = json_decode($userRole->permissions, true) ?? [];
            $allowedModules = json_decode($userRole->allowed_modules, true) ?? [];
            
            // Añadir permiso para llenar formularios públicos
            $newPermissions = [
                'formularios.fill_public',
                'formularios.view_public',
            ];
            
            foreach ($newPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            // Añadir módulo formularios para usuarios finales
            if (!in_array('formularios', $allowedModules)) {
                $allowedModules[] = 'formularios';
            }
            
            DB::table('roles')
                ->where('id', $userRole->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'allowed_modules' => json_encode($allowedModules),
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol end_customer
        $endCustomerRole = DB::table('roles')->where('name', 'end_customer')->first();
        
        if ($endCustomerRole) {
            $permissions = json_decode($endCustomerRole->permissions, true) ?? [];
            $allowedModules = json_decode($endCustomerRole->allowed_modules, true) ?? [];
            
            // Añadir permiso para llenar formularios públicos
            $newPermissions = [
                'formularios.fill_public',
                'formularios.view_public',
            ];
            
            foreach ($newPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            // Añadir módulo formularios
            if (!in_array('formularios', $allowedModules)) {
                $allowedModules[] = 'formularios';
            }
            
            DB::table('roles')
                ->where('id', $endCustomerRole->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'allowed_modules' => json_encode($allowedModules),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover permisos de formularios de todos los roles
        $roles = DB::table('roles')->get();
        
        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];
            $allowedModules = json_decode($role->allowed_modules, true) ?? [];
            
            // Remover permisos de formularios
            $permissions = array_filter($permissions, function($permission) {
                return !str_starts_with($permission, 'formularios.');
            });
            
            // Remover módulo formularios
            $allowedModules = array_filter($allowedModules, function($module) {
                return $module !== 'formularios';
            });
            
            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'permissions' => json_encode(array_values($permissions)),
                    'allowed_modules' => json_encode(array_values($allowedModules)),
                    'updated_at' => now(),
                ]);
        }
    }
};