<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
    BarChart3, 
    Download, 
    FileText, 
    Users, 
    TrendingUp,
    CheckCircle,
    XCircle,
    Clock,
    Edit,
    Send,
    User
} from 'lucide-vue-next';

interface Estadisticas {
    total: number;
    por_estado: {
        borradores: number;
        enviadas: number;
        en_revision: number;
        aceptadas: number;
        rechazadas: number;
    };
    con_candidatura: number;
}

interface TopConvocatoria {
    nombre: string;
    total_postulaciones: number;
}

interface Props {
    estadisticas: Estadisticas;
    top_convocatorias: TopConvocatoria[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/dashboard',
    },
    {
        title: 'Postulaciones',
        href: '/admin/postulaciones',
    },
    {
        title: 'Reportes',
        href: '/admin/postulaciones-reportes',
    },
];

const estadisticasCards = [
    {
        title: 'Total Postulaciones',
        value: props.estadisticas.total,
        icon: FileText,
        color: 'text-blue-600',
        bgColor: 'bg-blue-100',
    },
    {
        title: 'Borradores',
        value: props.estadisticas.por_estado.borradores,
        icon: Edit,
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-100',
    },
    {
        title: 'Enviadas',
        value: props.estadisticas.por_estado.enviadas,
        icon: Send,
        color: 'text-blue-600',
        bgColor: 'bg-blue-100',
    },
    {
        title: 'En Revisión',
        value: props.estadisticas.por_estado.en_revision,
        icon: Clock,
        color: 'text-purple-600',
        bgColor: 'bg-purple-100',
    },
    {
        title: 'Aceptadas',
        value: props.estadisticas.por_estado.aceptadas,
        icon: CheckCircle,
        color: 'text-green-600',
        bgColor: 'bg-green-100',
    },
    {
        title: 'Rechazadas',
        value: props.estadisticas.por_estado.rechazadas,
        icon: XCircle,
        color: 'text-red-600',
        bgColor: 'bg-red-100',
    },
];

const calcularPorcentaje = (valor: number, total: number) => {
    return total > 0 ? Math.round((valor / total) * 100) : 0;
};

const exportarDatos = () => {
    window.open('/admin/postulaciones-exportar', '_blank');
};
</script>

