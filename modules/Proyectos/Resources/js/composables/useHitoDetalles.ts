/**
 * Composable para cargar detalles completos de un hito.
 * Usado por HitoDetallesModal para cargar datos por API.
 */
import { ref, Ref, watch } from 'vue';
import axios from 'axios';
import type { Hito, Entregable } from '../types/hitos';

// Tipos
interface Usuario {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface Actividad {
    id: number;
    description: string;
    causer: Usuario | null;
    created_at: string;
    subject_type?: string;
    subject_id?: number;
    event?: string;
    properties?: Record<string, any>;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    es_requerido: boolean;
    descripcion?: string;
    opciones?: any[];
}

interface Estadisticas {
    total_entregables: number;
    entregables_completados: number;
    entregables_pendientes: number;
    entregables_en_progreso: number;
    entregables_cancelados: number;
    porcentaje_completado: number;
    dias_restantes: number | null;
    esta_vencido: boolean;
}

interface HitoDetallesData {
    hito: Hito | null;
    estadisticas: Estadisticas | null;
    actividades: Actividad[];
    usuariosActividades: Usuario[];
    usuariosEntregables: Usuario[];
    camposPersonalizados: CampoPersonalizado[];
    valoresCamposPersonalizados: Record<number, any>;
    canEdit: boolean;
    canDelete: boolean;
    canManageEntregables: boolean;
    canComplete: boolean;
}

interface UseHitoDetallesReturn {
    data: Ref<HitoDetallesData>;
    loading: Ref<boolean>;
    error: Ref<string | null>;
    cargar: () => Promise<void>;
    reset: () => void;
}

const defaultData: HitoDetallesData = {
    hito: null,
    estadisticas: null,
    actividades: [],
    usuariosActividades: [],
    usuariosEntregables: [],
    camposPersonalizados: [],
    valoresCamposPersonalizados: {},
    canEdit: false,
    canDelete: false,
    canManageEntregables: false,
    canComplete: false,
};

/**
 * Composable para cargar y gestionar detalles de un hito.
 *
 * @param hitoId - Ref con el ID del hito (puede ser null)
 * @returns Objeto con datos, estado de carga y funciones
 */
export function useHitoDetalles(hitoId: Ref<number | null>): UseHitoDetallesReturn {
    const data = ref<HitoDetallesData>({ ...defaultData });
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Carga los detalles del hito desde la API.
     */
    const cargar = async (): Promise<void> => {
        if (!hitoId.value) {
            reset();
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const url = `/api/proyectos/hitos/${hitoId.value}/detalles`;
            const response = await axios.get(url);

            data.value = {
                hito: response.data.hito || null,
                estadisticas: response.data.estadisticas || null,
                actividades: response.data.actividades || [],
                usuariosActividades: response.data.usuariosActividades || [],
                usuariosEntregables: response.data.usuariosEntregables || [],
                camposPersonalizados: response.data.camposPersonalizados || [],
                valoresCamposPersonalizados: response.data.valoresCamposPersonalizados || {},
                canEdit: response.data.canEdit || false,
                canDelete: response.data.canDelete || false,
                canManageEntregables: response.data.canManageEntregables || false,
                canComplete: response.data.canComplete || false,
            };
        } catch (e: any) {
            console.error('Error al cargar detalles del hito:', e);
            error.value = e.response?.data?.message || 'Error al cargar detalles del hito';
            reset();
        } finally {
            loading.value = false;
        }
    };

    /**
     * Resetea los datos a valores por defecto.
     */
    const reset = (): void => {
        data.value = { ...defaultData };
    };

    return {
        data,
        loading,
        error,
        cargar,
        reset,
    };
}

export type { HitoDetallesData, Estadisticas, CampoPersonalizado, Actividad, Usuario };
