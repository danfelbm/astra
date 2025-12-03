/**
 * Composable para gestionar edición inline de campos en Hitos y Entregables
 * Proporciona funciones para actualizar campos individuales via API
 */
import { ref, Ref } from 'vue';
import axios from 'axios';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';

// Tipos
interface UpdateFieldResponse {
    success: boolean;
    message?: string;
    data?: any;
}

interface UseInlineEditOptions {
    // Callback después de actualizar exitosamente
    onSuccess?: (field: string, value: any, response: any) => void;
    // Callback en caso de error
    onError?: (field: string, error: any) => void;
}

/**
 * Composable para edición inline de campos de un Hito
 */
export function useHitoInlineEdit(hitoId: Ref<number | null>, options: UseInlineEditOptions = {}) {
    const toast = useToast();
    const loadingField = ref<string | null>(null);
    const error = ref<string | null>(null);

    /**
     * Actualizar un campo del hito
     */
    const updateField = async (
        field: string,
        value: any,
        label?: string
    ): Promise<boolean> => {
        if (!hitoId.value) {
            error.value = 'No hay hito seleccionado';
            return false;
        }

        loadingField.value = field;
        error.value = null;

        try {
            // Si el valor es un archivo, usar FormData con POST (Laravel method spoofing)
            if (value instanceof File) {
                const formData = new FormData();
                formData.append('field', field);
                formData.append('value', value);
                formData.append('_method', 'PATCH');

                // Usar POST para FormData (axios.patch no maneja bien multipart)
                const response = await axios.post<UpdateFieldResponse>(
                    `/api/proyectos/hitos/${hitoId.value}/campo`,
                    formData
                );

                if (response.data.success) {
                    toast.success(label ? `${label} actualizado` : 'Campo actualizado');
                    options.onSuccess?.(field, value, response.data);
                    return true;
                } else {
                    throw new Error(response.data.message || 'Error al actualizar');
                }
            }

            // Para otros valores, usar PATCH normal
            const response = await axios.patch<UpdateFieldResponse>(
                `/api/proyectos/hitos/${hitoId.value}/campo`,
                { field, value }
            );

            if (response.data.success) {
                toast.success(label ? `${label} actualizado` : 'Campo actualizado');
                options.onSuccess?.(field, value, response.data);
                return true;
            } else {
                throw new Error(response.data.message || 'Error al actualizar');
            }
        } catch (e: any) {
            const message = e.response?.data?.message || e.message || 'Error al actualizar el campo';
            error.value = message;
            toast.error(message);
            options.onError?.(field, e);
            return false;
        } finally {
            loadingField.value = null;
        }
    };

    /**
     * Actualizar el responsable del hito
     */
    const updateResponsable = async (userId: number | null): Promise<boolean> => {
        return updateField('responsable_id', userId, 'Responsable');
    };

    /**
     * Actualizar el hito padre
     */
    const updatePadre = async (padreId: number | null): Promise<boolean> => {
        return updateField('parent_id', padreId, 'Hito padre');
    };

    /**
     * Actualizar las etiquetas del hito
     */
    const updateEtiquetas = async (etiquetaIds: number[]): Promise<boolean> => {
        return updateField('etiquetas', etiquetaIds, 'Etiquetas');
    };

    /**
     * Actualizar un campo personalizado
     */
    const updateCampoPersonalizado = async (campoId: number, value: any): Promise<boolean> => {
        return updateField(`campo_personalizado_${campoId}`, value, 'Campo personalizado');
    };

    return {
        loadingField,
        error,
        updateField,
        updateResponsable,
        updatePadre,
        updateEtiquetas,
        updateCampoPersonalizado,
    };
}

/**
 * Composable para edición inline de campos de un Entregable
 */
