<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData, type User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Vote, Users, BarChart3, FileText, Settings, Briefcase, Calendar, Megaphone, UserCheck, ClipboardList, Building2, Shield, Target, UserCog, Database, Lock } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Obtener usuario actual y datos de autenticación
const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const authRoles = page.props.auth.roles || [];
const authPermissions = page.props.auth.permissions || [];
const authAllowedModules = page.props.auth.allowedModules || [];
const authIsSuperAdmin = page.props.auth.isSuperAdmin || false;
const authIsAdmin = page.props.auth.isAdmin || false;
const authHasAdministrativeRole = page.props.auth.hasAdministrativeRole || false;

// Función para verificar si el usuario tiene un rol específico
const hasRole = (roleName: string): boolean => {
    return authRoles.some((role: any) => role.name === roleName);
};

// Función para verificar si el usuario tiene un permiso específico
const hasPermission = (permission: string): boolean => {
    // Super admin siempre tiene todos los permisos
    if (authIsSuperAdmin) return true;
    
    // Verificar si tiene el permiso específico
    return authPermissions.includes(permission) || authPermissions.includes('*');
};

// Función para verificar si el usuario tiene alguno de los permisos dados
const hasAnyPermission = (permissions: string[]): boolean => {
    // Super admin siempre tiene todos los permisos
    if (authIsSuperAdmin) return true;
    
    return permissions.some(permission => hasPermission(permission));
};

// Función para verificar si es admin o super admin
const isAdmin = (): boolean => {
    return authIsAdmin;
};

// Función para verificar si es super admin
const isSuperAdmin = (): boolean => {
    return authIsSuperAdmin;
};

// Función para verificar si el usuario tiene acceso a un módulo específico
const hasModuleAccess = (module: string): boolean => {
    // Super admin siempre tiene acceso a todos los módulos
    if (authIsSuperAdmin) return true;
    
    // Si tiene el wildcard '*', tiene acceso a todo
    if (authAllowedModules.includes('*')) return true;
    
    // Verificar si tiene el módulo específico
    return authAllowedModules.includes(module);
};

// Función para verificar si tiene algún permiso administrativo
// NOTA: Esta función ya no se usa. Ahora usamos authHasAdministrativeRole que verifica
// si el usuario tiene algún rol con is_administrative = true
// const hasAdminAccess = (): boolean => {
//     if (isSuperAdmin() || isAdmin()) return true;
//     
//     // Lista de permisos que indican acceso administrativo
//     const adminPermissions = [
//         'users.view', 'users.create', 'users.edit', 'users.delete',
//         'votaciones.view', 'votaciones.create', 'votaciones.edit', 'votaciones.delete',
//         'convocatorias.view', 'convocatorias.create', 'convocatorias.edit', 'convocatorias.delete',
//         'postulaciones.view', 'postulaciones.create', 'postulaciones.review',
//         'candidaturas.view', 'candidaturas.create', 'candidaturas.approve',
//         'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
//         'segments.view', 'segments.create', 'segments.edit', 'segments.delete',
//         'cargos.view', 'cargos.create', 'cargos.edit', 'cargos.delete',
//         'periodos.view', 'periodos.create', 'periodos.edit', 'periodos.delete',
//         'dashboard.view', 'settings.view', 'settings.edit'
//     ];
//     
//     return hasAnyPermission(adminPermissions);
// };

