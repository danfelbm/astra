/**
 * Composable para manejar la lógica de visualización de entregables
 * Incluye preferencias persistidas en localStorage y utilidades compartidas
 */
import { ref, computed, watch, type Ref, type ComputedRef } from 'vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import type {
    Entregable,
    EstadoEntregable,
    PrioridadEntregable,
    EntregablesViewMode,
    EntregablesAgrupados
} from '@modules/Proyectos/Resources/js/types/hitos';

// Claves de localStorage
const STORAGE_KEYS = {
    VIEW_MODE: 'entregables-view-mode',
    CONFIRM_ON_DRAG: 'entregables-confirm-drag',
} as const;

// Configuración de estados
export const ESTADO_CONFIG: Record<EstadoEntregable, {
    label: string;
    color: string;
    bgColor: string;
    dotColor: string;
}> = {
    pendiente: {
        label: 'Pendiente',
        color: 'text-gray-800',
        bgColor: 'bg-gray-100',
        dotColor: 'bg-gray-400',
    },
    en_progreso: {
        label: 'En Progreso',
        color: 'text-blue-800',
        bgColor: 'bg-blue-100',
        dotColor: 'bg-blue-500',
    },
    completado: {
        label: 'Completado',
        color: 'text-green-800',
        bgColor: 'bg-green-100',
        dotColor: 'bg-green-500',
    },
    cancelado: {
        label: 'Cancelado',
        color: 'text-red-800',
        bgColor: 'bg-red-100',
        dotColor: 'bg-red-500',
    },
};

// Configuración de prioridades
export const PRIORIDAD_CONFIG: Record<PrioridadEntregable, {
    label: string;
    color: string;
    bgColor: string;
}> = {
    baja: {
        label: 'Baja',
        color: 'text-gray-600',
        bgColor: 'bg-gray-100',
    },
    media: {
        label: 'Media',
        color: 'text-yellow-800',
        bgColor: 'bg-yellow-100',
    },
    alta: {
        label: 'Alta',
        color: 'text-red-800',
        bgColor: 'bg-red-100',
    },
};

// Lista ordenada de estados para tabs/kanban
export const ESTADOS_ORDENADOS: EstadoEntregable[] = [
    'pendiente',
    'en_progreso',
    'completado',
    'cancelado'
];

interface UseEntregablesViewOptions {
    defaultViewMode?: EntregablesViewMode;
    defaultConfirmOnDrag?: boolean;
}

interface UseEntregablesViewReturn {
    // Estado
    viewMode: Ref<EntregablesViewMode>;
    confirmOnDrag: Ref<boolean>;

    // Computeds
    entregablesAgrupados: ComputedRef<EntregablesAgrupados>;

    // Utilidades
    getEstadoConfig: (estado: EstadoEntregable) => typeof ESTADO_CONFIG[EstadoEntregable];
    getPrioridadConfig: (prioridad: PrioridadEntregable) => typeof PRIORIDAD_CONFIG[PrioridadEntregable];
    getEstadoColor: (estado: string) => string;
    getPrioridadColor: (prioridad: string) => string;
    formatDate: (date: string | null) => string;
    formatDateTime: (date: string | null) => string;
    getDiasRestantes: (fechaFin: string | null) => string | null;
    isVencido: (fechaFin: string | null, estado: EstadoEntregable) => boolean;
}

export function useEntregablesView(
    entregables: Ref<Entregable[]>,
    options: UseEntregablesViewOptions = {}
): UseEntregablesViewReturn {
    const {
        defaultViewMode = 'list',
        defaultConfirmOnDrag = true,
    } = options;

    // Estado persistido en localStorage
    const viewMode = ref<EntregablesViewMode>(
        (localStorage.getItem(STORAGE_KEYS.VIEW_MODE) as EntregablesViewMode) || defaultViewMode
    );

    const confirmOnDrag = ref<boolean>(
        localStorage.getItem(STORAGE_KEYS.CONFIRM_ON_DRAG) !== null
            ? localStorage.getItem(STORAGE_KEYS.CONFIRM_ON_DRAG) === 'true'
            : defaultConfirmOnDrag
    );

    // Persistir cambios automáticamente
    watch(viewMode, (value) => {
        localStorage.setItem(STORAGE_KEYS.VIEW_MODE, value);
    });

    watch(confirmOnDrag, (value) => {
        localStorage.setItem(STORAGE_KEYS.CONFIRM_ON_DRAG, String(value));
    });

    // Entregables agrupados por estado
    const entregablesAgrupados = computed<EntregablesAgrupados>(() => ({
        pendientes: entregables.value.filter(e => e.estado === 'pendiente'),
        en_progreso: entregables.value.filter(e => e.estado === 'en_progreso'),
        completados: entregables.value.filter(e => e.estado === 'completado'),
        cancelados: entregables.value.filter(e => e.estado === 'cancelado'),
    }));

    // Utilidades
    const getEstadoConfig = (estado: EstadoEntregable) => {
        return ESTADO_CONFIG[estado] || ESTADO_CONFIG.pendiente;
    };

    const getPrioridadConfig = (prioridad: PrioridadEntregable) => {
        return PRIORIDAD_CONFIG[prioridad] || PRIORIDAD_CONFIG.baja;
    };

    const getEstadoColor = (estado: string): string => {
        const config = ESTADO_CONFIG[estado as EstadoEntregable];
        return config ? `${config.bgColor} ${config.color}` : 'bg-gray-100 text-gray-800';
    };

    const getPrioridadColor = (prioridad: string): string => {
        const config = PRIORIDAD_CONFIG[prioridad as PrioridadEntregable];
        return config ? `${config.bgColor} ${config.color}` : 'bg-gray-100 text-gray-600';
    };

    const formatDate = (date: string | null): string => {
        if (!date) return 'Sin fecha';
        return format(new Date(date), 'dd MMM yyyy', { locale: es });
    };

    const formatDateTime = (date: string | null): string => {
        if (!date) return '-';
        return format(new Date(date), "dd MMM yyyy 'a las' HH:mm", { locale: es });
    };

    const getDiasRestantes = (fechaFin: string | null): string | null => {
        if (!fechaFin) return null;
        const dias = Math.ceil(
            (new Date(fechaFin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
        );
        if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
        if (dias === 0) return 'Vence hoy';
        if (dias === 1) return 'Vence mañana';
        return `${dias} días`;
    };

    const isVencido = (fechaFin: string | null, estado: EstadoEntregable): boolean => {
        if (!fechaFin) return false;
        if (estado === 'completado' || estado === 'cancelado') return false;
        return new Date(fechaFin) < new Date();
    };

    return {
        viewMode,
        confirmOnDrag,
        entregablesAgrupados,
        getEstadoConfig,
        getPrioridadConfig,
        getEstadoColor,
        getPrioridadColor,
        formatDate,
        formatDateTime,
        getDiasRestantes,
        isVencido,
    };
}
