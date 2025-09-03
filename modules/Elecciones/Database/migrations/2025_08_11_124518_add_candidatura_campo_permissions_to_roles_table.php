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
        // Agregar nuevos permisos a los roles admin y super_admin
        $this->agregarPermisosACandidaturas();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover los permisos agregados
        $this->removerPermisosDeCandidaturas();
    }

    /**
     * Agregar permisos de aprobación de campos a roles existentes
     */
    private function agregarPermisosACandidaturas(): void
    {
        // Nuevos permisos para candidaturas
        $nuevosPermisos = [
            'candidaturas.aprobar_campos',
            'candidaturas.aprobar_campos_criticos',
            'candidaturas.ver_historial_aprobaciones',
        ];

        // Obtener roles admin y super_admin
        $rolesAdmin = DB::table('roles')
            ->whereIn('name', ['admin', 'super_admin'])
            ->get();

        foreach ($rolesAdmin as $rol) {
            $permisosActuales = json_decode($rol->permissions ?? '[]', true);
            
            // Agregar nuevos permisos si no existen
            foreach ($nuevosPermisos as $permiso) {
                if (!in_array($permiso, $permisosActuales)) {
                    $permisosActuales[] = $permiso;
                }
            }
            
            // Actualizar el rol con los nuevos permisos
            DB::table('roles')
                ->where('id', $rol->id)
                ->update([
                    'permissions' => json_encode($permisosActuales),
                    'updated_at' => now(),
                ]);
        }

        // Crear rol específico para revisores de candidaturas si no existe
        $revisorRolExists = DB::table('roles')
            ->where('name', 'revisor_candidaturas')
            ->exists();

        if (!$revisorRolExists) {
            DB::table('roles')->insert([
                'name' => 'revisor_candidaturas',
                'display_name' => 'Revisor de Candidaturas',
                'description' => 'Rol para usuarios que solo pueden revisar y aprobar campos de candidaturas',
                'permissions' => json_encode([
                    'candidaturas.view',
                    'candidaturas.aprobar_campos',
                    'candidaturas.ver_historial_aprobaciones',
                ]),
                'allowed_modules' => json_encode(['candidaturas']),
                'is_system' => false,
                'is_administrative' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Actualizar permisos del rol manager si existe
        $managerRole = DB::table('roles')
            ->where('name', 'manager')
            ->first();

        if ($managerRole) {
            $permisosManager = json_decode($managerRole->permissions ?? '[]', true);
            
            // Agregar permisos básicos de aprobación para managers
            $permisosManagerNuevos = [
                'candidaturas.view',
                'candidaturas.aprobar_campos',
            ];
            
            foreach ($permisosManagerNuevos as $permiso) {
                if (!in_array($permiso, $permisosManager)) {
                    $permisosManager[] = $permiso;
                }
            }
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode($permisosManager),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Remover permisos de aprobación de campos
     */
    private function removerPermisosDeCandidaturas(): void
    {
        $permisosARemover = [
            'candidaturas.aprobar_campos',
            'candidaturas.aprobar_campos_criticos',
            'candidaturas.ver_historial_aprobaciones',
        ];

        $roles = DB::table('roles')->get();

        foreach ($roles as $rol) {
            $permisos = json_decode($rol->permissions ?? '[]', true);
            
            // Filtrar los permisos removiendo los nuevos
            $permisosFiltrados = array_values(array_diff($permisos, $permisosARemover));
            
            DB::table('roles')
                ->where('id', $rol->id)
                ->update([
                    'permissions' => json_encode($permisosFiltrados),
                    'updated_at' => now(),
                ]);
        }

        // Eliminar rol de revisor si fue creado
        DB::table('roles')
            ->where('name', 'revisor_candidaturas')
            ->delete();
    }
};