<template>
    <Head title="Reportes de Postulaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Reportes de Postulaciones</h1>
                <p class="text-muted-foreground">
                    Análisis y estadísticas de las postulaciones a convocatorias electorales.
                </p>
            </div>
            <Button @click="exportarDatos">
                <Download class="mr-2 h-4 w-4" />
                Exportar Datos
            </Button>
        </div>

        <!-- Estadísticas principales -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card v-for="stat in estadisticasCards" :key="stat.title">
                <CardContent class="p-6">
                    <div class="flex items-center space-x-2">
                        <div :class="[stat.bgColor, 'p-2 rounded-full']">
                            <component :is="stat.icon" :class="[stat.color, 'h-5 w-5']" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ stat.title }}</p>
                            <p class="text-2xl font-bold">{{ stat.value }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Análisis por estado -->
        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="h-5 w-5" />
                        Distribución por Estado
                    </CardTitle>
                    <CardDescription>
                        Porcentaje de postulaciones en cada estado
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Edit class="h-4 w-4 text-yellow-600" />
                                <span class="text-sm">Borradores</span>
                            </div>
                            <span class="text-sm font-medium">
                                {{ estadisticas.por_estado.borradores }} 
                                ({{ calcularPorcentaje(estadisticas.por_estado.borradores, estadisticas.total) }}%)
                            </span>
                        </div>
                        <Progress 
                            :value="calcularPorcentaje(estadisticas.por_estado.borradores, estadisticas.total)" 
                            class="h-2"
                        />
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Send class="h-4 w-4 text-blue-600" />
                                <span class="text-sm">Enviadas</span>
                            </div>
                            <span class="text-sm font-medium">
                                {{ estadisticas.por_estado.enviadas }} 
                                ({{ calcularPorcentaje(estadisticas.por_estado.enviadas, estadisticas.total) }}%)
                            </span>
                        </div>
                        <Progress 
                            :value="calcularPorcentaje(estadisticas.por_estado.enviadas, estadisticas.total)" 
                            class="h-2"
                        />
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Clock class="h-4 w-4 text-purple-600" />
                                <span class="text-sm">En Revisión</span>
                            </div>
                            <span class="text-sm font-medium">
                                {{ estadisticas.por_estado.en_revision }} 
                                ({{ calcularPorcentaje(estadisticas.por_estado.en_revision, estadisticas.total) }}%)
                            </span>
                        </div>
                        <Progress 
                            :value="calcularPorcentaje(estadisticas.por_estado.en_revision, estadisticas.total)" 
                            class="h-2"
                        />
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-green-600" />
                                <span class="text-sm">Aceptadas</span>
                            </div>
                            <span class="text-sm font-medium">
                                {{ estadisticas.por_estado.aceptadas }} 
                                ({{ calcularPorcentaje(estadisticas.por_estado.aceptadas, estadisticas.total) }}%)
                            </span>
                        </div>
                        <Progress 
                            :value="calcularPorcentaje(estadisticas.por_estado.aceptadas, estadisticas.total)" 
                            class="h-2"
                        />
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <XCircle class="h-4 w-4 text-red-600" />
                                <span class="text-sm">Rechazadas</span>
                            </div>
                            <span class="text-sm font-medium">
                                {{ estadisticas.por_estado.rechazadas }} 
                                ({{ calcularPorcentaje(estadisticas.por_estado.rechazadas, estadisticas.total) }}%)
                            </span>
                        </div>
                        <Progress 
                            :value="calcularPorcentaje(estadisticas.por_estado.rechazadas, estadisticas.total)" 
                            class="h-2"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Análisis de Perfiles
                    </CardTitle>
                    <CardDescription>
                        Postulaciones con y sin perfil de candidatura vinculado
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">
                            {{ calcularPorcentaje(estadisticas.con_candidatura, estadisticas.total) }}%
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Con perfil de candidatura
                        </p>
                    </div>

                    <Separator />

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Con perfil vinculado</span>
                            <Badge variant="secondary" class="bg-green-100 text-green-800">
                                {{ estadisticas.con_candidatura }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Sin perfil vinculado</span>
                            <Badge variant="secondary">
                                {{ estadisticas.total - estadisticas.con_candidatura }}
                            </Badge>
                        </div>
                    </div>

                    <div class="p-3 bg-muted rounded-lg">
                        <p class="text-xs text-muted-foreground">
                            Las postulaciones con perfil de candidatura vinculado proporcionan 
                            información más completa para el proceso de evaluación.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Top convocatorias -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrendingUp class="h-5 w-5" />
                    Top Convocatorias por Número de Postulaciones
                </CardTitle>
                <CardDescription>
                    Convocatorias con mayor número de postulaciones recibidas
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="top_convocatorias.length === 0" class="text-center py-8 text-muted-foreground">
                    <BarChart3 class="mx-auto h-12 w-12 mb-2" />
                    <p>No hay datos de convocatorias disponibles.</p>
                </div>
                <div v-else class="space-y-4">
                    <div 
                        v-for="(convocatoria, index) in top_convocatorias" 
                        :key="convocatoria.nombre"
                        class="flex items-center justify-between p-3 border rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold">
                                {{ index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium">{{ convocatoria.nombre }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ calcularPorcentaje(convocatoria.total_postulaciones, estadisticas.total) }}% 
                                    del total de postulaciones
                                </p>
                            </div>
                        </div>
                        <Badge variant="secondary" class="text-base px-3 py-1">
                            {{ convocatoria.total_postulaciones }}
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Resumen general -->
        <Card>
            <CardHeader>
                <CardTitle>Resumen del Sistema</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ estadisticas.total }}
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Total de postulaciones registradas
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ estadisticas.por_estado.aceptadas }}
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Postulaciones aceptadas
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-amber-600">
                            {{ estadisticas.por_estado.enviadas + estadisticas.por_estado.en_revision }}
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Pendientes de revisión
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
        </div>
        </div>
    </AppLayout>
</template>