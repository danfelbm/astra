import { ref, computed } from 'vue';
import type {
    Comentario,
    ComentarioCreateData,
    ComentarioUpdateData,
    ApiResponse,
    PaginatedResponse,
    PaginationLink,
    ReaccionResumen,
    EmojiKey,
} from '../types/comentarios';

// Tipos de ordenamiento disponibles
export type SortOption = 'recientes' | 'antiguos' | 'populares';

// Opciones para el composable
export interface UseComentariosOptions {
    paginaInicial?: number;
    urlParam?: string; // Nombre del parámetro en la URL (default: 'pagina')
    sincronizarUrl?: boolean; // Si debe actualizar la URL al cambiar de página
}

/**
 * Composable para gestionar comentarios en cualquier entidad.
 *
 * @param commentableType - Tipo de modelo ('hitos', 'entregables', etc.)
 * @param commentableId - ID del modelo
 * @param options - Opciones de configuración
 */
export function useComentarios(
    commentableType: string,
    commentableId: number,
    options: UseComentariosOptions = {}
) {
    const {
        paginaInicial = 1,
        urlParam = 'pagina',
        sincronizarUrl = true,
    } = options;

    // Estado
    const comentarios = ref<Comentario[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const currentPage = ref(paginaInicial);
    const lastPage = ref(1);
    const total = ref(0);
    const from = ref(0);
    const to = ref(0);
    const links = ref<PaginationLink[]>([]);
    const sortBy = ref<SortOption>('recientes');

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
     * Actualiza el parámetro de página en la URL sin recargar.
     */
    const actualizarUrl = (page: number): void => {
        if (!sincronizarUrl) return;

        const url = new URL(window.location.href);
        if (page > 1) {
            url.searchParams.set(urlParam, String(page));
        } else {
            url.searchParams.delete(urlParam);
        }
        window.history.replaceState({}, '', url.toString());
    };

    /**
     * Carga comentarios desde la API.
     * @param page - Página a cargar (por defecto usa currentPage)
     * @param actualizarUrlParam - Si debe actualizar el parámetro en la URL
     */
    const cargar = async (page?: number, actualizarUrlParam: boolean = true): Promise<void> => {
        // Si no se especifica página, usar la página actual (permite cargar página inicial de URL)
        const targetPage = page ?? currentPage.value;
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams({
                page: String(targetPage),
                sort: sortBy.value,
            });

            const response = await fetch(`${baseUrl}?${params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result: ApiResponse<PaginatedResponse<Comentario>> = await response.json();

            if (result.success && result.data) {
                // Siempre reemplazar datos (paginación real, no "cargar más")
                comentarios.value = result.data.data;
                currentPage.value = result.data.current_page;
                lastPage.value = result.data.last_page;
                total.value = result.data.total;
                from.value = result.data.from || 0;
                to.value = result.data.to || 0;
                links.value = result.data.links || [];

                // Actualizar URL si corresponde
                if (actualizarUrlParam) {
                    actualizarUrl(targetPage);
                }
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
     * Cambia el ordenamiento y recarga los comentarios.
     */
    const cambiarOrden = async (nuevoOrden: SortOption): Promise<void> => {
        if (nuevoOrden !== sortBy.value) {
            sortBy.value = nuevoOrden;
            await cargar(1);
        }
    };

    /**
     * Navega a una página específica.
     */
    const irAPagina = async (page: number): Promise<void> => {
        if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
            await cargar(page);
        }
    };

    /**
     * Página siguiente.
     */
    const paginaSiguiente = async (): Promise<void> => {
        if (currentPage.value < lastPage.value) {
            await cargar(currentPage.value + 1);
        }
    };

    /**
     * Página anterior.
     */
    const paginaAnterior = async (): Promise<void> => {
        if (currentPage.value > 1) {
            await cargar(currentPage.value - 1);
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

    /**
     * Carga respuestas adicionales de un comentario (para carga bajo demanda).
     * Útil cuando hay respuestas profundas que no se cargaron inicialmente.
     */
    const cargarRespuestasAdicionales = async (
        comentarioId: number,
        offset: number = 0,
        limit: number = 10
    ): Promise<ApiResponse<Comentario[]>> => {
        try {
            const params = new URLSearchParams({
                offset: String(offset),
                limit: String(limit),
            });

            const response = await fetch(`/api/comentarios/${comentarioId}/respuestas?${params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (result.success && result.data) {
                // Agregar las respuestas al comentario padre
                agregarRespuestasAComentario(comentarioId, result.data);
            }

            return result;
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Error al cargar respuestas';
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
     * Obtiene las respuestas de un comentario (soporta ambos formatos).
     */
    const getRespuestas = (comentario: Comentario): Comentario[] => {
        return comentario.respuestas || comentario.respuestas_limitadas || [];
    };

    /**
     * Agrega una respuesta a un comentario en la lista.
     */
    const agregarRespuestaAComentario = (parentId: number, respuesta: Comentario): void => {
        const agregarRecursivo = (lista: Comentario[]): boolean => {
            for (const comentario of lista) {
                if (comentario.id === parentId) {
                    // Usar respuestas o respuestas_limitadas según disponibilidad
                    if (!comentario.respuestas && !comentario.respuestas_limitadas) {
                        comentario.respuestas = [];
                    }
                    const target = comentario.respuestas || comentario.respuestas_limitadas;
                    target?.push(respuesta);
                    return true;
                }
                const respuestas = getRespuestas(comentario);
                if (respuestas.length > 0 && agregarRecursivo(respuestas)) {
                    return true;
                }
            }
            return false;
        };

        agregarRecursivo(comentarios.value);
    };

    /**
     * Agrega múltiples respuestas a un comentario (para carga bajo demanda).
     */
    const agregarRespuestasAComentario = (parentId: number, nuevasRespuestas: Comentario[]): void => {
        const agregarRecursivo = (lista: Comentario[]): boolean => {
            for (const comentario of lista) {
                if (comentario.id === parentId) {
                    if (!comentario.respuestas && !comentario.respuestas_limitadas) {
                        comentario.respuestas = [];
                    }
                    const target = comentario.respuestas || comentario.respuestas_limitadas;
                    if (target) {
                        target.push(...nuevasRespuestas);
                    }
                    // Actualizar contador
                    if (comentario.total_respuestas_anidadas !== undefined) {
                        comentario.total_respuestas_anidadas = Math.max(
                            0,
                            comentario.total_respuestas_anidadas - nuevasRespuestas.length
                        );
                    }
                    return true;
                }
                const respuestas = getRespuestas(comentario);
                if (respuestas.length > 0 && agregarRecursivo(respuestas)) {
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
                        respuestas_limitadas: lista[i].respuestas_limitadas,
                    };
                    return true;
                }
                const respuestas = getRespuestas(lista[i]);
                if (respuestas.length > 0 && actualizarRecursivo(respuestas)) {
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
                .map(c => {
                    const respuestas = getRespuestas(c);
                    return {
                        ...c,
                        respuestas: c.respuestas ? eliminarRecursivo(c.respuestas) : undefined,
                        respuestas_limitadas: c.respuestas_limitadas ? eliminarRecursivo(c.respuestas_limitadas) : undefined,
                    };
                });
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
                const respuestas = getRespuestas(comentario);
                if (respuestas.length > 0 && actualizarRecursivo(respuestas)) {
                    return true;
                }
            }
            return false;
        };

        actualizarRecursivo(comentarios.value);
    };

    // Computados
    const tieneComentarios = computed(() => comentarios.value.length > 0);
    const tienePaginacion = computed(() => lastPage.value > 1);
    const puedePaginaAnterior = computed(() => currentPage.value > 1);
    const puedePaginaSiguiente = computed(() => currentPage.value < lastPage.value);

    return {
        // Estado
        comentarios,
        loading,
        error,
        currentPage,
        lastPage,
        total,
        from,
        to,
        links,
        emojis,
        sortBy,

        // Computados
        tieneComentarios,
        tienePaginacion,
        puedePaginaAnterior,
        puedePaginaSiguiente,

        // Métodos
        cargar,
        irAPagina,
        paginaSiguiente,
        paginaAnterior,
        crear,
        editar,
        eliminar,
        toggleReaccion,
        cambiarOrden,
        cargarRespuestasAdicionales,
    };
}
