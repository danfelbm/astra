<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { 
    Calendar, 
    Clock, 
    Eye, 
    FileText, 
    MapPin, 
    Megaphone, 
    Users, 
    CheckCircle, 
    XCircle, 
    AlertCircle, 
    Edit,
    Send
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Postulacion {
    id: number;
    convocatoria: {
        id: number;
        nombre: string;
        cargo: string;
        periodo: string;
        estado_temporal: string;
    };
    estado: string;
    estado_label: string;
    estado_color: string;
    fecha_postulacion: string | null;
    tiene_candidatura_vinculada: boolean;
    comentarios_admin: string | null;
    puede_editar: boolean;
    created_at: string;
}

interface ConvocatoriaDisponible {
    id: number;
    nombre: string;
    descripcion: string;
    cargo: string;
    periodo: string;
    estado_temporal: string;
    estado_temporal_label: string;
    estado_temporal_color: string;
    rango_fechas: string;
    ubicacion: string;
    numero_postulaciones: number;
}

interface Props {
    postulaciones: Postulacion[];
    convocatorias_disponibles: ConvocatoriaDisponible[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Postulaciones',
        href: '/postulaciones',
    },
];

const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'borrador':
            return Edit;
        case 'enviada':
            return Send;
        case 'en_revision':
            return Clock;
        case 'aceptada':
            return CheckCircle;
        case 'rechazada':
            return XCircle;
        default:
            return FileText;
    }
};

const getEstadoTemporalIcon = (estadoTemporal: string) => {
    switch (estadoTemporal) {
        case 'abierta':
            return CheckCircle;
        case 'futura':
            return Calendar;
        case 'cerrada':
            return XCircle;
        default:
            return AlertCircle;
    }
};

const navegarAConvocatoria = (convocatoriaId: number) => {
    router.get(`/convocatorias/${convocatoriaId}`);
};
</script>

<template>
    <Head title="Mis Postulaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Postulaciones</h1>
            <p class="text-muted-foreground">
                Gestiona tus postulaciones a convocatorias electorales y descubre nuevas oportunidades.
            </p>
        </div>

        <!-- Tabs para separar postulaciones y convocatorias disponibles -->
        <Tabs default-value="convocatorias-disponibles" class="space-y-4">
            <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="convocatorias-disponibles" class="flex items-center gap-2">
                    <Megaphone class="h-4 w-4" />
                    Convocatorias Disponibles
                    <Badge v-if="convocatorias_disponibles.length > 0" variant="secondary" class="ml-1">
                        {{ convocatorias_disponibles.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="mis-postulaciones" class="flex items-center gap-2">
                    <FileText class="h-4 w-4" />
                    Mis Postulaciones
                    <Badge v-if="postulaciones.length > 0" variant="secondary" class="ml-1">
                        {{ postulaciones.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <!-- Tab: Mis Postulaciones -->
            <TabsContent value="mis-postulaciones" class="space-y-4">
                <div v-if="postulaciones.length === 0" class="text-center py-8">
                    <FileText class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No tienes postulaciones</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Explora las convocatorias disponibles para enviar tu primera postulación.
                    </p>
                </div>

                <div v-else class="grid gap-4">
                    <Card v-for="postulacion in postulaciones" :key="postulacion.id" class="hover:shadow-md transition-shadow">
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <CardTitle class="text-lg">{{ postulacion.convocatoria.nombre }}</CardTitle>
                                    <CardDescription class="flex items-center gap-4 text-sm">
                                        <span class="flex items-center gap-1">
                                            <Users class="h-4 w-4" />
                                            {{ postulacion.convocatoria.cargo }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Calendar class="h-4 w-4" />
                                            {{ postulacion.convocatoria.periodo }}
                                        </span>
                                    </CardDescription>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :class="postulacion.estado_color">
                                        <component :is="getEstadoIcon(postulacion.estado)" class="mr-1 h-3 w-3" />
                                        {{ postulacion.estado_label }}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Fecha postulación:</span>
                                    <p class="font-medium">{{ postulacion.fecha_postulacion || 'Borrador' }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Perfil vinculado:</span>
                                    <p class="font-medium">
                                        {{ postulacion.tiene_candidatura_vinculada ? 'Sí' : 'No' }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="postulacion.comentarios_admin" class="p-3 bg-muted rounded-lg">
                                <p class="text-sm font-medium text-muted-foreground mb-1">Comentarios de la comisión:</p>
                                <p class="text-sm">{{ postulacion.comentarios_admin }}</p>
                            </div>

                            <div class="flex justify-between items-center pt-2">
                                <p class="text-xs text-muted-foreground">
                                    Creada el {{ postulacion.created_at }}
                                </p>
                                <div class="flex gap-2">
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        @click="navegarAConvocatoria(postulacion.convocatoria.id)"
                                    >
                                        <Eye class="mr-2 h-4 w-4" />
                                        Ver detalle
                                    </Button>
                                    <Button 
                                        v-if="postulacion.puede_editar"
                                        size="sm"
                                        @click="navegarAConvocatoria(postulacion.convocatoria.id)"
                                    >
                                        <Edit class="mr-2 h-4 w-4" />
                                        Editar
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <!-- Tab: Convocatorias Disponibles -->
            <TabsContent value="convocatorias-disponibles" class="space-y-4">
                <div v-if="convocatorias_disponibles.length === 0" class="text-center py-8">
                    <Megaphone class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay convocatorias disponibles</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        No tienes convocatorias disponibles para postularte en este momento.
                    </p>
                </div>

                <div v-else class="grid gap-4">
                    <Card v-for="convocatoria in convocatorias_disponibles" :key="convocatoria.id" class="hover:shadow-md transition-shadow">
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <CardTitle class="text-lg">{{ convocatoria.nombre }}</CardTitle>
                                    <CardDescription>{{ convocatoria.descripcion }}</CardDescription>
                                </div>
                                <Badge :class="convocatoria.estado_temporal_color">
                                    <component :is="getEstadoTemporalIcon(convocatoria.estado_temporal)" class="mr-1 h-3 w-3" />
                                    {{ convocatoria.estado_temporal_label }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-muted-foreground">Cargo:</span>
                                    <p class="font-medium">{{ convocatoria.cargo }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Periodo:</span>
                                    <p class="font-medium">{{ convocatoria.periodo }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Fechas:</span>
                                    <p class="font-medium text-xs">{{ convocatoria.rango_fechas }}</p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground">Ubicación:</span>
                                    <p class="font-medium">{{ convocatoria.ubicacion }}</p>
                                </div>
                            </div>

                            <Separator />

                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                    <Users class="h-4 w-4" />
                                    {{ convocatoria.numero_postulaciones }} postulaciones
                                </div>
                                <Button 
                                    @click="navegarAConvocatoria(convocatoria.id)"
                                    :disabled="convocatoria.estado_temporal === 'cerrada'"
                                >
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ convocatoria.estado_temporal === 'futura' ? 'Ver convocatoria' : 'Postularme' }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>
        </Tabs>
        </div>
        </div>
    </AppLayout>
</template>