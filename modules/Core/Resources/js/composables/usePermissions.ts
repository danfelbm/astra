import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    // Obtener permisos del usuario actual
    const authPermissions = computed(() => {
        return page.props.auth?.permissions || [];
    });

    // Obtener roles del usuario actual
    const authRoles = computed(() => {
        return page.props.auth?.roles || [];
    });

    // Verificar si es super admin
    const authIsSuperAdmin = computed(() => {
        return authRoles.value.some((role: any) => role.name === 'super_admin');
    });

    // Verificar si tiene rol administrativo
    const hasAdministrativeRole = computed(() => {
        return page.props.auth?.hasAdministrativeRole || false;
    });

    // Función para verificar un permiso específico
    const hasPermission = (permission: string): boolean => {
        // Super admin tiene todos los permisos
        if (authIsSuperAdmin.value) return true;

        // Verificar si el permiso está en la lista de permisos del usuario
        return authPermissions.value.includes(permission);
    };

    // Función para verificar múltiples permisos (requiere todos)
    const hasAllPermissions = (permissions: string[]): boolean => {
        if (authIsSuperAdmin.value) return true;
        return permissions.every(permission => hasPermission(permission));
    };

    // Función para verificar si tiene al menos uno de los permisos
    const hasAnyPermission = (permissions: string[]): boolean => {
        if (authIsSuperAdmin.value) return true;
        return permissions.some(permission => hasPermission(permission));
    };

    // Función para verificar un rol específico
    const hasRole = (roleName: string): boolean => {
        return authRoles.value.some((role: any) => role.name === roleName);
    };

    // Función para verificar múltiples roles (requiere todos)
    const hasAllRoles = (roleNames: string[]): boolean => {
        return roleNames.every(roleName => hasRole(roleName));
    };

    // Función para verificar si tiene al menos uno de los roles
    const hasAnyRole = (roleNames: string[]): boolean => {
        return roleNames.some(roleName => hasRole(roleName));
    };

    return {
        authPermissions,
        authRoles,
        authIsSuperAdmin,
        hasAdministrativeRole,
        hasPermission,
        hasAllPermissions,
        hasAnyPermission,
        hasRole,
        hasAllRoles,
        hasAnyRole
    };
}

// Export tipo para usar en componentes TypeScript
export type UsePermissionsReturn = ReturnType<typeof usePermissions>;