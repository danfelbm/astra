<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import UserLayout from '@modules/Core/Resources/js/layouts/UserLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import {
    Calendar, Clock, Target, Users, CheckCircle, XCircle,
    AlertCircle, ArrowLeft, FileText, User, Hash, Flag,
    ChevronRight, ChevronDown
} from 'lucide-vue-next';
import type { Hito, Entregable } from '@modules/Proyectos/Resources/js/types/hitos';
import EntregablesList from '@modules/Proyectos/Resources/js/components/EntregablesList.vue';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import type { PageProps } from '@/types';

interface Props {
    hito: Hito;
    canComplete: boolean;
    canUpdateProgress: boolean;
}

const props = defineProps<Props>();
const page = usePage<PageProps>();
const { success, error } = useToast();

// Estado local
const expandedEntregables = ref<Record<number, boolean>>({});
const notasCompletado = ref<Record<number, string>>({});
const procesando = ref<Record<number, boolean>>({});

// Toggle expandir/colapsar entregable
const toggleEntregable = (entregableId: number) => {
    expandedEntregables.value[entregableId] = !expandedEntregables.value[entregableId];
};

// Función para completar un entregable
const completarEntregable = (entregable: Entregable) => {
    if (procesando.value[entregable.id]) return;

    procesando.value[entregable.id] = true;

    router.post(route('user.mis-hitos.completar', {
        hito: props.hito.id,
        entregable: entregable.id
    }), {
        notas: notasCompletado.value[entregable.id] || ''
    }, {
        preserveScroll: true,
        onSuccess: () => {
            success('Entregable marcado como completado');
            notasCompletado.value[entregable.id] = '';
            procesando.value[entregable.id] = false;
        },
        onError: () => {
            error('Error al completar el entregable');
            procesando.value[entregable.id] = false;
        }
    });
};

// Función para actualizar estado de entregable
const actualizarEstadoEntregable = (entregable: Entregable, nuevoEstado: string) => {
    if (!props.canUpdateProgress) return;

    router.put(route('user.mis-hitos.actualizar-estado', {
        hito: props.hito.id,
        entregable: entregable.id
    }), {
        estado: nuevoEstado
    }, {
        preserveScroll: true,
        onSuccess: () => {
            success('Estado actualizado correctamente');
        },
        onError: () => {
            error('Error al actualizar el estado');
        }
    });
};

// Función para regresar
const regresar = () => {
    router.visit(route('user.mis-hitos.index'));
};

// Función para formatear fecha
const formatDate = (date: string | null) => {
    if (!date) return 'No definida';
    return format(new Date(date), 'dd MMM yyyy', { locale: es });
};

// Función para formatear fecha con hora
const formatDateTime = (date: string | null) => {
    if (!date) return 'No definida';
    return format(new Date(date), "dd MMM yyyy 'a las' HH:mm", { locale: es });
};

// Función para obtener color del estado
const getEstadoColor = (estado: string) => {
    const colors: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-800',
        en_progreso: 'bg-blue-100 text-blue-800',
        completado: 'bg-green-100 text-green-800',
        cancelado: 'bg-red-100 text-red-800',
    };
    return colors[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener color de prioridad
const getPrioridadColor = (prioridad: string) => {
    const colors: Record<string, string> = {
        baja: 'bg-gray-100 text-gray-600',
        media: 'bg-yellow-100 text-yellow-800',
        alta: 'bg-red-100 text-red-800',
    };
    return colors[prioridad] || 'bg-gray-100 text-gray-600';
};

// Función para obtener icono del estado
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'pendiente':
            return AlertCircle;
        case 'en_progreso':
            return Clock;
        case 'completado':
            return CheckCircle;
        case 'cancelado':
            return XCircle;
        default:
            return AlertCircle;
    }
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

// Entregables agrupados por estado
const entregablesAgrupados = computed(() => {
    if (!props.hito.entregables) return { pendientes: [], en_progreso: [], completados: [] };

    return {
        pendientes: props.hito.entregables.filter(e => e.estado === 'pendiente'),
        en_progreso: props.hito.entregables.filter(e => e.estado === 'en_progreso'),
        completados: props.hito.entregables.filter(e => e.estado === 'completado'),
    };
});

// Estadísticas de entregables
const estadisticasEntregables = computed(() => {
    if (!props.hito.entregables) return { total: 0, completados: 0, porcentaje: 0 };

    const total = props.hito.entregables.length;
    const completados = props.hito.entregables.filter(e => e.estado === 'completado').length;
    const porcentaje = total > 0 ? Math.round((completados / total) * 100) : 0;

    return { total, completados, porcentaje };
});
</script>

