<script setup lang="ts">
/**
 * Componente reutilizable para mostrar el estado, progreso, fechas y duración de un proyecto.
 * Se usa tanto en Admin/Show como en User/Show.
 */
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import {
    Calendar,
    Clock,
    Target,
    Flag,
    CheckCircle,
    PauseCircle,
    PlayCircle,
    Ban,
    AlertCircle
} from 'lucide-vue-next';

interface Props {
    estado: string;
    estadoLabel: string;
    prioridad: string;
    prioridadLabel: string;
    porcentajeCompletado: number;
    fechaInicio: string;
    fechaFin?: string;
    duracionDias?: number;
}

const props = defineProps<Props>();

// Función para obtener el ícono del estado
const estadoIcon = computed(() => {
    const icons: Record<string, any> = {
        'planificacion': Target,
        'en_progreso': PlayCircle,
        'pausado': PauseCircle,
        'completado': CheckCircle,
        'cancelado': Ban
    };
    return icons[props.estado] || AlertCircle;
});

// Función para obtener color del badge de estado
const estadoColor = computed(() => {
    const colors: Record<string, string> = {
        'planificacion': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'en_progreso': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'pausado': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'completado': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'cancelado': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[props.estado] || 'bg-gray-100 text-gray-800';
});

// Función para obtener color del badge de prioridad
const prioridadColor = computed(() => {
    const colors: Record<string, string> = {
        'baja': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'media': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'alta': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'critica': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[props.prioridad] || 'bg-gray-100 text-gray-800';
});

// Función para formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Función para formatear fecha relativa
const formatRelativeDate = (date: string) => {
    const days = Math.floor((new Date(date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (days === 0) return 'Hoy';
    if (days === 1) return 'Mañana';
    if (days === -1) return 'Ayer';
    if (days > 0) return `En ${days} días`;
    return `Hace ${Math.abs(days)} días`;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Estado del Proyecto</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Estado y Prioridad -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <component
                        :is="estadoIcon"
                        class="h-5 w-5 text-gray-500"
                    />
                    <span class="font-medium">Estado:</span>
                    <Badge :class="estadoColor">
                        {{ estadoLabel }}
                    </Badge>
                </div>
                <div class="flex items-center gap-3">
                    <Flag class="h-5 w-5 text-gray-500" />
                    <span class="font-medium">Prioridad:</span>
                    <Badge :class="prioridadColor">
                        {{ prioridadLabel }}
                    </Badge>
                </div>
            </div>

            <!-- Barra de progreso -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Progreso</span>
                    <span class="font-medium">{{ porcentajeCompletado }}%</span>
                </div>
                <Progress :model-value="porcentajeCompletado" class="h-2" />
            </div>

            <!-- Fechas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div class="flex items-start gap-3">
                    <Calendar class="h-5 w-5 text-gray-500 mt-0.5" />
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de inicio</p>
                        <p class="font-medium">{{ formatDate(fechaInicio) }}</p>
                        <p class="text-xs text-gray-500">{{ formatRelativeDate(fechaInicio) }}</p>
                    </div>
                </div>
                <div v-if="fechaFin" class="flex items-start gap-3">
                    <Calendar class="h-5 w-5 text-gray-500 mt-0.5" />
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de fin</p>
                        <p class="font-medium">{{ formatDate(fechaFin) }}</p>
                        <p class="text-xs text-gray-500">{{ formatRelativeDate(fechaFin) }}</p>
                    </div>
                </div>
            </div>

            <!-- Duración -->
            <div v-if="duracionDias" class="flex items-center gap-3 pt-2">
                <Clock class="h-5 w-5 text-gray-500" />
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Duración total:</span>
                    <span class="font-medium ml-2">{{ duracionDias }} días</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
