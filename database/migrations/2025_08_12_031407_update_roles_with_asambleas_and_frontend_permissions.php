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
        // Actualizar permisos del rol admin (agregar permisos de Asambleas)
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $currentPermissions = json_decode($adminRole->permissions, true) ?? [];
            
            // Agregar permisos de Asambleas para admin
            $asambleasPermissions = [
                'asambleas.view',
                'asambleas.create',
                'asambleas.edit',
                'asambleas.delete',
                'asambleas.manage_participants',
            ];
            
            $updatedPermissions = array_unique(array_merge($currentPermissions, $asambleasPermissions));
            
            // Actualizar módulos permitidos
            $currentModules = json_decode($adminRole->allowed_modules, true) ?? [];
            if (!in_array('asambleas', $currentModules)) {
                $currentModules[] = 'asambleas';
            }
            
            DB::table('roles')
                ->where('id', $adminRole->id)
                ->update([
                    'permissions' => json_encode(array_values($updatedPermissions)),
                    'allowed_modules' => json_encode(array_values($currentModules)),
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol manager (agregar permisos de vista de Asambleas)
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        if ($managerRole) {
            $currentPermissions = json_decode($managerRole->permissions, true) ?? [];
            
            // Agregar permisos básicos de Asambleas para manager
            $asambleasPermissions = [
                'asambleas.view',
                'asambleas.manage_participants',
            ];
            
            $updatedPermissions = array_unique(array_merge($currentPermissions, $asambleasPermissions));
            
            // Actualizar módulos permitidos
            $currentModules = json_decode($managerRole->allowed_modules, true) ?? [];
            if (!in_array('asambleas', $currentModules)) {
                $currentModules[] = 'asambleas';
            }
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode(array_values($updatedPermissions)),
                    'allowed_modules' => json_encode(array_values($currentModules)),
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol user (usuario regular - permisos frontend)
        $userRole = DB::table('roles')->where('name', 'user')->first();
        if ($userRole) {
            // Permisos frontend para usuarios regulares
            $frontendPermissions = [
                'dashboard.view',
                'votaciones.view_public',
                'votaciones.vote',
                'votaciones.view_results',
                'asambleas.view_public',
                'asambleas.participate',
                'asambleas.view_minutes',
                'convocatorias.view_public',
                'convocatorias.apply',
                'postulaciones.create',
                'postulaciones.view_own',
                'postulaciones.edit_own',
                'candidaturas.create_own',
                'candidaturas.view_own',
                'candidaturas.edit_own',
                'candidaturas.view_public',
                'profile.view',
                'profile.edit',
            ];
            
            // Módulos frontend
            $frontendModules = [
                'dashboard',
                'votaciones',
                'asambleas',
                'convocatorias',
                'postulaciones',
                'candidaturas',
                'profile',
            ];
            
            DB::table('roles')
                ->where('id', $userRole->id)
                ->update([
                    'permissions' => json_encode($frontendPermissions),
                    'allowed_modules' => json_encode($frontendModules),
                    'is_administrative' => false,
                    'updated_at' => now(),
                ]);
        }
        
        // Actualizar permisos del rol end_customer (cliente final - permisos limitados)
        $endCustomerRole = DB::table('roles')->where('name', 'end_customer')->first();
        if ($endCustomerRole) {
            // Permisos muy limitados para clientes finales
            $limitedPermissions = [
                'dashboard.view',
                'votaciones.view_public',
                'votaciones.vote',
                'asambleas.view_public',
                'convocatorias.view_public',
                'candidaturas.view_public',
                'profile.view',
            ];
            
            // Módulos limitados
            $limitedModules = [
                'dashboard',
                'votaciones',
                'asambleas',
                'convocatorias',
                'candidaturas',
                'profile',
            ];
            
            DB::table('roles')
                ->where('id', $endCustomerRole->id)
                ->update([
                    'permissions' => json_encode($limitedPermissions),
                    'allowed_modules' => json_encode($limitedModules),
                    'is_administrative' => false,
                    'updated_at' => now(),
                ]);
        }
        
        // Crear rol específico para gestores de Asambleas si no existe
        $asambleaManagerRole = DB::table('roles')->where('name', 'asamblea_manager')->first();
        if (!$asambleaManagerRole) {
            DB::table('roles')->insert([
                'tenant_id' => null, // Rol del sistema
                'name' => 'asamblea_manager',
                'display_name' => 'Gestor de Asambleas',
                'description' => 'Rol especializado para gestionar asambleas y sus participantes',
                'permissions' => json_encode([
                    'dashboard.admin',
                    'asambleas.view',
                    'asambleas.create',
                    'asambleas.edit',
                    'asambleas.manage_participants',
                    'users.view',
                    'reports.view',
                ]),
                'allowed_modules' => json_encode([
                    'dashboard',
                    'asambleas',
                    'users',
                    'reports',
                ]),
                'is_system' => true,
                'is_administrative' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover permisos de Asambleas del rol admin
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $currentPermissions = json_decode($adminRole->permissions, true) ?? [];
            
            // Remover permisos de Asambleas
            $asambleasPermissions = [
                'asambleas.view',
                'asambleas.create',
                'asambleas.edit',
                'asambleas.delete',
                'asambleas.manage_participants',
            ];
            
            $updatedPermissions = array_diff($currentPermissions, $asambleasPermissions);
            
            // Remover módulo asambleas
            $currentModules = json_decode($adminRole->allowed_modules, true) ?? [];
            $updatedModules = array_diff($currentModules, ['asambleas']);
            
            DB::table('roles')
                ->where('id', $adminRole->id)
                ->update([
                    'permissions' => json_encode(array_values($updatedPermissions)),
                    'allowed_modules' => json_encode(array_values($updatedModules)),
                    'updated_at' => now(),
                ]);
        }
        
        // Remover permisos de Asambleas del rol manager
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        if ($managerRole) {
            $currentPermissions = json_decode($managerRole->permissions, true) ?? [];
            
            // Remover permisos de Asambleas
            $asambleasPermissions = [
                'asambleas.view',
                'asambleas.manage_participants',
            ];
            
            $updatedPermissions = array_diff($currentPermissions, $asambleasPermissions);
            
            // Remover módulo asambleas
            $currentModules = json_decode($managerRole->allowed_modules, true) ?? [];
            $updatedModules = array_diff($currentModules, ['asambleas']);
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode(array_values($updatedPermissions)),
                    'allowed_modules' => json_encode(array_values($updatedModules)),
                    'updated_at' => now(),
                ]);
        }
        
        // Restaurar permisos anteriores del rol user
        $userRole = DB::table('roles')->where('name', 'user')->first();
        if ($userRole) {
            // Restaurar permisos originales (simplificados)
            $originalPermissions = [
                'dashboard.view',
                'votaciones.view',
                'votaciones.vote',
                'convocatorias.view',
                'postulaciones.create',
                'candidaturas.create_own',
                'profile.edit',
            ];
            
            DB::table('roles')
                ->where('id', $userRole->id)
                ->update([
                    'permissions' => json_encode($originalPermissions),
                    'allowed_modules' => json_encode([]),
                    'updated_at' => now(),
                ]);
        }
        
        // Restaurar permisos anteriores del rol end_customer
        $endCustomerRole = DB::table('roles')->where('name', 'end_customer')->first();
        if ($endCustomerRole) {
            // Restaurar permisos originales
            $originalPermissions = [
                'votaciones.view',
                'votaciones.vote',
                'convocatorias.view',
            ];
            
            DB::table('roles')
                ->where('id', $endCustomerRole->id)
                ->update([
                    'permissions' => json_encode($originalPermissions),
                    'allowed_modules' => json_encode([]),
                    'updated_at' => now(),
                ]);
        }
        
        // Eliminar rol asamblea_manager si fue creado
        DB::table('roles')->where('name', 'asamblea_manager')->delete();
    }
};