/**
 * Composable para cargar actividades (audit log) de hitos y entregables.
 * Usado por ActividadSheet para cargar datos por API.
 */
import { ref, Ref, watch } from 'vue';
import axios from 'axios';

// Tipos
interface Usuario {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface ActividadProperties {
    entidad_tipo?: string;
    entidad_nombre?: string;
    entidad_url?: string;
    comentario_id?: number;
    contenido_preview?: string;
    [key: string]: any;
}

interface Actividad {
    id: number;
    description: string;
    causer: Usuario | null;
    created_at: string;
    subject_type?: string;
    subject_id?: number;
    event?: string;
    properties?: ActividadProperties;
}

interface UseActividadesReturn {
    actividades: Ref<Actividad[]>;
    usuarios: Ref<Usuario[]>;
    loading: Ref<boolean>;
    error: Ref<string | null>;
    cargar: () => Promise<void>;
}

/**
 * Composable para cargar y gestionar actividades.
 *
 * @param tipo - Tipo de entidad ('hitos' | 'entregables')
 * @param id - Ref con el ID de la entidad (puede ser null)
 * @returns Objeto con actividades, estado de carga y función para cargar
 */
export function useActividades(
    tipo: 'hitos' | 'entregables',
    id: Ref<number | null>
): UseActividadesReturn {
    const actividades = ref<Actividad[]>([]);
    const usuarios = ref<Usuario[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Carga las actividades desde la API.
     */
    const cargar = async (): Promise<void> => {
        if (!id.value) return;

        loading.value = true;
        error.value = null;

        try {
            const url = `/api/proyectos/${tipo}/${id.value}/actividades`;
            const response = await axios.get(url);

            actividades.value = response.data.actividades || [];
            usuarios.value = response.data.usuarios || [];
        } catch (e: any) {
            console.error('Error al cargar actividades:', e);
            error.value = e.response?.data?.message || 'Error al cargar actividades';
            actividades.value = [];
            usuarios.value = [];
        } finally {
            loading.value = false;
        }
    };

    return {
        actividades,
        usuarios,
        loading,
        error,
        cargar,
    };
}

export type { Actividad, Usuario, ActividadProperties };
