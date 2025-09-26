// Tipos para el sistema de hitos y entregables

export type EstadoHito = 'pendiente' | 'en_progreso' | 'completado' | 'cancelado';
export type EstadoEntregable = 'pendiente' | 'en_progreso' | 'completado' | 'cancelado';
export type PrioridadEntregable = 'baja' | 'media' | 'alta';
export type RolUsuario = 'responsable' | 'colaborador' | 'revisor';

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

export interface Proyecto {
    id: number;
    nombre: string;
    estado?: string;
    descripcion?: string;
    responsable_id?: number;
}

export interface Hito {
    id: number;
    proyecto_id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    orden: number;
    estado: EstadoHito;
    porcentaje_completado: number;
    responsable_id?: number;
    tenant_id?: number;
    created_by?: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;

    // Relaciones
    proyecto?: Proyecto;
    responsable?: User;
    entregables?: Entregable[];

    // Atributos computados
    estado_label?: string;
    estado_color?: string;
    dias_restantes?: number | null;
    total_entregables?: number;
    entregables_completados?: number;
    esta_vencido?: boolean;
    esta_proximo_vencer?: boolean;
}

export interface Entregable {
    id: number;
    hito_id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    orden: number;
    estado: EstadoEntregable;
    prioridad: PrioridadEntregable;
    responsable_id?: number;
    completado_at?: string;
    completado_por?: number;
    notas_completado?: string;
    tenant_id?: number;
    created_by?: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;

    // Relaciones
    hito?: Hito;
    responsable?: User;
    completado_por_user?: User;
    usuarios?: UsuarioEntregable[];

    // Atributos computados
    estado_label?: string;
    estado_color?: string;
    prioridad_label?: string;
    prioridad_color?: string;
    dias_restantes?: number | null;
    esta_vencido?: boolean;
    esta_proximo_vencer?: boolean;
    duracion_dias?: number | null;
}

export interface UsuarioEntregable {
    id: number;
    name: string;
    email: string;
    pivot: {
        rol: RolUsuario;
        created_at?: string;
        updated_at?: string;
    };
}

// Interfaces para formularios
export interface HitoFormData {
    proyecto_id: string | number;
    nombre: string;
    descripcion?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    estado: EstadoHito;
    responsable_id?: string | number;
    entregables?: EntregableFormData[];
}

export interface EntregableFormData {
    hito_id?: string | number;
    nombre: string;
    descripcion?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    estado: EstadoEntregable;
    prioridad: PrioridadEntregable;
    responsable_id?: string | number;
    usuarios?: Record<number, RolUsuario>;
}

// Interfaces para filtros
export interface HitoFilters {
    search?: string;
    proyecto_id?: number;
    estado?: EstadoHito;
    responsable_id?: number;
    vencidos?: boolean;
    proximos_vencer?: boolean;
    page?: number;
    per_page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
}

export interface EntregableFilters {
    search?: string;
    hito_id?: number;
    proyecto_id?: number;
    estado?: EstadoEntregable;
    prioridad?: PrioridadEntregable;
    responsable_id?: number;
    vencidos?: boolean;
    proximos_vencer?: boolean;
    page?: number;
    per_page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
}

// Interfaces para estadísticas
export interface HitoEstadisticas {
    total: number;
    pendientes: number;
    en_progreso: number;
    completados: number;
    cancelados: number;
    vencidos: number;
    proximos_vencer: number;
}

export interface EntregableEstadisticas {
    total: number;
    pendientes: number;
    en_progreso: number;
    completados: number;
    cancelados: number;
    vencidos: number;
    alta_prioridad: number;
}

// Props para componentes
export interface HitoCardProps {
    hito: Hito;
    showProyecto?: boolean;
    showActions?: boolean;
    onEdit?: (hito: Hito) => void;
    onDelete?: (hito: Hito) => void;
    onDuplicate?: (hito: Hito) => void;
    onChangeStatus?: (hito: Hito, estado: EstadoHito) => void;
}

export interface EntregableItemProps {
    entregable: Entregable;
    showHito?: boolean;
    showActions?: boolean;
    onEdit?: (entregable: Entregable) => void;
    onDelete?: (entregable: Entregable) => void;
    onComplete?: (entregable: Entregable) => void;
    onAssignUsers?: (entregable: Entregable) => void;
}

// Helpers de tipo
export const EstadoHitoLabels: Record<EstadoHito, string> = {
    'pendiente': 'Pendiente',
    'en_progreso': 'En Progreso',
    'completado': 'Completado',
    'cancelado': 'Cancelado'
};

export const EstadoEntregableLabels: Record<EstadoEntregable, string> = {
    'pendiente': 'Pendiente',
    'en_progreso': 'En Progreso',
    'completado': 'Completado',
    'cancelado': 'Cancelado'
};

export const PrioridadLabels: Record<PrioridadEntregable, string> = {
    'baja': 'Baja',
    'media': 'Media',
    'alta': 'Alta'
};

export const EstadoHitoColors: Record<EstadoHito, string> = {
    'pendiente': 'gray',
    'en_progreso': 'yellow',
    'completado': 'green',
    'cancelado': 'red'
};

export const EstadoEntregableColors: Record<EstadoEntregable, string> = {
    'pendiente': 'gray',
    'en_progreso': 'yellow',
    'completado': 'green',
    'cancelado': 'red'
};

export const PrioridadColors: Record<PrioridadEntregable, string> = {
    'baja': 'gray',
    'media': 'blue',
    'alta': 'red'
};

// Funciones de utilidad
export function getEstadoHitoLabel(estado: EstadoHito): string {
    return EstadoHitoLabels[estado] || estado;
}

export function getEstadoEntregableLabel(estado: EstadoEntregable): string {
    return EstadoEntregableLabels[estado] || estado;
}

export function getPrioridadLabel(prioridad: PrioridadEntregable): string {
    return PrioridadLabels[prioridad] || prioridad;
}

export function getEstadoHitoColor(estado: EstadoHito): string {
    return EstadoHitoColors[estado] || 'gray';
}

export function getEstadoEntregableColor(estado: EstadoEntregable): string {
    return EstadoEntregableColors[estado] || 'gray';
}

export function getPrioridadColor(prioridad: PrioridadEntregable): string {
    return PrioridadColors[prioridad] || 'gray';
}

export function calcularProgresoHito(hito: Hito): number {
    if (!hito.entregables || hito.entregables.length === 0) {
        return 0;
    }

    const completados = hito.entregables.filter(e => e.estado === 'completado').length;
    return Math.round((completados / hito.entregables.length) * 100);
}

export function esHitoVencido(hito: Hito): boolean {
    if (hito.estado === 'completado' || hito.estado === 'cancelado') {
        return false;
    }

    if (!hito.fecha_fin) {
        return false;
    }

    return new Date(hito.fecha_fin) < new Date();
}

export function esHitoProximoVencer(hito: Hito, dias: number = 7): boolean {
    if (esHitoVencido(hito)) {
        return false;
    }

    if (hito.estado === 'completado' || hito.estado === 'cancelado') {
        return false;
    }

    if (!hito.fecha_fin) {
        return false;
    }

    const fechaFin = new Date(hito.fecha_fin);
    const fechaLimite = new Date();
    fechaLimite.setDate(fechaLimite.getDate() + dias);

    return fechaFin <= fechaLimite && fechaFin > new Date();
}