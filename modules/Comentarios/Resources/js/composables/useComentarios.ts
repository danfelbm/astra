import { ref, computed } from 'vue';
import type {
    Comentario,
    ComentarioCreateData,
    ComentarioUpdateData,
    ApiResponse,
    PaginatedResponse,
    ReaccionResumen,
    EmojiKey,
} from '../types/comentarios';

/**
 * Composable para gestionar comentarios en cualquier entidad.
 *
 * @param commentableType - Tipo de modelo ('hitos', 'entregables', etc.)
 * @param commentableId - ID del modelo
 */
export function useComentarios(commentableType: string, commentableId: number) {
    // Estado
    const comentarios = ref<Comentario[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const total = ref(0);

    // Emojis disponibles
    const emojis: Record<EmojiKey, string> = {
        thumbs_up: '👍',
        thumbs_down: '👎',
        heart: '❤️',
        laugh: '😄',
        clap: '👏',
        fire: '🔥',
        check: '✅',
        eyes: '👀',
    };

    // URL base de la API
    const baseUrl = `/api/comentarios/${commentableType}/${commentableId}`;

    /**
     * Carga comentarios desde la API.
     */
    const cargar = async (page: number = 1): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`${baseUrl}?page=${page}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result: ApiResponse<PaginatedResponse<Comentario>> = await response.json();

            if (result.success && result.data) {
                if (page === 1) {
                    comentarios.value = result.data.data;
                } else {
                    // Agregar al final para scroll infinito
                    comentarios.value = [...comentarios.value, ...result.data.data];
                }
                currentPage.value = result.data.current_page;
                lastPage.value = result.data.last_page;
                total.value = result.data.total;
            } else {
                throw new Error(result.message || 'Error al cargar comentarios');
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Error desconocido';
            console.error('Error cargando comentarios:', e);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Carga más comentarios (scroll infinito).
     */
    const cargarMas = async (): Promise<void> => {
        if (currentPage.value < lastPage.value) {
            await cargar(currentPage.value + 1);
        }
    };

    /**
     * Crea un nuevo comentario.
     */
    const crear = async (data: ComentarioCreateData): Promise<ApiResponse<Comentario>> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            const result: ApiResponse<Comentario> = await response.json();

            if (result.success && result.comentario) {
                if (data.parent_id) {
                    // Es una respuesta, agregarla al comentario padre
                    agregarRespuestaAComentario(data.parent_id, result.comentario);
                } else {
                    // Comentario raíz, agregar al inicio
                    comentarios.value = [result.comentario, ...comentarios.value];
                    total.value++;
                }
            }

            return result;
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Error al crear comentario';
            error.value = message;
            return { success: false, message };
        } finally {
            loading.value = false;
        }
    };

    /**
     * Actualiza un comentario existente.
     */
    const editar = async (comentarioId: number, data: ComentarioUpdateData): Promise<ApiResponse<Comentario>> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/comentarios/${comentarioId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            const result: ApiResponse<Comentario> = await response.json();

            if (result.success && result.comentario) {
                actualizarComentarioEnLista(result.comentario);
            }

            return result;
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Error al actualizar comentario';
            error.value = message;
            return { success: false, message };
        } finally {
            loading.value = false;
        }
    };

    /**
     * Elimina un comentario.
     */
    const eliminar = async (comentarioId: number): Promise<ApiResponse<void>> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/comentarios/${comentarioId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                credentials: 'same-origin',
            });

            const result: ApiResponse<void> = await response.json();

            if (result.success) {
                eliminarComentarioDeLista(comentarioId);
                total.value--;
            }

            return result;
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Error al eliminar comentario';
            error.value = message;
            return { success: false, message };
        } finally {
            loading.value = false;
        }
    };

    /**
     * Toggle de reacción (agregar/quitar emoji).
     */
    const toggleReaccion = async (comentarioId: number, emoji: EmojiKey): Promise<ApiResponse<ReaccionResumen[]>> => {
        try {
            const response = await fetch(`/api/comentarios/${comentarioId}/reaccion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ emoji }),
            });

            const result = await response.json();

            if (result.success && result.reacciones) {
                actualizarReaccionesDeComentario(comentarioId, result.reacciones);
            }

            return result;
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Error al agregar reacción';
            return { success: false, message };
        }
    };

    // =========================================================================
    // Helpers internos
    // =========================================================================

    /**
     * Obtiene el token CSRF del meta tag.
     */
    const getCSRFToken = (): string => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return token || '';
    };

    /**
     * Agrega una respuesta a un comentario en la lista.
     */
    const agregarRespuestaAComentario = (parentId: number, respuesta: Comentario): void => {
        const agregarRecursivo = (lista: Comentario[]): boolean => {
            for (const comentario of lista) {
                if (comentario.id === parentId) {
                    if (!comentario.respuestas) {
                        comentario.respuestas = [];
                    }
                    comentario.respuestas.push(respuesta);
                    return true;
                }
                if (comentario.respuestas && agregarRecursivo(comentario.respuestas)) {
                    return true;
                }
            }
            return false;
        };

        agregarRecursivo(comentarios.value);
    };

    /**
     * Actualiza un comentario en la lista (recursivo).
     */
    const actualizarComentarioEnLista = (comentarioActualizado: Comentario): void => {
        const actualizarRecursivo = (lista: Comentario[]): boolean => {
            for (let i = 0; i < lista.length; i++) {
                if (lista[i].id === comentarioActualizado.id) {
                    // Preservar respuestas existentes
                    lista[i] = {
                        ...comentarioActualizado,
                        respuestas: lista[i].respuestas,
                    };
                    return true;
                }
                if (lista[i].respuestas && actualizarRecursivo(lista[i].respuestas)) {
                    return true;
                }
            }
            return false;
        };

        actualizarRecursivo(comentarios.value);
    };

    /**
     * Elimina un comentario de la lista (recursivo).
     */
    const eliminarComentarioDeLista = (comentarioId: number): void => {
        const eliminarRecursivo = (lista: Comentario[]): Comentario[] => {
            return lista
                .filter(c => c.id !== comentarioId)
                .map(c => ({
                    ...c,
                    respuestas: c.respuestas ? eliminarRecursivo(c.respuestas) : undefined,
                }));
        };

        comentarios.value = eliminarRecursivo(comentarios.value);
    };

    /**
     * Actualiza las reacciones de un comentario (recursivo).
     */
    const actualizarReaccionesDeComentario = (comentarioId: number, reacciones: ReaccionResumen[]): void => {
        const actualizarRecursivo = (lista: Comentario[]): boolean => {
            for (const comentario of lista) {
                if (comentario.id === comentarioId) {
                    comentario.reacciones_resumen = reacciones;
                    return true;
                }
                if (comentario.respuestas && actualizarRecursivo(comentario.respuestas)) {
                    return true;
                }
            }
            return false;
        };

        actualizarRecursivo(comentarios.value);
    };

    // Computados
    const tieneComentarios = computed(() => comentarios.value.length > 0);
    const puedeCargarMas = computed(() => currentPage.value < lastPage.value);

    return {
        // Estado
        comentarios,
        loading,
        error,
        currentPage,
        lastPage,
        total,
        emojis,

        // Computados
        tieneComentarios,
        puedeCargarMas,

        // Métodos
        cargar,
        cargarMas,
        crear,
        editar,
        eliminar,
        toggleReaccion,
    };
}
