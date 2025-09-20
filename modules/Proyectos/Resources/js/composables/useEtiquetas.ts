import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import type {
    Etiqueta,
    CategoriaEtiqueta,
    CreateEtiquetaRequest,
    UpdateEtiquetaRequest
} from '../types/etiquetas';

/**
 * Composable para manejar la lógica de etiquetas
 */
export function useEtiquetas() {
    // Estado local
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Agregar una etiqueta a un proyecto
     */
    async function addEtiquetaToProyecto(proyectoId: number, etiquetaId: number) {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            router.post(
                `/admin/proyectos/${proyectoId}/etiquetas`,
                { etiqueta_id: etiquetaId },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = Object.values(errors).join(', ');
                        reject(errors);
                    }
                }
            );
        });
    }

    /**
     * Quitar una etiqueta de un proyecto
     */
    async function removeEtiquetaFromProyecto(proyectoId: number, etiquetaId: number) {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            router.delete(
                `/admin/proyectos/${proyectoId}/etiquetas/${etiquetaId}`,
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = 'Error al quitar la etiqueta';
                        reject(errors);
                    }
                }
            );
        });
    }

    /**
     * Sincronizar todas las etiquetas de un proyecto
     */
    async function syncEtiquetasProyecto(proyectoId: number, etiquetaIds: number[]) {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            router.put(
                `/admin/proyectos/${proyectoId}/etiquetas/sync`,
                { etiquetas: etiquetaIds },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = 'Error al sincronizar etiquetas';
                        reject(errors);
                    }
                }
            );
        });
    }

    /**
     * Obtener sugerencias de etiquetas para un proyecto
     */
    async function getSugerenciasEtiquetas(proyectoId: number): Promise<Etiqueta[]> {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/admin/proyectos/${proyectoId}/etiquetas/suggest`);
            if (!response.ok) throw new Error('Error al obtener sugerencias');

            const data = await response.json();
            isLoading.value = false;
            return data.etiquetas || [];
        } catch (err) {
            isLoading.value = false;
            error.value = 'Error al obtener sugerencias de etiquetas';
            console.error(err);
            return [];
        }
    }

    /**
     * Crear una nueva etiqueta
     */
    async function createEtiqueta(data: CreateEtiquetaRequest): Promise<Etiqueta | null> {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await fetch('/admin/etiquetas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errors = await response.json();
                throw new Error(errors.message || 'Error al crear etiqueta');
            }

            const etiqueta = await response.json();
            isLoading.value = false;
            return etiqueta.data;
        } catch (err: any) {
            isLoading.value = false;
            error.value = err.message || 'Error al crear etiqueta';
            console.error(err);
            return null;
        }
    }

    /**
     * Buscar etiquetas
     */
    async function searchEtiquetas(query: string): Promise<Etiqueta[]> {
        if (query.length < 2) return [];

        try {
            const response = await fetch(`/admin/etiquetas/search?q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Error al buscar etiquetas');

            const data = await response.json();
            return data.data || [];
        } catch (err) {
            console.error('Error buscando etiquetas:', err);
            return [];
        }
    }

    /**
     * Formatear etiquetas para mostrar agrupadas por categoría
     */
    function groupEtiquetasByCategoria(etiquetas: Etiqueta[]): Map<string, Etiqueta[]> {
        const grouped = new Map<string, Etiqueta[]>();

        etiquetas.forEach(etiqueta => {
            const categoriaName = etiqueta.categoria?.nombre || 'Sin categoría';

            if (!grouped.has(categoriaName)) {
                grouped.set(categoriaName, []);
            }

            grouped.get(categoriaName)!.push(etiqueta);
        });

        return grouped;
    }

    /**
     * Obtener el color de una etiqueta (propio o de su categoría)
     */
    function getEtiquetaColor(etiqueta: Etiqueta): string {
        return etiqueta.color || etiqueta.categoria?.color || 'gray';
    }

    /**
     * Validar si se puede agregar más etiquetas
     */
    function canAddMoreEtiquetas(currentCount: number, maxCount: number = 10): boolean {
        return currentCount < maxCount;
    }

    /**
     * Reordenar etiquetas
     */
    async function reorderEtiquetas(proyectoId: number, etiquetaIds: number[]) {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            router.post(
                `/admin/proyectos/${proyectoId}/etiquetas/reorder`,
                { etiquetas: etiquetaIds },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = 'Error al reordenar etiquetas';
                        reject(errors);
                    }
                }
            );
        });
    }

    return {
        // Estado
        isLoading,
        error,

        // Métodos para proyectos
        addEtiquetaToProyecto,
        removeEtiquetaFromProyecto,
        syncEtiquetasProyecto,
        getSugerenciasEtiquetas,
        reorderEtiquetas,

        // Métodos para etiquetas
        createEtiqueta,
        searchEtiquetas,

        // Utilidades
        groupEtiquetasByCategoria,
        getEtiquetaColor,
        canAddMoreEtiquetas
    };
}