// Menús condicionales basados en permisos específicos
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    
    // Dashboard siempre disponible
    items.push({
        title: 'Dashboard',
        url: authHasAdministrativeRole ? '/admin/dashboard' : '/dashboard',
        icon: LayoutGrid,
    });

    // Menú para Super Admin (solo gestión de sistema multi-tenant)
    if (isSuperAdmin()) {
        items.push({
            title: 'Tenants',
            url: '/admin/tenants',
            icon: Building2,
        });
        
        // Territorios es global, podría ser útil para super admin
        items.push({
            title: 'Territorios',
            url: '/admin/territorios',
            icon: Folder,
        });
        
        items.push({
            title: 'Configuración Global',
            url: '/admin/configuracion-global',
            icon: Settings,
        });
    }
    
    // Menú basado en permisos específicos para cualquier usuario con permisos administrativos
    if (authHasAdministrativeRole && !isSuperAdmin()) {
        // Sección de Administración - mostrar solo si tiene algún permiso relevante
        const adminItems: NavItem[] = [];
        
        if (hasAnyPermission(['users.view', 'users.create', 'users.edit', 'users.delete']) && hasModuleAccess('users')) {
            adminItems.push({
                title: 'Usuarios',
                url: '/admin/usuarios',
                icon: Users,
            });
        }
        
        if (hasAnyPermission(['roles.view', 'roles.create', 'roles.edit', 'roles.delete']) && hasModuleAccess('roles')) {
            adminItems.push({
                title: 'Roles y Permisos',
                url: '/admin/roles',
                icon: Shield,
            });
        }
        
        if (hasAnyPermission(['segments.view', 'segments.create', 'segments.edit', 'segments.delete']) && hasModuleAccess('segments')) {
            adminItems.push({
                title: 'Segmentos',
                url: '/admin/segments',
                icon: Target,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (adminItems.length > 0) {
            items.push({
                title: 'Administración',
                icon: Shield,
                isCollapsible: true,
                items: adminItems,
            });
        }

        // Sección de Gestión Electoral - mostrar solo si tiene algún permiso relevante
        const electoralItems: NavItem[] = [];
        
        if (hasAnyPermission(['votaciones.view', 'votaciones.create', 'votaciones.edit', 'votaciones.delete']) && hasModuleAccess('votaciones')) {
            electoralItems.push({
                title: 'Votaciones',
                url: '/admin/votaciones',
                icon: Vote,
            });
        }
        
        if (hasAnyPermission(['cargos.view', 'cargos.create', 'cargos.edit', 'cargos.delete']) && hasModuleAccess('cargos')) {
            electoralItems.push({
                title: 'Cargos',
                url: '/admin/cargos',
                icon: Briefcase,
            });
        }
        
        if (hasAnyPermission(['periodos.view', 'periodos.create', 'periodos.edit', 'periodos.delete']) && hasModuleAccess('periodos')) {
            electoralItems.push({
                title: 'Periodos Electorales',
                url: '/admin/periodos-electorales',
                icon: Calendar,
            });
        }
        
        if (hasAnyPermission(['asambleas.view', 'asambleas.create', 'asambleas.edit', 'asambleas.delete', 'asambleas.manage_participants']) && hasModuleAccess('asambleas')) {
            electoralItems.push({
                title: 'Asambleas',
                url: '/admin/asambleas',
                icon: Users,
            });
        }
        
        if (hasAnyPermission(['convocatorias.view', 'convocatorias.create', 'convocatorias.edit', 'convocatorias.delete']) && hasModuleAccess('convocatorias')) {
            electoralItems.push({
                title: 'Convocatorias',
                url: '/admin/convocatorias',
                icon: Megaphone,
            });
        }
        
        if (hasAnyPermission(['formularios.view', 'formularios.create', 'formularios.edit', 'formularios.delete', 'formularios.view_responses']) && hasModuleAccess('formularios')) {
            electoralItems.push({
                title: 'Formularios',
                url: '/admin/formularios',
                icon: FileText,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (electoralItems.length > 0) {
            items.push({
                title: 'Gestión Electoral',
                icon: Vote,
                isCollapsible: true,
                items: electoralItems,
            });
        }

        // Sección de Participantes - mostrar solo si tiene algún permiso relevante
        const participantItems: NavItem[] = [];
        
        if (hasAnyPermission(['candidaturas.view', 'candidaturas.create', 'candidaturas.approve']) && hasModuleAccess('candidaturas')) {
            participantItems.push({
                title: 'Candidaturas',
                url: '/admin/candidaturas',
                icon: UserCheck,
            });
        }
        
        if (hasAnyPermission(['postulaciones.view', 'postulaciones.create', 'postulaciones.review']) && hasModuleAccess('postulaciones')) {
            participantItems.push({
                title: 'Postulaciones',
                url: '/admin/postulaciones',
                icon: ClipboardList,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (participantItems.length > 0) {
            items.push({
                title: 'Participantes',
                icon: Users,
                isCollapsible: true,
                items: participantItems,
            });
        }

        // Sección de Análisis - mostrar solo si tiene algún permiso relevante
        const analysisItems: NavItem[] = [];
        
        if (hasAnyPermission(['reports.view', 'reports.export']) && hasModuleAccess('reports')) {
            analysisItems.push({
                title: 'Resultados',
                url: '/admin/resultados',
                icon: BarChart3,
            });
        }
        
        if (hasAnyPermission(['auditoría.view', 'auditoría.export'])) {
            analysisItems.push({
                title: 'Auditoría',
                url: '/admin/auditoria',
                icon: FileText,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (analysisItems.length > 0) {
            items.push({
                title: 'Análisis',
                icon: BarChart3,
                isCollapsible: true,
                items: analysisItems,
            });
        }

        // Configuración - mostrar si tiene permisos de configuración
        if (hasAnyPermission(['settings.view', 'settings.edit'])) {
            items.push({
                title: 'Configuración',
                url: '/admin/configuracion',
                icon: Settings,
            });
        }
    }
    
    // Menú para usuarios sin permisos administrativos
    if (!authHasAdministrativeRole) {
        // Menú dinámico basado en permisos de frontend
        
        // Votaciones
        if (hasAnyPermission(['votaciones.view_public', 'votaciones.vote', 'votaciones.view_results']) && hasModuleAccess('votaciones')) {
            items.push({
                title: 'Mis Votaciones',
                url: '/votaciones',
                icon: Vote,
            });
        }
        
        // Asambleas
        if (hasAnyPermission(['asambleas.view_public', 'asambleas.participate', 'asambleas.view_minutes']) && hasModuleAccess('asambleas')) {
            items.push({
                title: 'Asambleas',
                url: '/asambleas',
                icon: Users,
            });
        }
        
        // Convocatorias
        if (hasAnyPermission(['convocatorias.view_public', 'convocatorias.apply']) && hasModuleAccess('convocatorias')) {
            items.push({
                title: 'Convocatorias',
                url: '/convocatorias',
                icon: Megaphone,
            });
        }
        
        // Candidaturas
        if (hasAnyPermission(['candidaturas.create_own', 'candidaturas.view_own', 'candidaturas.edit_own', 'candidaturas.view_public']) && hasModuleAccess('candidaturas')) {
            items.push({
                title: 'Mi Candidatura',
                url: '/candidaturas',
                icon: UserCheck,
            });
        }
        
        // Postulaciones
        if (hasAnyPermission(['postulaciones.create', 'postulaciones.view_own', 'postulaciones.edit_own', 'postulaciones.delete_own']) && hasModuleAccess('postulaciones')) {
            items.push({
                title: 'Mis Postulaciones',
                url: '/postulaciones',
                icon: ClipboardList,
            });
        }
        
        // Formularios
        if (hasAnyPermission(['formularios.view_public', 'formularios.fill_public']) && hasModuleAccess('formularios')) {
            items.push({
                title: 'Formularios',
                url: '/formularios',
                icon: FileText,
            });
        }
        
        // Si el usuario no tiene ningún permiso específico, mostrar menú básico
        if (items.length === 1) { // Solo tiene Dashboard
            // Menú por defecto para usuarios sin permisos específicos
            items.push({
                title: 'Votaciones',
                url: '/votaciones',
                icon: Vote,
            });
            
            items.push({
                title: 'Asambleas',
                url: '/asambleas',
                icon: Users,
            });
            
            items.push({
                title: 'Convocatorias',
                url: '/convocatorias',
                icon: Megaphone,
            });
        }
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        url: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg">
                        <AppLogo />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
