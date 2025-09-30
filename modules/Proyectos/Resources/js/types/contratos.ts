// Tipos para el sistema de contratos

export type EstadoContrato = 'borrador' | 'activo' | 'finalizado' | 'cancelado';
export type TipoContrato = 'servicio' | 'obra' | 'suministro' | 'consultoria' | 'otro';
export type TipoCampo = 'text' | 'number' | 'date' | 'textarea' | 'select' | 'checkbox' | 'radio' | 'file' | 'email' | 'url';

export interface Proyecto {
    id: number;
    nombre: string;
    estado?: string;
    descripcion?: string;
    responsable_id?: number;
}

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface ArchivoContrato {
    path: string;
    nombre: string;
    url: string;
    indice: number;
}

export interface Contrato {
    id: number;
    proyecto_id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: EstadoContrato;
    tipo: TipoContrato;
    monto_total?: number;
    monto_formateado?: string;
    moneda: string;
    responsable_id?: number;
    contraparte_user_id?: number;
    contraparte_nombre?: string;
    contraparte_identificacion?: string;
    contraparte_email?: string;
    contraparte_telefono?: string;
    archivo_pdf?: string;
    // Múltiples archivos
    archivos_paths?: string[];
    archivos_nombres?: string[];
    tipos_archivos?: Record<string, string>;
    total_archivos?: number;
    archivos_urls?: string[];
    archivos_info?: ArchivoContrato[];
    tiene_multiples_archivos?: boolean;
    observaciones?: string;
    dias_restantes?: number;
    porcentaje_transcurrido?: number;
    esta_vencido?: boolean;
    esta_proximo_vencer?: boolean;
    tenant_id?: number;
    created_by?: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;
    // Relaciones
    proyecto?: Proyecto;
    responsable?: User;
    contraparte_user?: User;
    created_by_user?: User;
    updated_by_user?: User;
    campos_personalizados?: ValorCampoPersonalizado[];
}

export interface CampoPersonalizadoContrato {
    id: number;
    nombre: string;
    slug: string;
    tipo: TipoCampo;
    opciones?: string[];
    es_requerido: boolean;
    orden: number;
    activo: boolean;
    descripcion?: string;
    placeholder?: string;
    validacion?: string;
    tenant_id?: number;
    created_at: string;
    updated_at: string;
    // Relaciones
    valores_count?: number;
}

export interface ValorCampoPersonalizado {
    id: number;
    contrato_id: number;
    campo_personalizado_contrato_id: number;
    valor: any;
    valor_formateado?: string;
    created_at: string;
    updated_at: string;
    // Relaciones
    campo?: CampoPersonalizadoContrato;
    contrato?: Contrato;
}

// Interfaces para formularios
export interface ContratoFormData {
    proyecto_id: string | number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: EstadoContrato;
    tipo: TipoContrato;
    monto_total?: string | number;
    moneda: string;
    responsable_id?: string | number;
    contraparte_nombre?: string;
    contraparte_identificacion?: string;
    contraparte_email?: string;
    contraparte_telefono?: string;
    archivo_pdf?: File | null;
    // Múltiples archivos
    archivos_paths?: string[];
    archivos_nombres?: string[];
    tipos_archivos?: Record<string, string>;
    observaciones?: string;
    campos_personalizados: Record<number, any>;
}

export interface CampoPersonalizadoFormData {
    nombre: string;
    slug?: string;
    tipo: TipoCampo;
    opciones?: string[];
    es_requerido: boolean;
    activo: boolean;
    orden?: number;
    descripcion?: string;
    placeholder?: string;
    validacion?: string;
}

// Interfaces para filtros
export interface ContratoFilters {
    search?: string;
    proyecto_id?: number;
    estado?: EstadoContrato;
    tipo?: TipoContrato;
    responsable_id?: number;
    vencidos?: boolean;
    proximos_vencer?: boolean;
    fecha_inicio_desde?: string;
    fecha_inicio_hasta?: string;
    fecha_fin_desde?: string;
    fecha_fin_hasta?: string;
    monto_min?: number;
    monto_max?: number;
    page?: number;
    per_page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
}

// Interfaces para estadísticas
export interface ContratoEstadisticas {
    total: number;
    activos: number;
    borradores: number;
    finalizados: number;
    cancelados: number;
    vencidos: number;
    proximos_vencer: number;
    monto_total: string;
    monto_activos: string;
    por_tipo: Record<TipoContrato, number>;
    por_mes: Array<{
        mes: string;
        cantidad: number;
        monto: number;
    }>;
}

// Props para componentes
export interface ContratoCardProps {
    contrato: Contrato;
    showProyecto?: boolean;
    showActions?: boolean;
    onEdit?: (contrato: Contrato) => void;
    onDelete?: (contrato: Contrato) => void;
    onDuplicate?: (contrato: Contrato) => void;
    onChangeStatus?: (contrato: Contrato, estado: EstadoContrato) => void;
}

export interface ContratoListProps {
    contratos: Contrato[];
    loading?: boolean;
    showProyecto?: boolean;
    showActions?: boolean;
    onSelect?: (contrato: Contrato) => void;
    selectedIds?: number[];
}

