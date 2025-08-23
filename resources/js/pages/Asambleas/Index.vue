<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import GlowEffect from '@/components/ui/GlowEffect.vue';
import { type BreadcrumbItemType } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, Clock, MapPin, Users, Eye, CheckCircle, UserCheck } from 'lucide-vue-next';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';

interface Asamblea {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'ordinaria' | 'extraordinaria';
    tipo_label: string;
    estado: 'programada' | 'en_curso' | 'finalizada' | 'cancelada';
    estado_label: string;
    estado_color: string;
    fecha_inicio: string;
    fecha_fin: string;
    lugar?: string;
    ubicacion_completa: string;
    duracion: string;
    tiempo_restante: string;
    rango_fechas: string;
    es_participante: boolean;
    mi_participacion?: {
        tipo: 'asistente' | 'moderador' | 'secretario';
        asistio: boolean;
        hora_registro?: string;
    };
}

interface PaginatedData {
    data: Asamblea[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    asambleas: PaginatedData;
    filters: {
        estado?: string;
        tipo?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Asambleas', href: '/asambleas' },
];

// Helper para obtener route
const { route } = window as any;

// Formatear fecha
const formatearFecha = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'PPP', { locale: es });
};

// Formatear hora
const formatearHora = (fecha: string) => {
    if (!fecha) return '';
    return format(new Date(fecha), 'p', { locale: es });
};

// Obtener badge para mi participación
const getParticipacionBadge = (tipo: string) => {
    switch (tipo) {
        case 'moderador':
            return { class: 'bg-purple-100 text-purple-800', text: 'Moderador' };
        case 'secretario':
            return { class: 'bg-blue-100 text-blue-800', text: 'Secretario' };
        default:
            return { class: 'bg-gray-100 text-gray-800', text: 'Asistente' };
    }
};

// Obtener badge de tipo
const getTipoBadge = (tipo: string) => {
    return tipo === 'ordinaria' ?
        { class: 'bg-blue-100 text-blue-800', text: 'Ordinaria' } :
        { class: 'bg-purple-100 text-purple-800', text: 'Extraordinaria' };
};
</script>

<template>
    <Head title="Asambleas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-2" style="overflow: visible; contain: layout;">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Asambleas</h1>
                <p class="text-muted-foreground">
                    Consulta las asambleas donde participas y las disponibles en tu territorio
                </p>
            </div>


            <!-- Lista unificada de asambleas -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 p-6" style="overflow: visible;">
                <Link 
                    v-for="asamblea in asambleas.data" 
                    :key="asamblea.id" 
                    :href="route('asambleas.show', asamblea.id)"
                    class="block transition-transform hover:scale-[1.02]"
                >
                    <!-- Contenedor superior que maneja glow y background -->
                    <div 
                        :class="[
                            'relative rounded-xl',
                            asamblea.es_participante ? 'bg-white dark:bg-gray-900' : ''
                        ]"
                    >
                        <!-- Glow Effect fuera del card, en el contenedor superior -->
                        <GlowEffect v-if="asamblea.es_participante" />
                        
                        <Card 
                            :class="[
                                'h-full hover:shadow-lg transition-shadow cursor-pointer relative z-10',
                                asamblea.es_participante ? 'border-0 bg-white dark:bg-gray-900 rounded-xl' : ''
                            ]"
                        >
                        <CardHeader>
                            <div class="space-y-1">
                                <CardTitle class="text-lg">{{ asamblea.nombre }}</CardTitle>
                                <div class="flex gap-2 flex-wrap">
                                    <!-- Badge de participación/territorio -->
                                    <Badge v-if="asamblea.es_participante" class="bg-blue-100 text-blue-800">
                                        <UserCheck class="mr-1 h-3 w-3" />
                                        Mi Asamblea
                                    </Badge>
                                    <Badge v-else class="bg-gray-100 text-gray-800">
                                        <MapPin class="mr-1 h-3 w-3" />
                                        Territorio
                                    </Badge>
                                    
                                    <!-- Badges existentes -->
                                    <Badge :class="asamblea.estado_color">
                                        {{ asamblea.estado_label }}
                                    </Badge>
                                    <Badge :class="getTipoBadge(asamblea.tipo).class">
                                        {{ getTipoBadge(asamblea.tipo).text }}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p v-if="asamblea.descripcion" class="text-sm text-muted-foreground line-clamp-2">
                                {{ asamblea.descripcion }}
                            </p>

                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatearFecha(asamblea.fecha_inicio) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ formatearHora(asamblea.fecha_inicio) }} - {{ formatearHora(asamblea.fecha_fin) }}</span>
                                </div>
                                <div v-if="asamblea.lugar || asamblea.ubicacion_completa" class="flex items-center gap-2">
                                    <MapPin class="h-4 w-4 text-muted-foreground" />
                                    <span class="line-clamp-1">{{ asamblea.lugar || asamblea.ubicacion_completa }}</span>
                                </div>
                            </div>

                            <!-- Información de participación (solo si es participante) -->
                            <div v-if="asamblea.mi_participacion" class="pt-2 border-t">
                                <div class="flex items-center justify-between">
                                    <Badge :class="getParticipacionBadge(asamblea.mi_participacion.tipo).class">
                                        {{ getParticipacionBadge(asamblea.mi_participacion.tipo).text }}
                                    </Badge>
                                    <Badge v-if="asamblea.mi_participacion.asistio" class="bg-green-100 text-green-800">
                                        <CheckCircle class="mr-1 h-3 w-3" />
                                        Asististe
                                    </Badge>
                                </div>
                            </div>

                            <div class="pt-2 text-sm font-medium">
                                {{ asamblea.tiempo_restante }}
                            </div>

                            <!-- Botón de acción -->
                            <div class="pt-3 border-t">
                                <div class="inline-flex items-center gap-2 text-sm font-medium text-primary">
                                    {{ asamblea.es_participante ? 'Ver detalles' : 'Ver información' }}
                                    <Eye class="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    </div> <!-- Cierre del contenedor superior -->
                </Link>

                <!-- Estado vacío -->
                <div v-if="asambleas.data.length === 0" class="col-span-full">
                    <Card>
                        <CardContent class="flex flex-col items-center justify-center py-12">
                            <Users class="h-12 w-12 text-muted-foreground mb-4" />
                            <p class="text-lg font-medium text-muted-foreground">
                                No hay asambleas disponibles
                            </p>
                            <p class="text-sm text-muted-foreground mt-1">
                                No tienes asambleas asignadas ni hay asambleas en tu territorio
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Paginación unificada -->
            <div v-if="asambleas.last_page > 1" class="mt-6 flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Mostrando {{ (asambleas.current_page - 1) * asambleas.per_page + 1 }} a 
                    {{ Math.min(asambleas.current_page * asambleas.per_page, asambleas.total) }} de 
                    {{ asambleas.total }} asambleas
                </p>
                <div class="flex gap-2">
                    <template v-for="link in asambleas.links" :key="link.label">
                        <Link 
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1 text-sm border rounded',
                                link.active 
                                    ? 'bg-primary text-primary-foreground' 
                                    : 'bg-background hover:bg-accent'
                            ]"
                            v-html="link.label"
                        />
                        <span 
                            v-else
                            :class="[
                                'px-3 py-1 text-sm border rounded',
                                'bg-muted text-muted-foreground cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>