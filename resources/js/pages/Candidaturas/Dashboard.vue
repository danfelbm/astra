<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import HistorialCandidatura from '@/components/forms/HistorialCandidatura.vue';
import { type BreadcrumbItemType, type Candidatura } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Clock, Edit, Plus, User, AlertCircle, XCircle, MessageSquare, History, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Configuracion {
    disponible: boolean;
    resumen: string;
    version: number;
}

interface Props {
    candidatura: Candidatura | null;
    configuracion: Configuracion;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mi Candidatura', href: '#' },
];

// Estado local
const mostrarHistorial = ref(false);

// Computadas
const hasCandidatura = computed(() => !!props.candidatura);

const statusIcon = computed(() => {
    if (!props.candidatura) return Plus;
    
    switch (props.candidatura.estado) {
        case 'aprobado':
            return CheckCircle;
        case 'rechazado':
            return XCircle;
        case 'pendiente':
            return Clock;
        case 'borrador':
            return Edit;
        default:
            return AlertCircle;
    }
});

const statusColor = computed(() => {
    if (!props.candidatura) return 'text-blue-600';
    
    switch (props.candidatura.estado) {
        case 'aprobado':
            return 'text-green-600';
        case 'rechazado':
            return 'text-red-600';
        case 'pendiente':
            return 'text-blue-600';
        case 'borrador':
            return 'text-yellow-600';
        default:
            return 'text-gray-600';
    }
});

const nextAction = computed(() => {
    if (!props.candidatura) {
        return {
            text: 'Crear Perfil de Candidatura',
            href: '/candidaturas/create',
            icon: Plus,
            description: 'Completa tu perfil para poder postularte a convocatorias'
        };
    }

    if (props.candidatura.puede_editar) {
        let text = 'Editar Candidatura';
        let description = 'Completa o actualiza la información de tu candidatura';
        
        if (props.candidatura.estado === 'rechazado') {
            description = 'Corrige los aspectos señalados y reenvía tu candidatura';
        } else if (props.candidatura.estado === 'aprobado') {
            text = 'Editar Campos Permitidos';
            description = 'Solo puedes editar campos marcados como editables. Los cambios requerirán nueva aprobación.';
        } else if (!props.candidatura.tiene_datos) {
            text = 'Completar Candidatura';
        }
        
        return {
            text,
            href: `/candidaturas/${props.candidatura.id}/edit`,
            icon: Edit,
            description
        };
    }

    return null;
});

// Función para formatear fecha
const formatearFecha = (fecha: string | null | undefined) => {
    if (!fecha) {
        return 'Fecha no disponible';
    }
    
    try {
        // Intentar parsear la fecha
        const dateObj = new Date(fecha);
        
        // Verificar si la fecha es válida
        if (isNaN(dateObj.getTime())) {
            // Si la fecha viene en formato dd/mm/yyyy HH:mm, intentar parsearlo
            const parts = fecha.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})/);
            if (parts) {
                const [, day, month, year, hour, minute] = parts;
                const parsedDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day), parseInt(hour), parseInt(minute));
                if (!isNaN(parsedDate.getTime())) {
                    return parsedDate.toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }
            return fecha; // Devolver la fecha tal cual si no se puede parsear
        }
        
        return dateObj.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Error al formatear fecha:', error);
        return fecha || 'Fecha no disponible';
    }
};
</script>

