<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
    Calendar, Clock, CheckCircle, AlertCircle, XCircle,
    User, ChevronRight, Target, Flag
} from 'lucide-vue-next';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface Props {
    hitos: Hito[];
    showProject?: boolean;
    onHitoClick?: (hito: Hito) => void;
}

const props = withDefaults(defineProps<Props>(), {
    showProject: false,
});

const emit = defineEmits<{
    'hito-click': [hito: Hito]
}>();

// Agrupar hitos por estado
const hitosAgrupados = computed(() => {
    const grupos = {
        completados: [] as Hito[],
        en_progreso: [] as Hito[],
        pendientes: [] as Hito[],
        cancelados: [] as Hito[],
    };

    props.hitos.forEach(hito => {
        switch (hito.estado) {
            case 'completado':
                grupos.completados.push(hito);
                break;
            case 'en_progreso':
                grupos.en_progreso.push(hito);
                break;
            case 'pendiente':
                grupos.pendientes.push(hito);
                break;
            case 'cancelado':
                grupos.cancelados.push(hito);
                break;
        }
    });

    return grupos;
});

// Función para obtener el color del estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-800 border-gray-300',
        en_progreso: 'bg-blue-100 text-blue-800 border-blue-300',
        completado: 'bg-green-100 text-green-800 border-green-300',
        cancelado: 'bg-red-100 text-red-800 border-red-300',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener el ícono del estado
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'completado':
            return CheckCircle;
        case 'en_progreso':
            return Clock;
        case 'pendiente':
            return AlertCircle;
        case 'cancelado':
            return XCircle;
        default:
            return Target;
    }
};

// Función para formatear fecha
const formatDate = (date: string | null) => {
    if (!date) return 'Sin fecha';
    return format(new Date(date), 'dd MMM yyyy', { locale: es });
};

