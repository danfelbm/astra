// Composable para la gestión de Obligaciones de Contratos
import { ref, computed, Ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type {
    ObligacionContrato,
    ObligacionFormData,
    ObligacionFilters,
    ObligacionEstadisticas,
    EstadoObligacion,
    PrioridadObligacion,
    ObligacionResponse,
    DragData
} from '@/types/obligaciones';

export function useObligaciones(contratoId?: number) {
    // Estado reactivo
    const obligaciones = ref<ObligacionContrato[]>([]);
    const obligacionActual = ref<ObligacionContrato | null>(null);
    const loading = ref(false);
    const errors = ref<Record<string, string[]>>({});
    const expandedNodes = ref<number[]>([]);
    const selectedNode = ref<number | null>(null);
    const draggedItem = ref<DragData | null>(null);
    const estadisticas = ref<ObligacionEstadisticas | null>(null);

    // Filtros
    const filters = ref<ObligacionFilters>({
        search: '',
        estado: undefined,
        prioridad: undefined,
        responsable_id: undefined,
        vencidas: false,
        proximas_vencer: false,
        ver_todas: false,
        sort_field: 'orden',
        sort_direction: 'asc'
    });

    // Form para crear/editar
    const form = useForm<ObligacionFormData>({
        contrato_id: contratoId || 0,
        parent_id: null,
        titulo: '',
        descripcion: '',
        fecha_vencimiento: '',
        estado: 'pendiente',
        prioridad: 'media',
        responsable_id: undefined,
        orden: 1,
        archivos: [],
        archivos_eliminar: [],
        notas_cumplimiento: ''
    });

    // Computed
    const obligacionesArbol = computed(() => {
        return construirArbol(obligaciones.value);
    });

    const obligacionesFiltradas = computed(() => {
        let resultado = [...obligaciones.value];

        // Aplicar filtros
        if (filters.value.search) {
            const searchLower = filters.value.search.toLowerCase();
            resultado = resultado.filter(o =>
                o.titulo.toLowerCase().includes(searchLower) ||
                o.descripcion?.toLowerCase().includes(searchLower)
            );
        }

        if (filters.value.estado) {
            resultado = resultado.filter(o => o.estado === filters.value.estado);
        }

        if (filters.value.prioridad) {
            resultado = resultado.filter(o => o.prioridad === filters.value.prioridad);
        }

        if (filters.value.responsable_id) {
            resultado = resultado.filter(o => o.responsable_id === filters.value.responsable_id);
        }

        if (filters.value.vencidas) {
            resultado = resultado.filter(o => o.esta_vencida);
        }

        if (filters.value.proximas_vencer) {
            resultado = resultado.filter(o => o.esta_proxima_vencer);
        }

        // Ordenamiento
        if (filters.value.sort_field) {
            resultado.sort((a, b) => {
                const aVal = a[filters.value.sort_field as keyof ObligacionContrato];
                const bVal = b[filters.value.sort_field as keyof ObligacionContrato];
                const comparison = aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                return filters.value.sort_direction === 'asc' ? comparison : -comparison;
            });
        }

        return resultado;
    });

    // Métodos
    const cargarObligaciones = async (contratoId?: number) => {
        loading.value = true;
        try {
            const params = contratoId ? { contrato_id: contratoId } : {};
            await router.get(
                route('admin.obligaciones.index', params),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: (page: any) => {
                        obligaciones.value = page.props.obligaciones?.data || [];
                        estadisticas.value = page.props.estadisticas || null;
                    },
                    onFinish: () => {
                        loading.value = false;
                    }
                }
            );
        } catch (error) {
            console.error('Error cargando obligaciones:', error);
            toast.error('Error al cargar las obligaciones');
            loading.value = false;
        }
    };

    const crearObligacion = async () => {
        form.post(route('admin.obligaciones.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Obligación creada exitosamente');
                form.reset();
                cargarObligaciones(form.contrato_id);
            },
            onError: (errors) => {
                console.error('Errores:', errors);
                toast.error('Error al crear la obligación');
            }
        });
    };

    const actualizarObligacion = async (id: number) => {
        form.put(route('admin.obligaciones.update', id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Obligación actualizada exitosamente');
                cargarObligaciones(form.contrato_id);
            },
            onError: (errors) => {
                console.error('Errores:', errors);
                toast.error('Error al actualizar la obligación');
            }
        });
    };

    const eliminarObligacion = async (id: number, confirmar: boolean = true) => {
        if (confirmar && !confirm('¿Estás seguro de eliminar esta obligación y todos sus hijos?')) {
            return;
        }

        loading.value = true;
        router.delete(route('admin.obligaciones.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Obligación eliminada exitosamente');
                cargarObligaciones(contratoId);
            },
            onError: () => {
                toast.error('Error al eliminar la obligación');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    const completarObligacion = async (id: number, notas?: string) => {
        loading.value = true;
        router.post(
            route('admin.obligaciones.completar', id),
            { notas_cumplimiento: notas },
            {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const response = page.props.flash?.obligacion_response as ObligacionResponse;
                    if (response?.requiere_confirmacion && response.total_hijos) {
                        if (confirm(`Esta obligación tiene ${response.total_hijos} obligaciones hijas. ¿Deseas marcarlas todas como cumplidas?`)) {
                            completarConHijos(id, notas);
                        }
                    } else {
                        toast.success('Obligación marcada como cumplida');
                        cargarObligaciones(contratoId);
                    }
                },
                onError: () => {
                    toast.error('Error al completar la obligación');
                },
                onFinish: () => {
                    loading.value = false;
                }
            }
        );
    };

    const completarConHijos = async (id: number, notas?: string) => {
        loading.value = true;
        router.post(
            route('admin.obligaciones.completar', id),
            { notas_cumplimiento: notas, incluir_hijos: true },
            {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const response = page.props.flash?.obligacion_response as ObligacionResponse;
                    toast.success(`${response?.actualizadas || 1} obligaciones marcadas como cumplidas`);
                    cargarObligaciones(contratoId);
                },
                onError: () => {
                    toast.error('Error al completar las obligaciones');
                },
                onFinish: () => {
                    loading.value = false;
                }
            }
        );
    };

    const duplicarObligacion = async (id: number) => {
        loading.value = true;
        router.post(
            route('admin.obligaciones.duplicar', id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Obligación duplicada exitosamente');
                    cargarObligaciones(contratoId);
                },
                onError: () => {
                    toast.error('Error al duplicar la obligación');
                },
                onFinish: () => {
                    loading.value = false;
                }
            }
        );
    };

    const moverObligacion = async (obligacionId: number, nuevoParentId: number | null, nuevoOrden: number) => {
        loading.value = true;
        router.post(
            route('admin.obligaciones.mover', obligacionId),
            {
                parent_id: nuevoParentId,
                orden: nuevoOrden
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Obligación movida exitosamente');
                    cargarObligaciones(contratoId);
                },
                onError: () => {
                    toast.error('Error al mover la obligación');
                },
                onFinish: () => {
                    loading.value = false;
                }
            }
        );
    };

    const reordenarObligaciones = async (items: number[], parentId: number | null) => {
        loading.value = true;
        router.post(
            route('admin.obligaciones.reordenar'),
            {
                items: items,
                parent_id: parentId
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    cargarObligaciones(contratoId);
                },
                onError: () => {
                    toast.error('Error al reordenar las obligaciones');
                },
                onFinish: () => {
                    loading.value = false;
                }
            }
        );
    };

    // Gestión del árbol
    const toggleNode = (nodeId: number) => {
        const index = expandedNodes.value.indexOf(nodeId);
        if (index > -1) {
            expandedNodes.value.splice(index, 1);
        } else {
            expandedNodes.value.push(nodeId);
        }
    };

    const expandAll = () => {
        expandedNodes.value = obligaciones.value
            .filter(o => o.tiene_hijos)
            .map(o => o.id);
    };

    const collapseAll = () => {
        expandedNodes.value = [];
    };

    const selectNode = (nodeId: number | null) => {
        selectedNode.value = nodeId;
        if (nodeId) {
            const obligacion = obligaciones.value.find(o => o.id === nodeId);
            if (obligacion) {
                obligacionActual.value = obligacion;
            }
        }
    };

    // Drag & Drop
    const handleDragStart = (item: ObligacionContrato, event: DragEvent) => {
        draggedItem.value = {
            obligacionId: item.id,
            parentId: item.parent_id,
            orden: item.orden,
            nivel: item.nivel
        };
        event.dataTransfer!.effectAllowed = 'move';
        event.dataTransfer!.setData('text/plain', JSON.stringify(draggedItem.value));
    };

    const handleDragOver = (event: DragEvent) => {
        event.preventDefault();
        event.dataTransfer!.dropEffect = 'move';
    };

    const handleDrop = (targetItem: ObligacionContrato | null, event: DragEvent) => {
        event.preventDefault();
        event.stopPropagation();

        const data = JSON.parse(event.dataTransfer!.getData('text/plain')) as DragData;

        if (!data || data.obligacionId === targetItem?.id) {
            return;
        }

        // Validar que no se está moviendo a un hijo propio
        if (targetItem && esDescendiente(data.obligacionId, targetItem.id)) {
            toast.error('No se puede mover una obligación dentro de sus propios hijos');
            return;
        }

        const nuevoParentId = targetItem?.id || null;
        const nuevoOrden = targetItem ? (targetItem.orden + 1) : 1;

        moverObligacion(data.obligacionId, nuevoParentId, nuevoOrden);
        draggedItem.value = null;
    };

    // Utilidades
    const construirArbol = (items: ObligacionContrato[]): ObligacionContrato[] => {
        const mapa = new Map<number, ObligacionContrato>();
        const raices: ObligacionContrato[] = [];

        // Crear mapa de items
        items.forEach(item => {
            mapa.set(item.id, { ...item, hijos: [] });
        });

        // Construir jerarquía
        items.forEach(item => {
            const nodo = mapa.get(item.id)!;
            if (item.parent_id === null) {
                raices.push(nodo);
            } else {
                const padre = mapa.get(item.parent_id);
                if (padre) {
                    padre.hijos = padre.hijos || [];
                    padre.hijos.push(nodo);
                }
            }
        });

        return raices;
    };

    const esDescendiente = (parentId: number, childId: number): boolean => {
        const obligacion = obligaciones.value.find(o => o.id === childId);
        if (!obligacion) return false;
        if (obligacion.parent_id === parentId) return true;
        if (obligacion.parent_id === null) return false;
        return esDescendiente(parentId, obligacion.parent_id);
    };

    const obtenerEstadoColor = (estado: EstadoObligacion): string => {
        const colores: Record<EstadoObligacion, string> = {
            'pendiente': 'gray',
            'en_progreso': 'blue',
            'cumplida': 'green',
            'vencida': 'red',
            'cancelada': 'yellow'
        };
        return colores[estado] || 'gray';
    };

    const obtenerPrioridadColor = (prioridad: PrioridadObligacion): string => {
        const colores: Record<PrioridadObligacion, string> = {
            'baja': 'green',
            'media': 'yellow',
            'alta': 'red'
        };
        return colores[prioridad] || 'gray';
    };

    return {
        // Estado
        obligaciones,
        obligacionActual,
        loading,
        errors,
        expandedNodes,
        selectedNode,
        draggedItem,
        estadisticas,
        filters,
        form,

        // Computed
        obligacionesArbol,
        obligacionesFiltradas,

        // Métodos principales
        cargarObligaciones,
        crearObligacion,
        actualizarObligacion,
        eliminarObligacion,
        completarObligacion,
        completarConHijos,
        duplicarObligacion,
        moverObligacion,
        reordenarObligaciones,

        // Gestión del árbol
        toggleNode,
        expandAll,
        collapseAll,
        selectNode,

        // Drag & Drop
        handleDragStart,
        handleDragOver,
        handleDrop,

        // Utilidades
        construirArbol,
        esDescendiente,
        obtenerEstadoColor,
        obtenerPrioridadColor
    };
}

export default useObligaciones;