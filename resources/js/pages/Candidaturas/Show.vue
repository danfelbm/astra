<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItemType } from '@/types';
import { type FormField } from '@/types/forms';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, User, Clock, MessageSquare, History, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import HistorialCandidatura from '@/components/forms/HistorialCandidatura.vue';

interface Candidatura {
    id: number;
    formulario_data: Record<string, any>;
    estado: string;
    estado_label: string;
    estado_color: string;
    version: number;
    comentarios_admin?: string;
    fecha_aprobacion?: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    candidatura: Candidatura;
    configuracion_campos: FormField[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mi Candidatura', href: '/candidaturas' },
    { title: 'Ver Candidatura', href: '#' },
];

// Estado local
const mostrarHistorial = ref(false);

// Computadas
const puedeEditar = computed(() => {
    return props.candidatura.estado === 'borrador' || props.candidatura.estado === 'rechazado';
});

const tieneComentarios = computed(() => {
    return !!props.candidatura.comentarios_admin;
});

// Método para toggle del historial
const toggleHistorial = () => {
    mostrarHistorial.value = !mostrarHistorial.value;
};

// Función para obtener valor formateado de un campo
const getFieldValue = (campo: FormField, value: any) => {
    if (!value) return 'No especificado';
    
    // Manejar diferentes tipos de campos basándose en el tipo conocido
    if (campo.type === 'checkbox') {
        return Array.isArray(value) ? value.join(', ') : value;
    }
    
    // Para otros tipos, intentar formatear basándose en el contenido
    // Si el valor parece una fecha ISO
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
        try {
            return new Date(value).toLocaleDateString('es-ES');
        } catch {
            return value;
        }
    }
    
    // Para arrays
    if (Array.isArray(value)) {
        return value.join(', ');
    }
    
    // Para números
    if (typeof value === 'number') {
        return value.toString();
    }
    
    // Default: retornar como string
    return String(value);
};

// Función para formatear fecha
const formatearFecha = (fecha: string) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
                        Vista detallada de tu perfil de candidatura
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="router.visit('/candidaturas')">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <Link v-if="puedeEditar" :href="`/candidaturas/${candidatura.id}/edit`">
                        <Button>
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Estado de la Candidatura -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <Badge :class="candidatura.estado_color" class="text-sm">
                                    {{ candidatura.estado_label }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">
                                    Versión {{ candidatura.version }}
                                </span>
                            </div>
                        </div>

                        <div class="text-right text-sm text-muted-foreground">
                            <p>Creada: {{ formatearFecha(candidatura.created_at) }}</p>
                            <p>Actualizada: {{ formatearFecha(candidatura.updated_at) }}</p>
                            <p v-if="candidatura.fecha_aprobacion">
                                Aprobada: {{ candidatura.fecha_aprobacion }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Comentarios de la Comisión -->
            <Card v-if="tieneComentarios" class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
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

            <!-- Datos de la Candidatura -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Información de Candidatura
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!candidatura.formulario_data || Object.keys(candidatura.formulario_data).length === 0" 
                         class="text-center py-8">
                        <Clock class="mx-auto h-12 w-12 text-yellow-500" />
                        <h3 class="mt-4 text-lg font-medium">Candidatura vacía</h3>
                        <p class="text-muted-foreground mb-4">Aún no has completado tu perfil de candidatura.</p>
                        <Link v-if="puedeEditar" :href="`/candidaturas/${candidatura.id}/edit`">
                            <Button>
                                <Edit class="mr-2 h-4 w-4" />
                                Completar Candidatura
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="space-y-6">
                        <div
                            v-for="campo in configuracion_campos"
                            :key="campo.id"
                            class="border-b pb-4 last:border-b-0"
                        >
                            <div class="grid gap-2 md:grid-cols-3">
                                <div class="md:col-span-1">
                                    <Label class="text-sm font-medium flex items-center gap-1">
                                        {{ campo.title }}
                                        <span v-if="campo.required" class="text-red-500">*</span>
                                    </Label>
                                    <p v-if="campo.description" class="text-xs text-muted-foreground mt-1">
                                        {{ campo.description }}
                                    </p>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <div class="min-h-[2rem] flex items-center">
                                        <p class="text-muted-foreground">
                                            {{ getFieldValue(campo, candidatura.formulario_data[campo.id]) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Historial de Cambios (Colapsable) -->
            <div v-show="mostrarHistorial" class="mb-4">
                <HistorialCandidatura 
                    :candidatura-id="candidatura.id" 
                    :version-actual="candidatura.version"
                    :is-admin="false"
                />
            </div>

            <!-- Información Adicional (Más pequeña y debajo del historial) -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Estado de la Candidatura (más compacto) -->
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Estado</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Badge :class="candidatura.estado_color" class="text-xs">
                                {{ candidatura.estado_label }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">v{{ candidatura.version }}</span>
                        </div>
                        
                        <div v-if="candidatura.estado === 'aprobado'" class="text-xs text-green-600 dark:text-green-400">
                            ✓ Lista para convocatorias
                        </div>
                        
                        <div v-else-if="candidatura.estado === 'rechazado'" class="text-xs text-orange-600 dark:text-orange-400">
                            Requiere corrección
                        </div>
                        
                        <div v-else class="text-xs text-blue-600 dark:text-blue-400">
                            {{ candidatura.formulario_data && Object.keys(candidatura.formulario_data).length > 0
                                ? 'En revisión'
                                : 'Pendiente de completar'
                            }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Acciones (con botón de historial) -->
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Acciones</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <!-- Botón de Historial de Cambios -->
                        <Button 
                            @click="toggleHistorial" 
                            variant="outline" 
                            class="w-full"
                        >
                            <History class="mr-2 h-4 w-4" />
                            {{ mostrarHistorial ? 'Ocultar' : 'Mostrar' }} Historial de Cambios
                            <component 
                                :is="mostrarHistorial ? ChevronUp : ChevronDown" 
                                class="ml-auto h-4 w-4"
                            />
                        </Button>
                        
                        <div v-if="puedeEditar">
                            <Link :href="`/candidaturas/${candidatura.id}/edit`">
                                <Button class="w-full">
                                    <Edit class="mr-2 h-4 w-4" />
                                    {{ candidatura.estado === 'rechazado' ? 'Corregir' : 'Editar' }}
                                </Button>
                            </Link>
                        </div>
                        
                        <div v-else-if="candidatura.estado === 'aprobado'" class="text-center">
                            <p class="text-xs text-green-600 dark:text-green-400 font-medium">
                                ✓ Lista para usar
                            </p>
                        </div>
                        
                        <div v-else class="text-center">
                            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                ⏳ En revisión
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuración actual (más compacto) -->
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Configuración actual</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="text-xs space-y-1">
                            <div>
                                <span class="text-muted-foreground">Creada:</span>
                                <p class="font-medium">{{ new Date(candidatura.created_at).toLocaleDateString('es-ES') }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Actualizada:</span>
                                <p class="font-medium">{{ new Date(candidatura.updated_at).toLocaleDateString('es-ES') }}</p>
                            </div>
                            <div v-if="candidatura.fecha_aprobacion">
                                <span class="text-muted-foreground">Aprobada:</span>
                                <p class="font-medium">{{ candidatura.fecha_aprobacion }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>