export function useEntregableInlineEdit(entregableId: Ref<number | null>, options: UseInlineEditOptions = {}) {
    const toast = useToast();
    const loadingField = ref<string | null>(null);
    const error = ref<string | null>(null);

    /**
     * Actualizar un campo del entregable
     */
    const updateField = async (
        field: string,
        value: any,
        label?: string
    ): Promise<boolean> => {
        if (!entregableId.value) {
            error.value = 'No hay entregable seleccionado';
            return false;
        }

        loadingField.value = field;
        error.value = null;

        try {
            // Si el valor es un archivo, usar FormData con POST (Laravel method spoofing)
            if (value instanceof File) {
                const formData = new FormData();
                formData.append('field', field);
                formData.append('value', value);
                formData.append('_method', 'PATCH');

                // Usar POST para FormData (axios.patch no maneja bien multipart)
                const response = await axios.post<UpdateFieldResponse>(
                    `/api/proyectos/entregables/${entregableId.value}/campo`,
                    formData
                );

                if (response.data.success) {
                    toast.success(label ? `${label} actualizado` : 'Campo actualizado');
                    options.onSuccess?.(field, value, response.data);
                    return true;
                } else {
                    throw new Error(response.data.message || 'Error al actualizar');
                }
            }

            // Para otros valores, usar PATCH normal
            const response = await axios.patch<UpdateFieldResponse>(
                `/api/proyectos/entregables/${entregableId.value}/campo`,
                { field, value }
            );

            if (response.data.success) {
                toast.success(label ? `${label} actualizado` : 'Campo actualizado');
                options.onSuccess?.(field, value, response.data);
                return true;
            } else {
                throw new Error(response.data.message || 'Error al actualizar');
            }
        } catch (e: any) {
            const message = e.response?.data?.message || e.message || 'Error al actualizar el campo';
            error.value = message;
            toast.error(message);
            options.onError?.(field, e);
            return false;
        } finally {
            loadingField.value = null;
        }
    };

    /**
     * Actualizar el responsable del entregable
     */
    const updateResponsable = async (userId: number | null): Promise<boolean> => {
        return updateField('responsable_id', userId, 'Responsable');
    };

    /**
     * Actualizar los colaboradores del entregable
     */
    const updateColaboradores = async (
        usuarios: Array<{ user_id: number; rol: string }>
    ): Promise<boolean> => {
        return updateField('usuarios', usuarios, 'Colaboradores');
    };

    /**
     * Actualizar las etiquetas del entregable
     */
    const updateEtiquetas = async (etiquetaIds: number[]): Promise<boolean> => {
        return updateField('etiquetas', etiquetaIds, 'Etiquetas');
    };

    /**
     * Actualizar un campo personalizado
     */
    const updateCampoPersonalizado = async (campoId: number, value: any): Promise<boolean> => {
        return updateField(`campo_personalizado_${campoId}`, value, 'Campo personalizado');
    };

    /**
     * Actualizar el estado (con observaciones opcionales)
     */
    const updateEstado = async (estado: string, observaciones?: string): Promise<boolean> => {
        if (!entregableId.value) {
            error.value = 'No hay entregable seleccionado';
            return false;
        }

        loadingField.value = 'estado';
        error.value = null;

        try {
            const response = await axios.patch<UpdateFieldResponse>(
                `/api/proyectos/entregables/${entregableId.value}/campo`,
                {
                    field: 'estado',
                    value: estado,
                    observaciones
                }
            );

            if (response.data.success) {
                toast.success('Estado actualizado');
                options.onSuccess?.('estado', estado, response.data);
                return true;
            } else {
                throw new Error(response.data.message || 'Error al actualizar');
            }
        } catch (e: any) {
            const message = e.response?.data?.message || e.message || 'Error al actualizar el estado';
            error.value = message;
            toast.error(message);
            options.onError?.('estado', e);
            return false;
        } finally {
            loadingField.value = null;
        }
    };

    return {
        loadingField,
        error,
        updateField,
        updateResponsable,
        updateColaboradores,
        updateEtiquetas,
        updateCampoPersonalizado,
        updateEstado,
    };
}

export type { UpdateFieldResponse, UseInlineEditOptions };