<template>
    <UserLayout>
        <Head :title="`Hito: ${hito.nombre}`" />

        <div class="space-y-6">
            <!-- Header con navegación -->
            <div class="flex items-center gap-4">
                <Button @click="regresar" variant="ghost" size="sm" class="gap-2">
                    <ArrowLeft class="h-4 w-4" />
                    Volver a Mis Hitos
                </Button>
            </div>

            <!-- Información del Hito -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <CardTitle class="text-2xl">{{ hito.nombre }}</CardTitle>
                            <CardDescription>
                                Proyecto: {{ hito.proyecto?.nombre || 'Sin proyecto' }}
                            </CardDescription>
                        </div>
                        <Badge :class="getEstadoColor(hito.estado)" class="ml-4">
                            {{ hito.estado }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Descripción -->
                    <div v-if="hito.descripcion">
                        <h3 class="font-semibold mb-2">Descripción</h3>
                        <p class="text-sm text-muted-foreground">{{ hito.descripcion }}</p>
                    </div>

                    <!-- Progreso General -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold">Progreso General</h3>
                            <span class="text-sm font-medium">{{ estadisticasEntregables.porcentaje }}%</span>
                        </div>
                        <Progress :value="estadisticasEntregables.porcentaje" class="h-2" />
                        <p class="text-sm text-muted-foreground mt-2">
                            {{ estadisticasEntregables.completados }} de {{ estadisticasEntregables.total }} entregables completados
                        </p>
                    </div>

                    <!-- Información adicional en grid -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground flex items-center gap-1">
                                <Calendar class="h-4 w-4" />
                                Fecha de Inicio
                            </p>
                            <p class="text-sm font-medium">{{ formatDate(hito.fecha_inicio) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground flex items-center gap-1">
                                <Calendar class="h-4 w-4" />
                                Fecha de Fin
                            </p>
                            <p class="text-sm font-medium">{{ formatDate(hito.fecha_fin) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground flex items-center gap-1">
                                <Clock class="h-4 w-4" />
                                Tiempo Restante
                            </p>
                            <p class="text-sm font-medium" :class="{ 'text-red-600': hito.estado !== 'completado' && getDiasRestantes(hito.fecha_fin)?.includes('Vencido') }">
                                {{ getDiasRestantes(hito.fecha_fin) || 'Sin fecha límite' }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground flex items-center gap-1">
                                <User class="h-4 w-4" />
                                Responsable
                            </p>
                            <p class="text-sm font-medium">{{ hito.responsable?.name || 'Sin responsable' }}</p>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <div v-if="getDiasRestantes(hito.fecha_fin)?.includes('Vencido') && hito.estado !== 'completado'" class="mt-4">
                        <Alert variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                Este hito está vencido. Por favor, actualiza su estado o contacta a tu supervisor.
                            </AlertDescription>
                        </Alert>
                    </div>
                </CardContent>
            </Card>

            <!-- Entregables -->
            <Card>
                <CardHeader>
                    <CardTitle>Entregables</CardTitle>
                    <CardDescription>
                        Lista de entregables asociados a este hito
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Entregables agrupados por estado -->
                    <div class="space-y-6">
                        <!-- Pendientes -->
                        <div v-if="entregablesAgrupados.pendientes.length > 0">
                            <h3 class="font-semibold mb-3 flex items-center gap-2">
                                <AlertCircle class="h-5 w-5 text-yellow-600" />
                                Pendientes ({{ entregablesAgrupados.pendientes.length }})
                            </h3>
                            <div class="space-y-2">
                                <div v-for="entregable in entregablesAgrupados.pendientes" :key="entregable.id"
                                    class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 space-y-2">
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    @click="toggleEntregable(entregable.id)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="p-0 h-auto"
                                                >
                                                    <ChevronRight v-if="!expandedEntregables[entregable.id]" class="h-4 w-4" />
                                                    <ChevronDown v-else class="h-4 w-4" />
                                                </Button>
                                                <h4 class="font-medium">{{ entregable.nombre }}</h4>
                                                <Badge :class="getPrioridadColor(entregable.prioridad)" variant="outline">
                                                    {{ entregable.prioridad }}
                                                </Badge>
                                            </div>

                                            <div v-if="expandedEntregables[entregable.id]" class="ml-6 space-y-3">
                                                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
                                                    {{ entregable.descripcion }}
                                                </p>
                                                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                                    <span class="flex items-center gap-1">
                                                        <Calendar class="h-3 w-3" />
                                                        Vence: {{ formatDate(entregable.fecha_fin) }}
                                                    </span>
                                                    <span v-if="entregable.responsable" class="flex items-center gap-1">
                                                        <User class="h-3 w-3" />
                                                        {{ entregable.responsable.name }}
                                                    </span>
                                                </div>

                                                <!-- Área para completar -->
                                                <div v-if="canComplete" class="border-t pt-3 space-y-2">
                                                    <Textarea
                                                        v-model="notasCompletado[entregable.id]"
                                                        placeholder="Notas al completar (opcional)..."
                                                        class="text-sm"
                                                        rows="2"
                                                    />
                                                    <div class="flex gap-2">
                                                        <Button
                                                            @click="completarEntregable(entregable)"
                                                            :disabled="procesando[entregable.id]"
                                                            size="sm"
                                                            class="gap-2"
                                                        >
                                                            <CheckCircle class="h-4 w-4" />
                                                            Marcar como Completado
                                                        </Button>
                                                        <Button
                                                            v-if="canUpdateProgress"
                                                            @click="actualizarEstadoEntregable(entregable, 'en_progreso')"
                                                            variant="outline"
                                                            size="sm"
                                                            class="gap-2"
                                                        >
                                                            <Clock class="h-4 w-4" />
                                                            En Progreso
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- En Progreso -->
                        <div v-if="entregablesAgrupados.en_progreso.length > 0">
                            <h3 class="font-semibold mb-3 flex items-center gap-2">
                                <Clock class="h-5 w-5 text-blue-600" />
                                En Progreso ({{ entregablesAgrupados.en_progreso.length }})
                            </h3>
                            <div class="space-y-2">
                                <div v-for="entregable in entregablesAgrupados.en_progreso" :key="entregable.id"
                                    class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 space-y-2">
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    @click="toggleEntregable(entregable.id)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="p-0 h-auto"
                                                >
                                                    <ChevronRight v-if="!expandedEntregables[entregable.id]" class="h-4 w-4" />
                                                    <ChevronDown v-else class="h-4 w-4" />
                                                </Button>
                                                <h4 class="font-medium">{{ entregable.nombre }}</h4>
                                                <Badge :class="getPrioridadColor(entregable.prioridad)" variant="outline">
                                                    {{ entregable.prioridad }}
                                                </Badge>
                                                <Badge class="bg-blue-100 text-blue-800">
                                                    En Progreso
                                                </Badge>
                                            </div>

                                            <div v-if="expandedEntregables[entregable.id]" class="ml-6 space-y-3">
                                                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
                                                    {{ entregable.descripcion }}
                                                </p>
                                                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                                    <span class="flex items-center gap-1">
                                                        <Calendar class="h-3 w-3" />
                                                        Vence: {{ formatDate(entregable.fecha_fin) }}
                                                    </span>
                                                    <span v-if="entregable.responsable" class="flex items-center gap-1">
                                                        <User class="h-3 w-3" />
                                                        {{ entregable.responsable.name }}
                                                    </span>
                                                </div>

                                                <!-- Área para completar -->
                                                <div v-if="canComplete" class="border-t pt-3 space-y-2">
                                                    <Textarea
                                                        v-model="notasCompletado[entregable.id]"
                                                        placeholder="Notas al completar (opcional)..."
                                                        class="text-sm"
                                                        rows="2"
                                                    />
                                                    <Button
                                                        @click="completarEntregable(entregable)"
                                                        :disabled="procesando[entregable.id]"
                                                        size="sm"
                                                        class="gap-2"
                                                    >
                                                        <CheckCircle class="h-4 w-4" />
                                                        Marcar como Completado
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Completados -->
                        <div v-if="entregablesAgrupados.completados.length > 0">
                            <h3 class="font-semibold mb-3 flex items-center gap-2">
                                <CheckCircle class="h-5 w-5 text-green-600" />
                                Completados ({{ entregablesAgrupados.completados.length }})
                            </h3>
                            <div class="space-y-2">
                                <div v-for="entregable in entregablesAgrupados.completados" :key="entregable.id"
                                    class="border rounded-lg p-4 bg-green-50/50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 space-y-2">
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    @click="toggleEntregable(entregable.id)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="p-0 h-auto"
                                                >
                                                    <ChevronRight v-if="!expandedEntregables[entregable.id]" class="h-4 w-4" />
                                                    <ChevronDown v-else class="h-4 w-4" />
                                                </Button>
                                                <h4 class="font-medium line-through text-muted-foreground">{{ entregable.nombre }}</h4>
                                                <Badge class="bg-green-100 text-green-800">
                                                    Completado
                                                </Badge>
                                            </div>

                                            <div v-if="expandedEntregables[entregable.id]" class="ml-6 space-y-2 text-sm text-muted-foreground">
                                                <p v-if="entregable.descripcion">{{ entregable.descripcion }}</p>
                                                <div class="flex items-center gap-4">
                                                    <span class="flex items-center gap-1">
                                                        <CheckCircle class="h-3 w-3" />
                                                        Completado: {{ formatDateTime(entregable.completado_at) }}
                                                    </span>
                                                    <span v-if="entregable.completado_por_usuario" class="flex items-center gap-1">
                                                        <User class="h-3 w-3" />
                                                        Por: {{ entregable.completado_por_usuario.name }}
                                                    </span>
                                                </div>
                                                <p v-if="entregable.notas_completado" class="italic">
                                                    Notas: {{ entregable.notas_completado }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje si no hay entregables -->
                        <div v-if="!hito.entregables || hito.entregables.length === 0" class="text-center py-8">
                            <FileText class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <p class="text-muted-foreground">No hay entregables asignados a este hito</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </UserLayout>
</template>