/**
 * Composable para manejar categorías de etiquetas
 */
export function useCategoriaEtiquetas() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Crear una nueva categoría
     */
    async function createCategoria(data: {
        nombre: string;
        color: string;
        icono?: string;
        descripcion?: string;
    }): Promise<CategoriaEtiqueta | null> {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve) => {
            router.post(
                '/admin/categorias-etiquetas',
                data,
                {
                    preserveState: false,
                    onSuccess: (page: any) => {
                        isLoading.value = false;
                        // La categoría creada debería venir en la respuesta
                        resolve(page.props.categoria || null);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = Object.values(errors).join(', ');
                        resolve(null);
                    }
                }
            );
        });
    }

    /**
     * Actualizar una categoría
     */
    async function updateCategoria(
        id: number,
        data: Partial<CategoriaEtiqueta>
    ): Promise<boolean> {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve) => {
            router.put(
                `/admin/categorias-etiquetas/${id}`,
                data,
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: (errors) => {
                        isLoading.value = false;
                        error.value = Object.values(errors).join(', ');
                        resolve(false);
                    }
                }
            );
        });
    }

    /**
     * Eliminar una categoría
     */
    async function deleteCategoria(id: number): Promise<boolean> {
        if (!confirm('¿Estás seguro de eliminar esta categoría? Las etiquetas se moverán a "Sin categoría".')) {
            return false;
        }

        isLoading.value = true;
        error.value = null;

        return new Promise((resolve) => {
            router.delete(
                `/admin/categorias-etiquetas/${id}`,
                {
                    preserveState: false,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: () => {
                        isLoading.value = false;
                        error.value = 'Error al eliminar la categoría';
                        resolve(false);
                    }
                }
            );
        });
    }

    /**
     * Cambiar el estado activo de una categoría
     */
    async function toggleCategoriaActive(id: number): Promise<boolean> {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve) => {
            router.patch(
                `/admin/categorias-etiquetas/${id}/toggle-active`,
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: () => {
                        isLoading.value = false;
                        error.value = 'Error al cambiar el estado de la categoría';
                        resolve(false);
                    }
                }
            );
        });
    }

    /**
     * Reordenar categorías
     */
    async function reorderCategorias(categoriaIds: number[]): Promise<boolean> {
        isLoading.value = true;
        error.value = null;

        return new Promise((resolve) => {
            router.post(
                '/admin/categorias-etiquetas/reorder',
                { categorias: categoriaIds },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        isLoading.value = false;
                        resolve(true);
                    },
                    onError: () => {
                        isLoading.value = false;
                        error.value = 'Error al reordenar categorías';
                        resolve(false);
                    }
                }
            );
        });
    }

    return {
        isLoading,
        error,
        createCategoria,
        updateCategoria,
        deleteCategoria,
        toggleCategoriaActive,
        reorderCategorias
    };
}