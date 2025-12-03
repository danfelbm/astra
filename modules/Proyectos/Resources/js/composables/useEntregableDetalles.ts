/**
 * Composable para cargar detalles completos de un entregable.
 * Usado por EntregableDetallesModal para cargar datos por API.
 */
import { ref, Ref } from 'vue';
import axios from 'axios';
import type { Entregable } from '../types/hitos';

// Tipos
interface Usuario {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface UsuarioAsignado {
    user_id: number;
    user: Usuario;
    rol: 'colaborador' | 'revisor' | 'responsable';
    created_at: string;
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

interface Evidencia {
    id: number;
    tipo_evidencia: string;
    descripcion?: string;
    archivo_url?: string;
    estado: string;
    created_at: string;
    usuario?: Usuario;
    obligacion?: {
        id: number;
        titulo: string;
        contrato_id: number;
        contrato?: {
            id: number;
            nombre: string;
        };
    };
}

interface ContratoRelacionado {
    id: number;
    nombre: string;
}

interface ProyectoInfo {
    id: number;
    nombre: string;
}

interface HitoInfo {
    id: number;
    nombre: string;
    estado: string;
    porcentaje_completado: number;
}

interface SelectOption {
    value: string;
    label: string;
    color?: string;
}

interface CategoriaEtiqueta {
    id: number;
    nombre: string;
    slug: string;
    color?: string;
    icono?: string;
    etiquetas?: Array<{
        id: number;
        nombre: string;
        slug: string;
        color?: string;
        descripcion?: string;
    }>;
}

interface EntregableDetallesData {
    entregable: Entregable | null;
    proyecto: ProyectoInfo | null;
    hito: HitoInfo | null;
    usuariosAsignados: UsuarioAsignado[];
    actividades: Actividad[];
    usuariosActividades: Usuario[];
    camposPersonalizados: CampoPersonalizado[];
    valoresCamposPersonalizados: Record<number, any>;
    contratosRelacionados: ContratoRelacionado[];
    // Datos para edición inline
    estados: SelectOption[];
    prioridades: SelectOption[];
    categorias: CategoriaEtiqueta[];
    searchUsersEndpoint: string;
    // Permisos
    canEdit: boolean;
    canDelete: boolean;
    canComplete: boolean;
}

interface UseEntregableDetallesReturn {
    data: Ref<EntregableDetallesData>;
    loading: Ref<boolean>;
    error: Ref<string | null>;
    cargar: () => Promise<void>;
    reset: () => void;
}

const defaultData: EntregableDetallesData = {
    entregable: null,
    proyecto: null,
    hito: null,
    usuariosAsignados: [],
    actividades: [],
    usuariosActividades: [],
    camposPersonalizados: [],
    valoresCamposPersonalizados: {},
    contratosRelacionados: [],
    // Datos para edición inline
    estados: [],
    prioridades: [],
    categorias: [],
    searchUsersEndpoint: '',
    // Permisos
    canEdit: false,
    canDelete: false,
    canComplete: false,
};

/**
 * Composable para cargar y gestionar detalles de un entregable.
 *
 * @param entregableId - Ref con el ID del entregable (puede ser null)
 * @returns Objeto con datos, estado de carga y funciones
 */
export function useEntregableDetalles(entregableId: Ref<number | null>): UseEntregableDetallesReturn {
    const data = ref<EntregableDetallesData>({ ...defaultData });
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Carga los detalles del entregable desde la API.
     */
    const cargar = async (): Promise<void> => {
        if (!entregableId.value) {
            reset();
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const url = `/api/proyectos/entregables/${entregableId.value}/detalles`;
            const response = await axios.get(url);

            data.value = {
                entregable: response.data.entregable || null,
                proyecto: response.data.proyecto || null,
                hito: response.data.hito || null,
                usuariosAsignados: response.data.usuariosAsignados || [],
                actividades: response.data.actividades || [],
                usuariosActividades: response.data.usuariosActividades || [],
                camposPersonalizados: response.data.camposPersonalizados || [],
                valoresCamposPersonalizados: response.data.valoresCamposPersonalizados || {},
                contratosRelacionados: response.data.contratosRelacionados || [],
                // Datos para edición inline
                estados: response.data.estados || [],
                prioridades: response.data.prioridades || [],
                categorias: response.data.categorias || [],
                searchUsersEndpoint: response.data.searchUsersEndpoint || '',
                // Permisos
                canEdit: response.data.canEdit || false,
                canDelete: response.data.canDelete || false,
                canComplete: response.data.canComplete || false,
            };
        } catch (e: any) {
            console.error('Error al cargar detalles del entregable:', e);
            error.value = e.response?.data?.message || 'Error al cargar detalles del entregable';
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

export type {
    EntregableDetallesData,
    UsuarioAsignado,
    Evidencia,
    CampoPersonalizado,
    Actividad,
    Usuario,
    ContratoRelacionado,
    ProyectoInfo,
    HitoInfo,
};
