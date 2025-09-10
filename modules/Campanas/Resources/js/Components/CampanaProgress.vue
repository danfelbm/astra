<script setup lang="ts">
import { computed } from 'vue';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Clock, Send, CheckCircle2, XCircle, AlertCircle, TrendingUp } from 'lucide-vue-next';

interface Props {
    progreso?: number; // Porcentaje de progreso calculado externamente
    total: number;
    enviados: number;
    pendientes: number;
    fallidos: number;
    estado: string;
    velocidad?: number; // envíos por minuto
    tiempoRestante?: number; // minutos
    fechaInicio?: string;
}

const props = defineProps<Props>();

const porcentaje = computed(() => {
    let valor = 0;
    // Si se pasa el progreso como prop, usarlo directamente
    if (props.progreso !== undefined) {
        valor = props.progreso;
    } else {
        // Si no, calcularlo internamente
        if (props.total === 0) return 0;
        valor = Math.round((props.enviados / props.total) * 100);
    }
    // Asegurar que el valor esté entre 0 y 100
    return Math.max(0, Math.min(valor, 100));
});

const porcentajeFallidos = computed(() => {
    if (props.total === 0) return 0;
    return Math.round((props.fallidos / props.total) * 100);
});

const formatTime = (minutos?: number): string => {
    if (!minutos) return '--';
    
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

const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        'iniciando': 'text-blue-600',
        'enviando': 'text-green-600',
        'pausada': 'text-yellow-600',
        'completada': 'text-green-700',
        'cancelada': 'text-red-600',
        'fallida': 'text-red-700',
    };
    return colors[estado] || 'text-gray-600';
};

const getEstadoBadge = (estado: string) => {
    const badges: Record<string, any> = {
        'iniciando': { variant: 'default', text: 'Iniciando' },
        'enviando': { variant: 'default', text: 'Enviando' },
        'pausada': { variant: 'warning', text: 'Pausada' },
        'completada': { variant: 'success', text: 'Completada' },
        'cancelada': { variant: 'destructive', text: 'Cancelada' },
        'fallida': { variant: 'destructive', text: 'Fallida' },
    };
    return badges[estado] || { variant: 'secondary', text: estado };
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex justify-between items-center">
                <CardTitle class="text-base">Progreso de Envío</CardTitle>
                <Badge :variant="getEstadoBadge(estado).variant">
                    {{ getEstadoBadge(estado).text }}
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Barra de progreso principal -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Progreso total</span>
                    <span class="font-medium">{{ porcentaje }}%</span>
                </div>
                <Progress :model-value="porcentaje" class="h-3" />
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span>{{ enviados }} enviados</span>
                    <span>{{ total }} total</span>
                </div>
            </div>

            <!-- Estadísticas detalladas -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4 text-green-600" />
                        <span class="text-sm font-medium">Enviados</span>
                    </div>
                    <p class="text-2xl font-bold">{{ enviados.toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ porcentaje }}% completado
                    </p>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Clock class="w-4 h-4 text-blue-600" />
                        <span class="text-sm font-medium">Pendientes</span>
                    </div>
                    <p class="text-2xl font-bold">{{ pendientes.toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground">
                        Por enviar
                    </p>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <XCircle class="w-4 h-4 text-red-600" />
                        <span class="text-sm font-medium">Fallidos</span>
                    </div>
                    <p class="text-2xl font-bold">{{ fallidos.toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ porcentajeFallidos }}% del total
                    </p>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <TrendingUp class="w-4 h-4 text-purple-600" />
                        <span class="text-sm font-medium">Velocidad</span>
                    </div>
                    <p class="text-2xl font-bold">
                        {{ velocidad ? velocidad.toFixed(1) : '--' }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        envíos/min
                    </p>
                </div>
            </div>

            <!-- Tiempo estimado -->
            <div v-if="tiempoRestante || fechaInicio" class="pt-2 border-t">
                <div class="flex justify-between items-center text-sm">
                    <div v-if="fechaInicio">
                        <span class="text-muted-foreground">Iniciado:</span>
                        <span class="ml-2">{{ new Date(fechaInicio).toLocaleString('es-CO') }}</span>
                    </div>
                    <div v-if="tiempoRestante">
                        <span class="text-muted-foreground">Tiempo restante:</span>
                        <span class="ml-2 font-medium">{{ formatTime(tiempoRestante) }}</span>
                    </div>
                </div>
            </div>

            <!-- Advertencias -->
            <div v-if="porcentajeFallidos > 10" class="p-3 bg-red-50 border border-red-200 rounded-md">
                <div class="flex items-center gap-2">
                    <AlertCircle class="w-4 h-4 text-red-600" />
                    <span class="text-sm text-red-800">
                        Alta tasa de fallos ({{ porcentajeFallidos }}%). 
                        Revisa la configuración de envío.
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>