<template>
    <Head title="Mi Candidatura" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Mi Candidatura</h1>
                    <p class="text-muted-foreground">
                        Gestiona tu perfil de candidatura para participar en convocatorias
                    </p>
                </div>
            </div>

            <!-- Estado Actual -->
            <Card class="border-2">
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex items-center gap-4 flex-1">
                            <div :class="[statusColor, 'p-3 rounded-full bg-opacity-10 flex-shrink-0']">
                                <component :is="statusIcon" class="h-8 w-8" />
                            </div>
                            
                            <div class="flex-1">
                                <h3 class="text-xl font-semibold mb-1">
                                    {{ hasCandidatura ? candidatura!.estado_label : 'Sin Candidatura' }}
                                </h3>
                                
                                <p class="text-muted-foreground mb-2">
                                    {{ hasCandidatura 
                                        ? `Versión ${candidatura!.version} - Actualizada el ${formatearFecha(candidatura!.updated_at)}`
                                        : 'Aún no has creado tu perfil de candidatura'
                                    }}
                                </p>

                                <div v-if="hasCandidatura" class="flex items-center gap-2 flex-wrap">
                                    <Badge :class="candidatura!.estado_color">
                                        {{ candidatura!.estado_label }}
                                    </Badge>
                                    
                                    <Badge v-if="!candidatura!.tiene_datos" variant="outline" class="bg-yellow-50 text-yellow-700">
                                        Incompleto
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div v-if="nextAction" class="w-full sm:w-auto">
                            <Link :href="nextAction.href" class="block">
                                <Button class="w-full sm:w-auto">
                                    <component :is="nextAction.icon" class="mr-2 h-4 w-4" />
                                    {{ nextAction.text }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Comentarios de la Comisión -->
            <Card v-if="candidatura?.comentarios_admin" class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                <CardHeader>
                    <CardTitle class="text-blue-800 dark:text-blue-200 flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Comentarios de la comisión
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-blue-700 dark:text-blue-300">{{ candidatura.comentarios_admin }}</p>
                </CardContent>
            </Card>

            <!-- Historial de Cambios (Colapsable) -->
            <div v-if="hasCandidatura && mostrarHistorial">
                <HistorialCandidatura 
                    :candidatura-id="candidatura!.id"
                    :version-actual="candidatura!.version"
                />
            </div>

            <!-- Información de la Candidatura (Más compacta y debajo del historial) -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Estados de Candidatura (más compacto) -->
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base flex items-center gap-2">
                            <User class="h-4 w-4" />
                            Estados
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Edit class="h-3 w-3 text-yellow-600" />
                            <p class="text-xs"><span class="font-medium">Borrador:</span> Editable</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <Clock class="h-3 w-3 text-blue-600" />
                            <p class="text-xs"><span class="font-medium">Pendiente:</span> En revisión</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <CheckCircle class="h-3 w-3 text-green-600" />
                            <p class="text-xs"><span class="font-medium">Aprobado:</span> Listo</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <XCircle class="h-3 w-3 text-red-600" />
                            <p class="text-xs"><span class="font-medium">Rechazado:</span> Corregir</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuración actual (más compacto) -->
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base flex items-center gap-2">
                            <AlertCircle class="h-4 w-4" />
                            Configuración
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div>
                            <p class="text-xs font-medium">Campos:</p>
                            <p class="text-xs text-muted-foreground">{{ configuracion.resumen }}</p>
                        </div>
                        
                        <div>
                            <p class="text-xs font-medium">Versión:</p>
                            <p class="text-xs text-muted-foreground">v{{ configuracion.version }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Acciones (con botón de historial) -->
                <Card v-if="hasCandidatura" class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Acciones</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <!-- Botón de Historial de Cambios -->
                        <Button 
                            @click="mostrarHistorial = !mostrarHistorial" 
                            variant="outline" 
                            class="w-full"
                        >
                            <History class="mr-2 h-4 w-4" />
                            {{ mostrarHistorial ? 'Ocultar' : 'Mostrar' }} Historial
                            <component 
                                :is="mostrarHistorial ? ChevronUp : ChevronDown" 
                                class="ml-auto h-4 w-4"
                            />
                        </Button>
                        
                        <div class="flex gap-1">
                            <Link :href="`/candidaturas/${candidatura!.id}`">
                                <Button variant="outline" size="sm">
                                    <User class="mr-1 h-3 w-3" />
                                    Ver
                                </Button>
                            </Link>
                            
                            <Link v-if="candidatura!.puede_editar" :href="`/candidaturas/${candidatura!.id}/edit`">
                                <Button variant="outline" size="sm">
                                    <Edit class="mr-1 h-3 w-3" />
                                    Editar
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>