export interface ContratoTimelineProps {
    contratos: Contrato[];
    showProyecto?: boolean;
    orientation?: 'horizontal' | 'vertical';
    groupBy?: 'estado' | 'tipo' | 'mes';
}

export interface CampoPersonalizadoInputProps {
    campo: CampoPersonalizadoContrato;
    modelValue: any;
    disabled?: boolean;
    error?: string;
}

// Helpers de tipo
export const EstadoContratoLabels: Record<EstadoContrato, string> = {
    'borrador': 'Borrador',
    'activo': 'Activo',
    'finalizado': 'Finalizado',
    'cancelado': 'Cancelado'
};

export const TipoContratoLabels: Record<TipoContrato, string> = {
    'servicio': 'Servicio',
    'obra': 'Obra',
    'suministro': 'Suministro',
    'consultoria': 'Consultoría',
    'otro': 'Otro'
};

export const EstadoContratoColors: Record<EstadoContrato, string> = {
    'borrador': 'gray',
    'activo': 'green',
    'finalizado': 'blue',
    'cancelado': 'red'
};

export const TipoContratoIcons: Record<TipoContrato, string> = {
    'servicio': 'briefcase',
    'obra': 'hard-hat',
    'suministro': 'package',
    'consultoria': 'users',
    'otro': 'file-text'
};

// Funciones de utilidad
export function getEstadoLabel(estado: EstadoContrato): string {
    return EstadoContratoLabels[estado] || estado;
}

export function getTipoLabel(tipo: TipoContrato): string {
    return TipoContratoLabels[tipo] || tipo;
}

export function getEstadoColor(estado: EstadoContrato): string {
    return EstadoContratoColors[estado] || 'gray';
}

export function getTipoIcon(tipo: TipoContrato): string {
    return TipoContratoIcons[tipo] || 'file-text';
}

export function formatMonto(monto: number | undefined, moneda: string = 'USD'): string {
    if (!monto) return '-';

    const formatter = new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: moneda,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    return formatter.format(monto);
}

export function calcularDiasRestantes(fechaFin: string | undefined): number | null {
    if (!fechaFin) return null;

    const hoy = new Date();
    const fin = new Date(fechaFin);
    const diferencia = fin.getTime() - hoy.getTime();

    return Math.ceil(diferencia / (1000 * 60 * 60 * 24));
}

export function calcularPorcentajeTranscurrido(fechaInicio: string, fechaFin: string | undefined): number {
    if (!fechaFin) return 0;

    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    const hoy = new Date();

    if (hoy < inicio) return 0;
    if (hoy > fin) return 100;

    const total = fin.getTime() - inicio.getTime();
    const transcurrido = hoy.getTime() - inicio.getTime();

    return Math.round((transcurrido / total) * 100);
}

export function estaVencido(contrato: Contrato): boolean {
    if (contrato.estado !== 'activo' || !contrato.fecha_fin) return false;

    const hoy = new Date();
    const fin = new Date(contrato.fecha_fin);

    return hoy > fin;
}

export function estaProximoVencer(contrato: Contrato, dias: number = 30): boolean {
    if (contrato.estado !== 'activo' || !contrato.fecha_fin) return false;

    const diasRestantes = calcularDiasRestantes(contrato.fecha_fin);

    return diasRestantes !== null && diasRestantes > 0 && diasRestantes <= dias;
}

// Validadores
export function validarContrato(data: ContratoFormData): Record<string, string> {
    const errores: Record<string, string> = {};

    if (!data.proyecto_id) {
        errores.proyecto_id = 'El proyecto es obligatorio';
    }

    if (!data.nombre || data.nombre.trim().length === 0) {
        errores.nombre = 'El nombre es obligatorio';
    }

    if (!data.fecha_inicio) {
        errores.fecha_inicio = 'La fecha de inicio es obligatoria';
    }

    if (data.fecha_fin && data.fecha_fin < data.fecha_inicio) {
        errores.fecha_fin = 'La fecha de fin debe ser posterior a la fecha de inicio';
    }

    if (data.contraparte_email && !validarEmail(data.contraparte_email)) {
        errores.contraparte_email = 'El email no es válido';
    }

    if (data.monto_total && Number(data.monto_total) < 0) {
        errores.monto_total = 'El monto no puede ser negativo';
    }

    return errores;
}

export function validarEmail(email: string): boolean {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

export function validarCampoPersonalizado(
    campo: CampoPersonalizadoContrato,
    valor: any
): string | null {
    // Campo requerido
    if (campo.es_requerido && !valor) {
        return `El campo ${campo.nombre} es obligatorio`;
    }

    // Validación según tipo
    switch (campo.tipo) {
        case 'number':
            if (valor && isNaN(Number(valor))) {
                return `${campo.nombre} debe ser un número`;
            }
            break;

        case 'email':
            if (valor && !validarEmail(valor)) {
                return `${campo.nombre} debe ser un email válido`;
            }
            break;

        case 'url':
            if (valor && !validarURL(valor)) {
                return `${campo.nombre} debe ser una URL válida`;
            }
            break;

        case 'date':
            if (valor && !Date.parse(valor)) {
                return `${campo.nombre} debe ser una fecha válida`;
            }
            break;
    }

    return null;
}

export function validarURL(url: string): boolean {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}