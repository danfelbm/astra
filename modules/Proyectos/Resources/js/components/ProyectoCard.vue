<script setup lang="ts">
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Calendar, User, Flag, Clock, ArrowRight } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

interface Proyecto {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: string;
    estado_label: string;
    prioridad: string;
    prioridad_label: string;
    responsable?: {
        id: number;
        name: string;
    };
    porcentaje_completado: number;
    duracion_dias?: number;
}

interface Props {
    proyecto: Proyecto;
    showActions?: boolean;
    linkUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true
});

// Función para obtener color del badge de estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        'planificacion': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'en_progreso': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'pausado': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'completado': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'cancelado': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

// Función para obtener color del badge de prioridad
const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        'baja': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'media': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'alta': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'critica': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

// Formatear fecha
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Calcular días restantes
const getDiasRestantes = (fechaFin: string) => {
    if (!fechaFin) return null;
    const dias = Math.ceil((new Date(fechaFin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
    if (dias === 0) return 'Vence hoy';
    if (dias === 1) return 'Vence mañana';
    return `${dias} días restantes`;
};
</script>

<template>
    <Card class="hover:shadow-lg transition-shadow duration-300">
        <CardHeader>
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <CardTitle class="text-lg">
                        {{ proyecto.nombre }}
                    </CardTitle>
                    <CardDescription v-if="proyecto.descripcion" class="mt-1 line-clamp-2">
                        {{ proyecto.descripcion }}
                    </CardDescription>
                </div>
                <div class="flex gap-2">
                    <Badge :class="getEstadoColor(proyecto.estado)" class="text-xs">
                        {{ proyecto.estado_label }}
                    </Badge>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Progreso -->
            <div v-if="proyecto.estado !== 'cancelado'" class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Progreso</span>
                    <span class="font-medium">{{ proyecto.porcentaje_completado }}%</span>
                </div>
                <Progress :value="proyecto.porcentaje_completado" class="h-2" />
            </div>

            <!-- Información rápida -->
            <div class="grid grid-cols-2 gap-3 text-sm">
                <!-- Fechas -->
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <Calendar class="h-4 w-4" />
                    <span>{{ formatDate(proyecto.fecha_inicio) }}</span>
                </div>

                <!-- Prioridad -->
                <div class="flex items-center gap-2">
                    <Flag class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                    <Badge :class="getPrioridadColor(proyecto.prioridad)" class="text-xs">
                        {{ proyecto.prioridad_label }}
                    </Badge>
                </div>

                <!-- Responsable -->
                <div v-if="proyecto.responsable" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <User class="h-4 w-4" />
                    <span class="truncate">{{ proyecto.responsable.name }}</span>
                </div>

                <!-- Tiempo restante -->
                <div v-if="proyecto.fecha_fin" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <Clock class="h-4 w-4" />
                    <span class="text-xs">{{ getDiasRestantes(proyecto.fecha_fin) }}</span>
                </div>
            </div>

            <!-- Duración -->
            <div v-if="proyecto.duracion_dias" class="pt-2 border-t">
                <p class="text-xs text-gray-500">
                    Duración total: {{ proyecto.duracion_dias }} días
                </p>
            </div>
        </CardContent>

        <CardFooter v-if="showActions" class="pt-0">
            <Link 
                :href="linkUrl || `/admin/proyectos/${proyecto.id}`"
                class="w-full"
            >
                <Button variant="outline" class="w-full group">
                    Ver detalles
                    <ArrowRight class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" />
                </Button>
            </Link>
        </CardFooter>
    </Card>
</template>