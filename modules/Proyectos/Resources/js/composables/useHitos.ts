import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';

export function useHitos() {
    const loading = ref(false);
    const processing = ref(false);

    /**
     * Crea un nuevo hito
     */
    const createHito = (proyectoId: number, data: Partial<Hito>) => {
        processing.value = true;

        router.post(`/admin/proyectos/${proyectoId}/hitos`, data, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Hito creado exitosamente');
                processing.value = false;
            },
            onError: () => {
                toast.error('Error al crear el hito');
                processing.value = false;
            },
        });
    };

    /**
     * Actualiza un hito existente
     */
    const updateHito = (proyectoId: number, hitoId: number, data: Partial<Hito>) => {
        processing.value = true;

        router.put(`/admin/proyectos/${proyectoId}/hitos/${hitoId}`, data, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Hito actualizado exitosamente');
                processing.value = false;
            },
            onError: () => {
                toast.error('Error al actualizar el hito');
                processing.value = false;
            },
        });
    };

    /**
     * Elimina un hito
     */
    const deleteHito = (proyectoId: number, hitoId: number, hitoNombre: string) => {
        if (!confirm(`¿Estás seguro de eliminar el hito "${hitoNombre}"? Se eliminarán también todos sus entregables.`)) {
            return;
        }

        processing.value = true;

        router.delete(`/admin/proyectos/${proyectoId}/hitos/${hitoId}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Hito eliminado exitosamente');
                processing.value = false;
            },
            onError: () => {
                toast.error('Error al eliminar el hito');
                processing.value = false;
            },
        });
    };

    /**
     * Duplica un hito con sus entregables
     */
    const duplicateHito = (proyectoId: number, hitoId: number) => {
        processing.value = true;

        router.post(`/admin/proyectos/${proyectoId}/hitos/${hitoId}/duplicar`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Hito duplicado exitosamente');
                processing.value = false;
            },
            onError: () => {
                toast.error('Error al duplicar el hito');
                processing.value = false;
            },
        });
    };

    /**
     * Marca un entregable como completado
     */
    const completeEntregable = (
        proyectoId: number,
        hitoId: number,
        entregableId: number,
        notas?: string
    ) => {
        processing.value = true;

        router.post(`/admin/proyectos/${proyectoId}/hitos/${hitoId}/entregables/${entregableId}/completar`,
            { notas },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Entregable completado');
                    processing.value = false;
                },
                onError: () => {
                    toast.error('Error al completar el entregable');
                    processing.value = false;
                },
            }
        );
    };

    /**
     * Actualiza el orden de los hitos
     */
    const reorderHitos = (proyectoId: number, hitos: { id: number; orden: number }[]) => {
        processing.value = true;

        router.post(`/admin/proyectos/${proyectoId}/hitos/reordenar`,
            { hitos },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success('Orden actualizado');
                    processing.value = false;
                },
                onError: () => {
                    toast.error('Error al actualizar el orden');
                    processing.value = false;
                },
            }
        );
    };

    /**
     * Actualiza el orden de los entregables
     */
    const reorderEntregables = (
        proyectoId: number,
        hitoId: number,
        entregables: { id: number; orden: number }[]
    ) => {
        processing.value = true;

        router.post(`/admin/proyectos/${proyectoId}/hitos/${hitoId}/entregables/reordenar`,
            { entregables },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success('Orden actualizado');
                    processing.value = false;
                },
                onError: () => {
                    toast.error('Error al actualizar el orden');
                    processing.value = false;
                },
            }
        );
    };

    /**
     * Calcula el progreso total de un conjunto de hitos
     */
    const calculateOverallProgress = (hitos: Hito[]): number => {
        if (hitos.length === 0) return 0;

        const totalProgress = hitos.reduce((sum, hito) => sum + (hito.porcentaje_completado || 0), 0);
        return Math.round(totalProgress / hitos.length);
    };

    /**
     * Obtiene estadísticas de hitos
     */
    const getHitosStats = (hitos: Hito[]) => {
        return {
            total: hitos.length,
            pendientes: hitos.filter(h => h.estado === 'pendiente').length,
            enProgreso: hitos.filter(h => h.estado === 'en_progreso').length,
            completados: hitos.filter(h => h.estado === 'completado').length,
            cancelados: hitos.filter(h => h.estado === 'cancelado').length,
            vencidos: hitos.filter(h => {
                if (h.fecha_fin && h.estado !== 'completado' && h.estado !== 'cancelado') {
                    return new Date(h.fecha_fin) < new Date();
                }
                return false;
            }).length,
            proximosVencer: hitos.filter(h => {
                if (h.fecha_fin && h.estado !== 'completado' && h.estado !== 'cancelado') {
                    const diasRestantes = Math.floor(
                        (new Date(h.fecha_fin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
                    );
                    return diasRestantes >= 0 && diasRestantes <= 7;
                }
                return false;
            }).length,
        };
    };

    /**
     * Formatea el estado para mostrar
     */
    const formatEstado = (estado: string): string => {
        const estados: Record<string, string> = {
            pendiente: 'Pendiente',
            en_progreso: 'En Progreso',
            completado: 'Completado',
            cancelado: 'Cancelado',
        };
        return estados[estado] || estado;
    };

    /**
     * Obtiene el color del badge según el estado
     */
    const getEstadoColor = (estado: string): string => {
        const colores: Record<string, string> = {
            pendiente: 'secondary',
            en_progreso: 'default',
            completado: 'success',
            cancelado: 'destructive',
        };
        return colores[estado] || 'secondary';
    };

    /**
     * Obtiene el color de prioridad
     */
    const getPrioridadColor = (prioridad: string): string => {
        const colores: Record<string, string> = {
            baja: 'blue',
            media: 'yellow',
            alta: 'red',
        };
        return colores[prioridad] || 'gray';
    };

    return {
        loading,
        processing,
        createHito,
        updateHito,
        deleteHito,
        duplicateHito,
        completeEntregable,
        reorderHitos,
        reorderEntregables,
        calculateOverallProgress,
        getHitosStats,
        formatEstado,
        getEstadoColor,
        getPrioridadColor,
    };
}