// Función para calcular días restantes
const getDiasRestantes = (fechaFin: string | null) => {
    if (!fechaFin) return null;
    const dias = Math.ceil((new Date(fechaFin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (dias < 0) return `Vencido hace ${Math.abs(dias)} días`;
    if (dias === 0) return 'Vence hoy';
    if (dias === 1) return 'Vence mañana';
    return `${dias} días restantes`;
};

// Función para manejar click en hito
const handleHitoClick = (hito: Hito) => {
    emit('hito-click', hito);
    if (props.onHitoClick) {
        props.onHitoClick(hito);
    }
};

// Calcular estadísticas de entregables
const getEstadisticasEntregables = (hito: Hito) => {
    if (!hito.entregables || hito.entregables.length === 0) {
        return { total: 0, completados: 0, porcentaje: 0 };
    }
    const total = hito.entregables.length;
    const completados = hito.entregables.filter(e => e.estado === 'completado').length;
    const porcentaje = Math.round((completados / total) * 100);
    return { total, completados, porcentaje };
};
</script>

<template>
    <div class="space-y-8">
        <!-- Timeline de Hitos -->
        <div class="relative">
            <!-- Línea vertical del timeline -->
            <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            <!-- Sección: En Progreso -->
            <div v-if="hitosAgrupados.en_progreso.length > 0" class="mb-8">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <Clock class="h-5 w-5 text-blue-600" />
                    En Progreso
                </h3>
                <div class="space-y-4">
                    <div v-for="hito in hitosAgrupados.en_progreso" :key="hito.id" class="relative flex gap-4">
                        <!-- Punto en el timeline -->
                        <div class="absolute left-4 w-4 h-4 bg-blue-500 rounded-full border-2 border-white shadow-sm"></div>

                        <!-- Card del hito -->
                        <Card
                            class="ml-12 flex-1 hover:shadow-lg transition-all cursor-pointer border-l-4"
                            :class="getEstadoColor(hito.estado)"
                            @click="handleHitoClick(hito)"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <CardTitle class="flex items-center gap-2 text-lg">
                                            <component :is="getEstadoIcon(hito.estado)" class="h-5 w-5" />
                                            {{ hito.nombre }}
                                        </CardTitle>
                                        <CardDescription v-if="showProject && hito.proyecto">
                                            {{ hito.proyecto.nombre }}
                                        </CardDescription>
                                    </div>
                                    <Badge :class="getEstadoColor(hito.estado)" variant="outline">
                                        {{ hito.estado }}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="hito.descripcion" class="text-sm text-muted-foreground">
                                    {{ hito.descripcion }}
                                </div>

                                <!-- Progreso -->
                                <div v-if="hito.entregables && hito.entregables.length > 0">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium">Progreso</span>
                                        <span class="text-sm text-muted-foreground">
                                            {{ getEstadisticasEntregables(hito).completados }}/{{ getEstadisticasEntregables(hito).total }} entregables
                                        </span>
                                    </div>
                                    <Progress :value="getEstadisticasEntregables(hito).porcentaje" class="h-2" />
                                </div>

                                <!-- Información adicional -->
                                <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>{{ formatDate(hito.fecha_inicio) }} - {{ formatDate(hito.fecha_fin) }}</span>
                                    </div>
                                    <div v-if="hito.responsable" class="flex items-center gap-1">
                                        <User class="h-4 w-4" />
                                        <span>{{ hito.responsable.name }}</span>
                                    </div>
                                    <div v-if="getDiasRestantes(hito.fecha_fin)"
                                         class="flex items-center gap-1"
                                         :class="{ 'text-red-600': getDiasRestantes(hito.fecha_fin)?.includes('Vencido') }">
                                        <Clock class="h-4 w-4" />
                                        <span>{{ getDiasRestantes(hito.fecha_fin) }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Sección: Pendientes -->
            <div v-if="hitosAgrupados.pendientes.length > 0" class="mb-8">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <AlertCircle class="h-5 w-5 text-yellow-600" />
                    Pendientes
                </h3>
                <div class="space-y-4">
                    <div v-for="hito in hitosAgrupados.pendientes" :key="hito.id" class="relative flex gap-4">
                        <!-- Punto en el timeline -->
                        <div class="absolute left-4 w-4 h-4 bg-yellow-500 rounded-full border-2 border-white shadow-sm"></div>

                        <!-- Card del hito -->
                        <Card
                            class="ml-12 flex-1 hover:shadow-lg transition-all cursor-pointer border-l-4 border-yellow-300"
                            @click="handleHitoClick(hito)"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <CardTitle class="flex items-center gap-2 text-lg">
                                            <AlertCircle class="h-5 w-5 text-yellow-600" />
                                            {{ hito.nombre }}
                                        </CardTitle>
                                        <CardDescription v-if="showProject && hito.proyecto">
                                            {{ hito.proyecto.nombre }}
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" class="bg-yellow-50">
                                        Pendiente
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="hito.descripcion" class="text-sm text-muted-foreground">
                                    {{ hito.descripcion }}
                                </div>

                                <!-- Información adicional -->
                                <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>Inicio: {{ formatDate(hito.fecha_inicio) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Flag class="h-4 w-4" />
                                        <span>Fin: {{ formatDate(hito.fecha_fin) }}</span>
                                    </div>
                                    <div v-if="hito.responsable" class="flex items-center gap-1">
                                        <User class="h-4 w-4" />
                                        <span>{{ hito.responsable.name }}</span>
                                    </div>
                                </div>

                                <div v-if="hito.entregables && hito.entregables.length > 0" class="text-sm text-muted-foreground">
                                    {{ hito.entregables.length }} entregables pendientes
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Sección: Completados -->
            <div v-if="hitosAgrupados.completados.length > 0" class="mb-8">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <CheckCircle class="h-5 w-5 text-green-600" />
                    Completados
                </h3>
                <div class="space-y-4">
                    <div v-for="hito in hitosAgrupados.completados" :key="hito.id" class="relative flex gap-4">
                        <!-- Punto en el timeline -->
                        <div class="absolute left-4 w-4 h-4 bg-green-500 rounded-full border-2 border-white shadow-sm">
                            <CheckCircle class="h-3 w-3 text-white absolute -top-0.5 -left-0.5" />
                        </div>

                        <!-- Card del hito -->
                        <Card
                            class="ml-12 flex-1 hover:shadow-lg transition-all cursor-pointer border-l-4 border-green-300 opacity-75"
                            @click="handleHitoClick(hito)"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <CardTitle class="flex items-center gap-2 text-lg">
                                            <CheckCircle class="h-5 w-5 text-green-600" />
                                            {{ hito.nombre }}
                                        </CardTitle>
                                        <CardDescription v-if="showProject && hito.proyecto">
                                            {{ hito.proyecto.nombre }}
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" class="bg-green-50">
                                        Completado
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <CheckCircle class="h-4 w-4 text-green-600" />
                                        <span>100% completado</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>Completado: {{ formatDate(hito.fecha_fin) }}</span>
                                    </div>
                                    <div v-if="hito.responsable" class="flex items-center gap-1">
                                        <User class="h-4 w-4" />
                                        <span>{{ hito.responsable.name }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Mensaje si no hay hitos -->
            <div v-if="hitos.length === 0" class="text-center py-12">
                <Target class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <p class="text-lg font-medium text-muted-foreground">No hay hitos disponibles</p>
                <p class="text-sm text-muted-foreground">Los hitos aparecerán aquí cuando se creen</p>
            </div>
        </div>
    </div>
</template>