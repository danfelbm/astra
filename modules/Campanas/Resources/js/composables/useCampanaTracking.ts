import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export interface CampanaMetrics {
    total_destinatarios: number;
    total_enviados: number;
    total_pendientes: number;
    total_fallidos: number;
    emails_enviados: number;
    emails_abiertos: number;
    emails_con_click: number;
    emails_rebotados: number;
    total_clicks: number;
    tasa_apertura: number;
    tasa_click: number;
    tasa_rebote: number;
    whatsapp_enviados: number;
    whatsapp_fallidos: number;
    whatsapp_entregados: number;
    tiempo_promedio_apertura: number;
    tiempo_promedio_click: number;
    ultima_actualizacion?: string;
}

export interface CampanaProgreso {
    porcentaje: number;
    enviados: number;
    total: number;
    estado: 'iniciando' | 'en_progreso' | 'finalizando' | 'completado';
    velocidad: number; // envíos por minuto
    tiempo_restante: number; // minutos
}

export function useCampanaTracking(campanaId?: number) {
    const metrics = ref<CampanaMetrics | null>(null);
    const progreso = ref<CampanaProgreso | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const pollingInterval = ref<number | null>(null);

    // Obtener métricas de una campaña
    const fetchMetrics = async (id?: number) => {
        const targetId = id || campanaId;
        if (!targetId) return;

        isLoading.value = true;
        error.value = null;

        try {
            await router.get(`/admin/envio-campanas/${targetId}/metrics`, {}, {
                preserveState: true,
                preserveScroll: true,
                only: ['metricas', 'progreso'],
                onSuccess: (page: any) => {
                    metrics.value = page.props.metricas;
                    progreso.value = page.props.progreso;
                },
                onError: () => {
                    error.value = 'Error al obtener métricas';
                },
            });
        } finally {
            isLoading.value = false;
        }
    };

    // Iniciar polling de métricas
    const startPolling = (intervalMs: number = 5000) => {
        stopPolling();
        
        fetchMetrics();
        pollingInterval.value = window.setInterval(() => {
            fetchMetrics();
        }, intervalMs);
    };

    // Detener polling
    const stopPolling = () => {
        if (pollingInterval.value) {
            clearInterval(pollingInterval.value);
            pollingInterval.value = null;
        }
    };

    // Calcular métricas derivadas
    const derivedMetrics = computed(() => {
        if (!metrics.value) return null;

        const m = metrics.value;
        
        return {
            // Tasa de entrega
            tasaEntrega: m.total_enviados > 0 
                ? ((m.total_enviados - m.total_fallidos) / m.total_enviados * 100).toFixed(2)
                : '0.00',
            
            // Engagement total
            engagementRate: m.emails_enviados > 0
                ? ((m.emails_abiertos + m.emails_con_click) / (m.emails_enviados * 2) * 100).toFixed(2)
                : '0.00',
            
            // Click-through rate (CTR)
            ctr: m.emails_abiertos > 0
                ? (m.emails_con_click / m.emails_abiertos * 100).toFixed(2)
                : '0.00',
            
            // Promedio clicks por usuario
            clicksPorUsuario: m.emails_con_click > 0
                ? (m.total_clicks / m.emails_con_click).toFixed(2)
                : '0.00',
            
            // Estado de completitud
            completitud: m.total_destinatarios > 0
                ? ((m.total_enviados / m.total_destinatarios) * 100).toFixed(1)
                : '0.0',
            
            // Tasa de éxito WhatsApp
            tasaExitoWhatsApp: m.whatsapp_enviados > 0
                ? ((m.whatsapp_entregados / m.whatsapp_enviados) * 100).toFixed(2)
                : '0.00',
        };
    });

    // Formatear tiempo
    const formatTime = (minutos: number): string => {
        if (minutos < 60) {
            return `${Math.round(minutos)} min`;
        }
        
        const horas = Math.floor(minutos / 60);
        const mins = Math.round(minutos % 60);
        
        if (horas < 24) {
            return `${horas}h ${mins}m`;
        }
        
        const dias = Math.floor(horas / 24);
        const horasRestantes = horas % 24;
        
        return `${dias}d ${horasRestantes}h`;
    };

    // Obtener color según métrica
    const getMetricColor = (value: number, type: 'apertura' | 'click' | 'rebote' | 'entrega'): string => {
        const thresholds = {
            apertura: { good: 20, warning: 10 },
            click: { good: 5, warning: 2 },
            rebote: { good: 5, warning: 10 }, // Inverso: menor es mejor
            entrega: { good: 95, warning: 85 },
        };
        
        const t = thresholds[type];
        
        if (type === 'rebote') {
            if (value <= t.good) return 'text-green-600';
            if (value <= t.warning) return 'text-yellow-600';
            return 'text-red-600';
        }
        
        if (value >= t.good) return 'text-green-600';
        if (value >= t.warning) return 'text-yellow-600';
        return 'text-red-600';
    };

    // Exportar reporte
    const exportReport = async (formato: 'excel' | 'pdf' | 'csv' = 'excel', incluirDetalles: boolean = false) => {
        if (!campanaId) return;
        
        isLoading.value = true;
        
        try {
            await router.post(`/admin/envio-campanas/${campanaId}/export`, {
                formato,
                incluir_detalles: incluirDetalles,
            }, {
                onSuccess: (response: any) => {
                    // Descargar archivo
                    const url = response.props.download_url;
                    if (url) {
                        window.open(url, '_blank');
                    }
                },
                onError: () => {
                    error.value = 'Error al exportar reporte';
                },
            });
        } finally {
            isLoading.value = false;
        }
    };

    // Calcular tendencia
    const calculateTrend = (current: number, previous: number): 'up' | 'down' | 'stable' => {
        const diff = current - previous;
        if (Math.abs(diff) < 0.5) return 'stable';
        return diff > 0 ? 'up' : 'down';
    };

    // Formatear número grande
    const formatLargeNumber = (num: number): string => {
        if (num >= 1000000) {
            return `${(num / 1000000).toFixed(1)}M`;
        }
        if (num >= 1000) {
            return `${(num / 1000).toFixed(1)}K`;
        }
        return num.toString();
    };

    // Cleanup al desmontar
    const cleanup = () => {
        stopPolling();
    };

    return {
        metrics,
        progreso,
        derivedMetrics,
        isLoading,
        error,
        fetchMetrics,
        startPolling,
        stopPolling,
        formatTime,
        getMetricColor,
        exportReport,
        calculateTrend,
        formatLargeNumber,
        cleanup,
    };
}