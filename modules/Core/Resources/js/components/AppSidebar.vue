<script setup lang="ts">
import NavFooter from "./NavFooter.vue";
import NavMain from "./NavMain.vue";
import NavUser from "./NavUser.vue";
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from "./ui/sidebar";
import { type NavItem, type SharedData, type User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Folder, LayoutGrid, Vote, Users, BarChart3, FileText, Settings, Briefcase, Calendar, Megaphone, UserCheck, ClipboardList, Building2, Shield, Target, UserCog, Database, Lock, ExternalLink, Mail, MessageSquare, Send, FolderOpen, Tag } from 'lucide-vue-next';
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
        url: authHasAdministrativeRole ? '/admin/dashboard' : '/miembro/dashboard',
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
    // NOTA: Este sidebar SOLO se muestra en el panel administrativo
    // Los usuarios regulares tienen su propio layout sin sidebar
    if (authHasAdministrativeRole && !isSuperAdmin()) {
        // Usuarios - como elemento de primer nivel
        if (hasPermission('users.view') && hasModuleAccess('users')) {
            items.push({
                title: 'Usuarios',
                url: '/admin/usuarios',
                icon: Users,
            });
        }
        
        // Segmentos - como elemento de primer nivel
        if (hasPermission('segments.view') && hasModuleAccess('segments')) {
            items.push({
                title: 'Segmentos',
                url: '/admin/segments',
                icon: Target,
            });
        }
        
        // Roles y Permisos - como elemento de primer nivel
        if (hasPermission('roles.view') && hasModuleAccess('roles')) {
            items.push({
                title: 'Roles y Permisos',
                url: '/admin/roles',
                icon: Shield,
            });
        }
        
        // Sección CRM - Solo Formularios
        const crmItems: NavItem[] = [];
        
        if (hasPermission('formularios.view') && hasModuleAccess('formularios')) {
            crmItems.push({
                title: 'Formularios',
                url: '/admin/formularios',
                icon: FileText,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (crmItems.length > 0) {
            items.push({
                title: 'CRM',
                icon: UserCog,
                isCollapsible: true,
                items: crmItems,
            });
        }

        // Sección Elecciones - con Cargos, Periodos, Convocatorias, Candidaturas, Postulaciones
        const electoralItems: NavItem[] = [];
        
        if (hasPermission('cargos.view') && hasModuleAccess('cargos')) {
            electoralItems.push({
                title: 'Cargos',
                url: '/admin/cargos',
                icon: Briefcase,
            });
        }
        
        if (hasPermission('periodos.view') && hasModuleAccess('periodos')) {
            electoralItems.push({
                title: 'Periodos Electorales',
                url: '/admin/periodos-electorales',
                icon: Calendar,
            });
        }
        
        if (hasPermission('convocatorias.view') && hasModuleAccess('convocatorias')) {
            electoralItems.push({
                title: 'Convocatorias',
                url: '/admin/convocatorias',
                icon: Megaphone,
            });
        }
        
        if (hasPermission('candidaturas.view') && hasModuleAccess('candidaturas')) {
            electoralItems.push({
                title: 'Candidaturas',
                url: '/admin/candidaturas',
                icon: UserCheck,
            });
        }
        
        if (hasPermission('postulaciones.view') && hasModuleAccess('postulaciones')) {
            electoralItems.push({
                title: 'Postulaciones',
                url: '/admin/postulaciones',
                icon: ClipboardList,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (electoralItems.length > 0) {
            items.push({
                title: 'Elecciones',
                icon: Vote,
                isCollapsible: true,
                items: electoralItems,
            });
        }

        // Asambleas - como elemento de primer nivel
        if (hasPermission('asambleas.view') && hasModuleAccess('asambleas')) {
            items.push({
                title: 'Asambleas',
                url: '/admin/asambleas',
                icon: Users,
            });
        }
        
        // Votaciones - como elemento de primer nivel
        if (hasPermission('votaciones.view') && hasModuleAccess('votaciones')) {
            items.push({
                title: 'Votaciones',
                url: '/admin/votaciones',
                icon: Vote,
            });
        }

        // Sección Proyectos - con submenú
        const proyectosItems: NavItem[] = [];

        if (hasPermission('proyectos.view') && hasModuleAccess('proyectos')) {
            proyectosItems.push({
                title: 'Proyectos',
                url: '/admin/proyectos',
                icon: Folder,
            });
        }

        if (hasPermission('proyectos.manage_fields') && hasModuleAccess('proyectos')) {
            proyectosItems.push({
                title: 'Campos Personalizados',
                url: '/admin/campos-personalizados',
                icon: Settings,
            });
        }

        if (hasPermission('categorias_etiquetas.view') && hasModuleAccess('proyectos')) {
            proyectosItems.push({
                title: 'Categorías de Etiquetas',
                url: '/admin/categorias-etiquetas',
                icon: Tag,
            });
        }

        if (hasPermission('contratos.view') && hasModuleAccess('proyectos')) {
            proyectosItems.push({
                title: 'Contratos',
                url: '/admin/contratos',
                icon: FileText,
            });
        }

        // Solo agregar la sección si hay elementos
        if (proyectosItems.length > 0) {
            items.push({
                title: 'Proyectos',
                icon: FolderOpen,
                isCollapsible: true,
                items: proyectosItems,
            });
        }

        // Sección Campañas - Email y WhatsApp
        const campanasItems: NavItem[] = [];
        
        if (hasPermission('campanas.plantillas.view')) {
            campanasItems.push({
                title: 'Plantillas Email',
                url: '/admin/campanas/plantillas-email',
                icon: Mail,
            });
            campanasItems.push({
                title: 'Plantillas WhatsApp',
                url: '/admin/campanas/plantillas-whatsapp',
                icon: MessageSquare,
            });
        }
        
        if (hasPermission('campanas.view')) {
            campanasItems.push({
                title: 'Campañas',
                url: '/admin/campanas',
                icon: Send,
            });
        }
        
        // Solo agregar la sección si hay elementos
        if (campanasItems.length > 0) {
            items.push({
                title: 'Campañas',
                icon: Send,
                isCollapsible: true,
                items: campanasItems,
            });
        }

        // Sección de Análisis - COMENTADO: Funcionalidad aún no implementada
        // const analysisItems: NavItem[] = [];
        // 
        // if (hasAnyPermission(['reports.view', 'reports.export']) && hasModuleAccess('reports')) {
        //     analysisItems.push({
        //         title: 'Resultados',
        //         url: '/admin/resultados',
        //         icon: BarChart3,
        //     });
        // }
        // 
        // if (hasAnyPermission(['auditoría.view', 'auditoría.export'])) {
        //     analysisItems.push({
        //         title: 'Auditoría',
        //         url: '/admin/auditoria',
        //         icon: FileText,
        //     });
        // }
        // 
        // // Solo agregar la sección si hay elementos
        // if (analysisItems.length > 0) {
        //     items.push({
        //         title: 'Análisis',
        //         icon: BarChart3,
        //         isCollapsible: true,
        //         items: analysisItems,
        //     });
        // }

        // Configuración - mostrar si tiene permisos de configuración
        if (hasPermission('settings.view')) {
            items.push({
                title: 'Configuración',
                url: '/admin/configuracion',
                icon: Settings,
            });
        }
    }
    
    // NOTA: Los usuarios sin permisos administrativos usan un layout diferente
    // Este sidebar SOLO se muestra en el panel administrativo
    
    return items;
});

const footerNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    
    // Agregar enlace para cambiar a vista de usuario si el admin tiene rol user
    if (authHasAdministrativeRole && user?.roles?.some((role: any) => role.name === 'user')) {
        items.push({
            title: 'Cambiar a Vista Usuario',
            url: '/miembro/dashboard',
            icon: ExternalLink,
            isInternal: true, // Marcar como enlace interno
        });
    }
    
    return items;
});
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
