import { ref, computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import type {
    Contrato,
    ContratoFormData,
    ContratoFilters,
    EstadoContrato,
    TipoContrato,
    CampoPersonalizadoContrato,
    ContratoEstadisticas
} from '../types/contratos';
import {
    getEstadoLabel,
    getTipoLabel,
    getEstadoColor,
    calcularDiasRestantes,
    calcularPorcentajeTranscurrido,
    estaVencido,
    estaProximoVencer,
    validarContrato,
    formatMonto
} from '../types/contratos';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import { debounce } from 'lodash';

/**
 * Composable principal para gestión de contratos
 */
export function useContratos() {
    const toast = useToast();

    // Estado
    const loading = ref(false);
    const contratos = ref<Contrato[]>([]);
    const contratoSeleccionado = ref<Contrato | null>(null);

    // Métodos de utilidad exportados
    const utils = {
        getEstadoLabel,
        getTipoLabel,
        getEstadoColor,
        calcularDiasRestantes,
        calcularPorcentajeTranscurrido,
        estaVencido,
        estaProximoVencer,
        formatMonto
    };

    // Cargar contratos
    const loadContratos = async (filters?: ContratoFilters) => {
        loading.value = true;
        try {
            router.get(route('admin.contratos.index'), filters || {}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    if (page.props.contratos) {
                        contratos.value = page.props.contratos.data;
                    }
                },
                onFinish: () => {
                    loading.value = false;
                }
            });
        } catch (error) {
            toast.error('Error al cargar contratos');
            loading.value = false;
        }
    };

    // Crear contrato
    const createContrato = (data: ContratoFormData) => {
        const errores = validarContrato(data);
        if (Object.keys(errores).length > 0) {
            Object.values(errores).forEach(error => toast.error(error));
            return;
        }

        loading.value = true;
        router.post(route('admin.contratos.store'), data, {
            onSuccess: () => {
                toast.success('Contrato creado exitosamente');
            },
            onError: (errors) => {
                Object.values(errors).flat().forEach(error => {
                    toast.error(error as string);
                });
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    // Actualizar contrato
    const updateContrato = (id: number, data: Partial<ContratoFormData>) => {
        loading.value = true;
        router.put(route('admin.contratos.update', id), data, {
            onSuccess: () => {
                toast.success('Contrato actualizado exitosamente');
            },
            onError: (errors) => {
                Object.values(errors).flat().forEach(error => {
                    toast.error(error as string);
                });
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    // Eliminar contrato
    const deleteContrato = (contrato: Contrato) => {
        if (!confirm(`¿Está seguro de eliminar el contrato "${contrato.nombre}"?`)) {
            return;
        }

        loading.value = true;
        router.delete(route('admin.contratos.destroy', contrato.id), {
            onSuccess: () => {
                toast.success('Contrato eliminado exitosamente');
                contratos.value = contratos.value.filter(c => c.id !== contrato.id);
            },
            onError: () => {
                toast.error('Error al eliminar el contrato');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    // Cambiar estado
    const changeEstado = (contrato: Contrato, nuevoEstado: EstadoContrato) => {
        loading.value = true;
        router.post(route('admin.contratos.cambiar-estado', contrato.id), {
            estado: nuevoEstado
        }, {
            onSuccess: () => {
                toast.success('Estado actualizado exitosamente');
                if (contratoSeleccionado.value?.id === contrato.id) {
                    contratoSeleccionado.value.estado = nuevoEstado;
                }
            },
            onError: () => {
                toast.error('Error al cambiar el estado');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    // Duplicar contrato
    const duplicateContrato = (contrato: Contrato) => {
        if (!confirm(`¿Desea duplicar el contrato "${contrato.nombre}"?`)) {
            return;
        }

        loading.value = true;
        router.post(route('admin.contratos.duplicar', contrato.id), {}, {
            onSuccess: () => {
                toast.success('Contrato duplicado exitosamente');
            },
            onError: () => {
                toast.error('Error al duplicar el contrato');
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    return {
        // Estado
        loading,
        contratos,
        contratoSeleccionado,

        // Métodos
        loadContratos,
        createContrato,
        updateContrato,
        deleteContrato,
        changeEstado,
        duplicateContrato,

        // Utils
        utils
    };
}

/**
 * Composable para formularios de contrato
 */
export function useContratoForm(contratoInicial?: Contrato) {
    const toast = useToast();

    const form = useForm<ContratoFormData>({
        proyecto_id: contratoInicial?.proyecto_id || '',
        nombre: contratoInicial?.nombre || '',
        descripcion: contratoInicial?.descripcion || '',
        fecha_inicio: contratoInicial?.fecha_inicio || '',
        fecha_fin: contratoInicial?.fecha_fin || '',
        estado: contratoInicial?.estado || 'borrador',
        tipo: contratoInicial?.tipo || 'servicio',
        monto_total: contratoInicial?.monto_total?.toString() || '',
        moneda: contratoInicial?.moneda || 'USD',
        responsable_id: contratoInicial?.responsable_id?.toString() || '',
        contraparte_nombre: contratoInicial?.contraparte_nombre || '',
        contraparte_identificacion: contratoInicial?.contraparte_identificacion || '',
        contraparte_email: contratoInicial?.contraparte_email || '',
        contraparte_telefono: contratoInicial?.contraparte_telefono || '',
        archivo_pdf: null,
        observaciones: contratoInicial?.observaciones || '',
        campos_personalizados: {}
    });

    const validate = () => {
        const errores = validarContrato(form.data() as ContratoFormData);
        if (Object.keys(errores).length > 0) {
            Object.values(errores).forEach(error => toast.error(error));
            return false;
        }
        return true;
    };

    const submit = (url: string, method: 'post' | 'put' = 'post') => {
        if (!validate()) return;

        const formData = new FormData();

        // Convertir a FormData para manejar archivos
        Object.entries(form.data()).forEach(([key, value]) => {
            if (key === 'campos_personalizados' && typeof value === 'object') {
                Object.entries(value).forEach(([campoId, valor]) => {
                    if (valor !== null && valor !== undefined && valor !== '') {
                        formData.append(`campos_personalizados[${campoId}]`, valor);
                    }
                });
            } else if (key === 'archivo_pdf' && value instanceof File) {
                formData.append('archivo_pdf', value);
            } else if (value !== null && value !== undefined && value !== '') {
                formData.append(key, value.toString());
            }
        });

        if (method === 'put') {
            formData.append('_method', 'PUT');
        }

        router.post(url, formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success(method === 'post' ? 'Contrato creado exitosamente' : 'Contrato actualizado exitosamente');
            },
            onError: (errors) => {
                Object.values(errors).flat().forEach(error => {
                    toast.error(error as string);
                });
            }
        });
    };

    const reset = () => {
        form.reset();
    };

    return {
        form,
        validate,
        submit,
        reset
    };
}

/**
 * Composable para filtros de contratos
 */
export function useContratoFilters() {
    const filters = reactive<ContratoFilters>({
        search: '',
        proyecto_id: undefined,
        estado: undefined,
        tipo: undefined,
        responsable_id: undefined,
        vencidos: false,
        proximos_vencer: false,
        page: 1
    });

    const applyFilters = debounce(() => {
        router.get(route('admin.contratos.index'), filters, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    }, 300);

    const clearFilters = () => {
        Object.keys(filters).forEach(key => {
            filters[key] = undefined;
        });
        filters.page = 1;
        applyFilters();
    };

    const setFilter = <K extends keyof ContratoFilters>(
        key: K,
        value: ContratoFilters[K]
    ) => {
        filters[key] = value;
        filters.page = 1; // Reset página al cambiar filtros
        applyFilters();
    };

    const hasActiveFilters = computed(() => {
        return Object.entries(filters).some(([key, value]) => {
            if (key === 'page') return false;
            return value !== undefined && value !== '' && value !== false;
        });
    });

    return {
        filters,
        applyFilters,
        clearFilters,
        setFilter,
        hasActiveFilters
    };
}

/**
 * Composable para estadísticas de contratos
 */
export function useContratoStats(contratos: Contrato[]) {
    const stats = computed(() => {
        const total = contratos.length;
        const activos = contratos.filter(c => c.estado === 'activo').length;
        const borradores = contratos.filter(c => c.estado === 'borrador').length;
        const finalizados = contratos.filter(c => c.estado === 'finalizado').length;
        const cancelados = contratos.filter(c => c.estado === 'cancelado').length;
        const vencidos = contratos.filter(c => estaVencido(c)).length;
        const proximosVencer = contratos.filter(c => estaProximoVencer(c)).length;

        const montoTotal = contratos.reduce((sum, c) => sum + (c.monto_total || 0), 0);
        const montoActivos = contratos
            .filter(c => c.estado === 'activo')
            .reduce((sum, c) => sum + (c.monto_total || 0), 0);

        const porTipo = contratos.reduce((acc, c) => {
            acc[c.tipo] = (acc[c.tipo] || 0) + 1;
            return acc;
        }, {} as Record<TipoContrato, number>);

        return {
            total,
            activos,
            borradores,
            finalizados,
            cancelados,
            vencidos,
            proximosVencer,
            montoTotal,
            montoActivos,
            porTipo
        };
    });

    const chartData = computed(() => {
        const estadoData = {
            labels: ['Borrador', 'Activo', 'Finalizado', 'Cancelado'],
            datasets: [{
                label: 'Contratos por Estado',
                data: [
                    stats.value.borradores,
                    stats.value.activos,
                    stats.value.finalizados,
                    stats.value.cancelados
                ],
                backgroundColor: [
                    '#9CA3AF',
                    '#10B981',
                    '#3B82F6',
                    '#EF4444'
                ]
            }]
        };

        const tipoData = {
            labels: Object.keys(stats.value.porTipo),
            datasets: [{
                label: 'Contratos por Tipo',
                data: Object.values(stats.value.porTipo),
                backgroundColor: [
                    '#8B5CF6',
                    '#F59E0B',
                    '#6366F1',
                    '#EC4899',
                    '#6B7280'
                ]
            }]
        };

        return {
            estado: estadoData,
            tipo: tipoData
        };
    });

    return {
        stats,
        chartData
    };
}

/**
 * Composable para validación de campos personalizados
 */
export function useCamposPersonalizados(campos: CampoPersonalizadoContrato[]) {
    const valores = reactive<Record<number, any>>({});
    const errores = reactive<Record<number, string>>({});

    const setValor = (campoId: number, valor: any) => {
        valores[campoId] = valor;
        // Limpiar error al cambiar valor
        if (errores[campoId]) {
            delete errores[campoId];
        }
    };

    const validate = () => {
        let isValid = true;

        campos.forEach(campo => {
            const valor = valores[campo.id];

            // Validar requerido
            if (campo.es_requerido && !valor) {
                errores[campo.id] = `${campo.nombre} es obligatorio`;
                isValid = false;
                return;
            }

            // Validar según tipo
            switch (campo.tipo) {
                case 'number':
                    if (valor && isNaN(Number(valor))) {
                        errores[campo.id] = `${campo.nombre} debe ser un número`;
                        isValid = false;
                    }
                    break;

                case 'email':
                    if (valor && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) {
                        errores[campo.id] = `${campo.nombre} debe ser un email válido`;
                        isValid = false;
                    }
                    break;

                case 'date':
                    if (valor && !Date.parse(valor)) {
                        errores[campo.id] = `${campo.nombre} debe ser una fecha válida`;
                        isValid = false;
                    }
                    break;
            }
        });

        return isValid;
    };

    const getValoresFormateados = () => {
        const formatted: Record<number, any> = {};

        Object.entries(valores).forEach(([campoId, valor]) => {
            if (valor !== null && valor !== undefined && valor !== '') {
                formatted[Number(campoId)] = valor;
            }
        });

        return formatted;
    };

    return {
        valores,
        errores,
        setValor,
        validate,
        getValoresFormateados
    };
}