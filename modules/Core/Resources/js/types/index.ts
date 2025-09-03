import type { LucideIcon } from 'lucide-vue-next';

export interface Role {
    id: number;
    name: string;
    display_name: string;
    description?: string;
    permissions?: string[];
    allowed_modules?: string[];
}

export interface Auth {
    user: User;
    roles?: Role[];
    permissions?: string[];
    isSuperAdmin?: boolean;
    isAdmin?: boolean;
    hasAdministrativeRole?: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    url?: string;
    href?: string;
    icon?: LucideIcon;
    isActive?: boolean;
    isCollapsible?: boolean;
    isInternal?: boolean;
    isExternal?: boolean;
    items?: NavItem[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    territorio_id: number | null;
    departamento_id: number | null;
    municipio_id: number | null;
    activo: boolean;
    es_admin?: boolean; // Ahora es opcional ya que será removido
    roles?: Role[];
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// Tipos para sistema de candidaturas
export interface Candidatura {
    id: number;
    estado: string;
    estado_label: string;
    estado_color: string;
    version: number;
    comentarios_admin?: string;
    fecha_aprobacion?: string;
    created_at: string;
    updated_at: string;
    tiene_datos: boolean;
    puede_editar: boolean;
    hay_campos_editables: boolean;
    formulario_data?: Record<string, any>;
}

export interface CandidaturaHistorial {
    id: number;
    version: number;
    formulario_data: Record<string, any>;
    formulario_data_con_nombres: Record<string, any>;
    configuracion_campos_en_momento?: any[];
    estado_en_momento: string;
    estado_label: string;
    estado_color: string;
    comentarios_admin_en_momento?: string;
    motivo_cambio?: string;
    resumen_cambios: string;
    fecha_formateada: string;
    created_by: string;
    created_at: string;
}

export interface HistorialPaginado {
    data: CandidaturaHistorial[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}
