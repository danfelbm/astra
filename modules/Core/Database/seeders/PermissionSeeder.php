<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define todos los permisos del sistema
        $permissions = [
            // Permisos Administrativos
            // Usuarios
            'users.view' => 'Ver usuarios',
            'users.create' => 'Crear usuarios',
            'users.edit' => 'Editar usuarios',
            'users.delete' => 'Eliminar usuarios',
            'users.export' => 'Exportar usuarios',
            'users.import' => 'Importar usuarios desde CSV',
            'users.assign_roles' => 'Asignar roles a usuarios',
            'users.impersonate' => 'Impersonar usuarios',
            
            // Votaciones Admin
            'votaciones.view' => 'Ver todas las votaciones',
            'votaciones.create' => 'Crear votaciones',
            'votaciones.edit' => 'Editar votaciones',
            'votaciones.delete' => 'Eliminar votaciones',
            'votaciones.manage_voters' => 'Gestionar votantes',
            
            // Asambleas Admin
            'asambleas.view' => 'Ver todas las asambleas',
            'asambleas.create' => 'Crear asambleas',
            'asambleas.edit' => 'Editar asambleas',
            'asambleas.delete' => 'Eliminar asambleas',
            'asambleas.manage_participants' => 'Gestionar participantes',
            
            // Convocatorias Admin
            'convocatorias.view' => 'Ver todas las convocatorias',
            'convocatorias.create' => 'Crear convocatorias',
            'convocatorias.edit' => 'Editar convocatorias',
            'convocatorias.delete' => 'Eliminar convocatorias',
            
            // Postulaciones Admin
            'postulaciones.view' => 'Ver todas las postulaciones',
            'postulaciones.review' => 'Revisar postulaciones',
            'postulaciones.approve' => 'Aprobar postulaciones',
            'postulaciones.reject' => 'Rechazar postulaciones',
            
            // Candidaturas Admin
            'candidaturas.view' => 'Ver todas las candidaturas',
            'candidaturas.create' => 'Crear candidaturas para otros',
            'candidaturas.approve' => 'Aprobar candidaturas',
            'candidaturas.reject' => 'Rechazar candidaturas',
            'candidaturas.configuracion' => 'Acceder a configuración de candidaturas',
            'candidaturas.notificaciones' => 'Enviar notificaciones de estado pendiente',
            'candidaturas.recordatorios' => 'Enviar recordatorios a borradores',
            'candidaturas.comment' => 'Comentar candidaturas',
            'candidaturas.aprobar_campos' => 'Aprobar campos individuales',
            
            // Cargos
            'cargos.view' => 'Ver cargos',
            'cargos.create' => 'Crear cargos',
            'cargos.edit' => 'Editar cargos',
            'cargos.delete' => 'Eliminar cargos',
            
            // Periodos Electorales
            'periodos.view' => 'Ver periodos electorales',
            'periodos.create' => 'Crear periodos electorales',
            'periodos.edit' => 'Editar periodos electorales',
            'periodos.delete' => 'Eliminar periodos electorales',
            
            // Reportes
            'reports.view' => 'Ver reportes',
            'reports.export' => 'Exportar reportes',
            'reports.generate' => 'Generar reportes',
            
            // Roles
            'roles.view' => 'Ver roles',
            'roles.create' => 'Crear roles',
            'roles.edit' => 'Editar roles',
            'roles.delete' => 'Eliminar roles',
            
            // Segmentos
            'segments.view' => 'Ver segmentos',
            'segments.create' => 'Crear segmentos',
            'segments.edit' => 'Editar segmentos',
            'segments.delete' => 'Eliminar segmentos',
            
            // Configuración
            'settings.view' => 'Ver configuración',
            'settings.edit' => 'Editar configuración',
            
            // Dashboard Admin
            'dashboard.admin' => 'Ver dashboard administrativo',
            
            // Formularios Admin
            'formularios.view' => 'Ver todos los formularios',
            'formularios.create' => 'Crear formularios',
            'formularios.edit' => 'Editar formularios',
            'formularios.delete' => 'Eliminar formularios',
            'formularios.view_responses' => 'Ver respuestas de formularios',
            'formularios.export' => 'Exportar respuestas',
            'formularios.manage_permissions' => 'Gestionar permisos de formularios',
            
            // Tenants (Super Admin)
            'tenants.view' => 'Ver tenants',
            'tenants.create' => 'Crear tenants',
            'tenants.edit' => 'Editar tenants',
            'tenants.delete' => 'Eliminar tenants',
            'tenants.switch' => 'Cambiar entre tenants',
            
            // Permisos Frontend (usuarios no administrativos)
            // Votaciones Frontend
            'votaciones.view_public' => 'Ver votaciones disponibles',
            'votaciones.vote' => 'Participar en votaciones',
            'votaciones.view_results' => 'Ver resultados públicos',
            'votaciones.view_own_vote' => 'Ver mi voto emitido',
            
            // Asambleas Frontend
            'asambleas.view_public' => 'Ver asambleas públicas',
            'asambleas.participate' => 'Participar en asambleas',
            'asambleas.view_minutes' => 'Ver actas de asambleas',
            
            // Convocatorias Frontend
            'convocatorias.view_public' => 'Ver convocatorias públicas',
            'convocatorias.apply' => 'Aplicar a convocatorias',
            
            // Postulaciones Frontend
            'postulaciones.create' => 'Crear postulaciones propias',
            'postulaciones.view_own' => 'Ver postulaciones propias',
            'postulaciones.edit_own' => 'Editar postulaciones propias',
            'postulaciones.delete_own' => 'Eliminar postulaciones propias',
            
            // Candidaturas Frontend
            'candidaturas.create_own' => 'Crear candidatura propia',
            'candidaturas.view_own' => 'Ver candidatura propia',
            'candidaturas.edit_own' => 'Editar candidatura propia',
            'candidaturas.view_public' => 'Ver candidaturas públicas',
            
            // Perfil
            'profile.view' => 'Ver perfil propio',
            'profile.edit' => 'Editar perfil propio',
            'profile.change_password' => 'Cambiar contraseña',
            
            // Dashboard Usuario
            'dashboard.view' => 'Ver dashboard personal',
            
            // Formularios Frontend
            'formularios.view_public' => 'Ver formularios públicos',
            'formularios.fill_public' => 'Llenar formularios públicos',
        ];

        // Crear todos los permisos
        foreach ($permissions as $name => $description) {
            // Spatie Permission no tiene campo description por defecto
            // Solo crear el permiso con name y guard_name
            Permission::firstOrCreate([
                'name' => $name, 
                'guard_name' => 'web'
            ]);
        }

        // Sincronizar permisos con roles existentes
        $this->syncExistingRoles();

        $this->command->info('Permisos creados exitosamente.');
    }

    /**
     * Sincronizar permisos con roles existentes basándose en sus configuraciones actuales
     */
    private function syncExistingRoles(): void
    {
        // Super Admin - todos los permisos
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
            $this->command->info('Rol super_admin sincronizado con todos los permisos.');
        }

        // Admin - permisos administrativos específicos
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $adminPermissions = [
                // Usuarios
                'users.view', 'users.create', 'users.edit', 'users.delete', 
                'users.export', 'users.import', 'users.assign_roles',
                // Votaciones
                'votaciones.view', 'votaciones.create', 'votaciones.edit', 
                'votaciones.delete', 'votaciones.manage_voters',
                // Asambleas
                'asambleas.view', 'asambleas.create', 'asambleas.edit', 
                'asambleas.delete', 'asambleas.manage_participants',
                // Convocatorias
                'convocatorias.view', 'convocatorias.create', 'convocatorias.edit', 
                'convocatorias.delete',
                // Postulaciones
                'postulaciones.view', 'postulaciones.review', 'postulaciones.approve', 
                'postulaciones.reject',
                // Candidaturas
                'candidaturas.view', 'candidaturas.create', 'candidaturas.approve', 
                'candidaturas.reject', 'candidaturas.configuracion', 
                'candidaturas.notificaciones', 'candidaturas.recordatorios',
                'candidaturas.comment', 'candidaturas.aprobar_campos',
                // Cargos
                'cargos.view', 'cargos.create', 'cargos.edit', 'cargos.delete',
                // Periodos
                'periodos.view', 'periodos.create', 'periodos.edit', 'periodos.delete',
                // Roles y Segmentos
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                'segments.view', 'segments.create', 'segments.edit', 'segments.delete',
                // Reportes
                'reports.view', 'reports.export', 'reports.generate',
                // Configuración
                'settings.view', 'settings.edit',
                // Dashboard
                'dashboard.admin',
                // Formularios
                'formularios.view', 'formularios.create', 'formularios.edit', 
                'formularios.delete', 'formularios.view_responses', 
                'formularios.export', 'formularios.manage_permissions',
            ];
            $admin->syncPermissions($adminPermissions);
            $this->command->info('Rol admin sincronizado con permisos administrativos.');
        }

        // Manager - permisos de supervisión
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $managerPermissions = [
                // Vista de usuarios
                'users.view', 'users.edit',
                // Gestión de votaciones
                'votaciones.view', 'votaciones.create', 'votaciones.edit',
                // Vista de asambleas
                'asambleas.view',
                // Gestión de convocatorias
                'convocatorias.view', 'convocatorias.create',
                // Vista de postulaciones
                'postulaciones.view',
                // Vista de candidaturas
                'candidaturas.view',
                // Reportes
                'reports.view',
                // Dashboard
                'dashboard.admin',
                // Formularios
                'formularios.view', 'formularios.view_responses',
            ];
            $manager->syncPermissions($managerPermissions);
            $this->command->info('Rol manager sincronizado con permisos de supervisión.');
        }

        // User - permisos de usuario estándar
        $user = Role::where('name', 'user')->first();
        if ($user) {
            $userPermissions = [
                // Dashboard
                'dashboard.view',
                // Votaciones
                'votaciones.view_public', 'votaciones.vote', 'votaciones.view_results', 'votaciones.view_own_vote',
                // Asambleas
                'asambleas.view_public', 'asambleas.participate',
                // Convocatorias
                'convocatorias.view_public', 'convocatorias.apply',
                // Postulaciones
                'postulaciones.create', 'postulaciones.view_own', 
                'postulaciones.edit_own', 'postulaciones.delete_own',
                // Candidaturas
                'candidaturas.create_own', 'candidaturas.view_own', 
                'candidaturas.edit_own', 'candidaturas.view_public',
                // Perfil
                'profile.view', 'profile.edit', 'profile.change_password',
                // Formularios
                'formularios.view_public', 'formularios.fill_public',
            ];
            $user->syncPermissions($userPermissions);
            $this->command->info('Rol user sincronizado con permisos de usuario.');
        }

        // End Customer - permisos limitados
        $endCustomer = Role::where('name', 'end_customer')->first();
        if ($endCustomer) {
            $endCustomerPermissions = [
                // Dashboard
                'dashboard.view',
                // Votaciones básicas
                'votaciones.view_public', 'votaciones.vote',
                // Vista de convocatorias
                'convocatorias.view_public',
                // Perfil básico
                'profile.view', 'profile.edit',
                // Formularios públicos
                'formularios.view_public', 'formularios.fill_public',
            ];
            $endCustomer->syncPermissions($endCustomerPermissions);
            $this->command->info('Rol end_customer sincronizado con permisos básicos.');
        }
    }
}