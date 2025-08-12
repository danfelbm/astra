<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DynamicFormBuilder from '@/components/forms/DynamicFormBuilder.vue';
import { type BreadcrumbItemType } from '@/types';
import { type FormField } from '@/types/forms';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Settings, Save, History, CheckCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface ConfiguracionActiva {
    id: number;
    campos: FormField[];
    version: number;
    resumen: string;
    fecha_creacion: string;
    created_by: {
        name: string;
        email: string;
    };
}

interface HistorialConfig {
    id: number;
    version: number;
    estado: string;
    color_estado: string;
    resumen: string;
    fecha_creacion: string;
    created_by: {
        name: string;
        email: string;
    };
}

interface Props {
    configuracion_activa: ConfiguracionActiva | null;
    historial: HistorialConfig[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Candidaturas', href: '/admin/candidaturas' },
    { title: 'Configuración', href: '#' },
];

// Estado para mostrar/ocultar el formulario de configuración
const showConfigForm = ref(false);

// Formulario para nueva configuración
const form = useForm({
    campos: props.configuracion_activa?.campos || [],
});

// Computadas
const hasActiveConfig = computed(() => !!props.configuracion_activa);

const formHasChanges = computed(() => {
    if (!props.configuracion_activa) return form.campos.length > 0;
    return JSON.stringify(form.campos) !== JSON.stringify(props.configuracion_activa.campos);
});

const canSave = computed(() => {
    return form.campos.length > 0 && formHasChanges.value;
});

// Métodos
const startNewConfig = () => {
    form.campos = props.configuracion_activa?.campos || [];
    showConfigForm.value = true;
};

const cancelConfig = () => {
    form.reset();
    form.campos = props.configuracion_activa?.campos || [];
    showConfigForm.value = false;
};

const saveConfig = () => {
    form.post('/admin/candidaturas/configuracion', {
        onSuccess: () => {
            showConfigForm.value = false;
        },
        onError: (errors) => {
            console.error('Error guardando configuración:', errors);
        }
    });
};

const activateConfig = (configId: number) => {
    if (confirm('¿Estás seguro de que quieres activar esta configuración? Se desactivará la configuración actual.')) {
        // Usar Inertia router para navegar
        window.location.href = `/admin/candidaturas/configuracion/${configId}/activar`;
    }
};
</script>

<template>
    <Head title="Configuración de Candidaturas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Configuración de Candidaturas</h1>
                    <p class="text-muted-foreground">
                        Define los campos que aparecerán en el formulario de candidaturas para usuarios
                    </p>
                </div>
                <Button variant="outline" @click="$inertia.visit('/admin/candidaturas')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Candidaturas
                </Button>
            </div>

            <!-- Configuración Activa -->
            <Card v-if="hasActiveConfig" class="border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400" />
                            <CardTitle class="text-green-800 dark:text-green-200">Configuración Activa</CardTitle>
                            <Badge variant="outline" class="bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                                Versión {{ configuracion_activa!.version }}
                            </Badge>
                        </div>
                        <Button @click="startNewConfig" :disabled="showConfigForm">
                            <Settings class="mr-2 h-4 w-4" />
                            Modificar Configuración
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-300">Resumen</p>
                            <p class="text-green-600 dark:text-green-400">{{ configuracion_activa!.resumen }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-300">Creada por</p>
                            <p class="text-green-600 dark:text-green-400">{{ configuracion_activa!.created_by.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-300">Fecha de creación</p>
                            <p class="text-green-600 dark:text-green-400">{{ configuracion_activa!.fecha_creacion }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No hay configuración activa -->
            <Card v-else class="border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-950/20">
                <CardContent class="p-6 text-center">
                    <Settings class="mx-auto h-12 w-12 text-yellow-600 dark:text-yellow-400 mb-4" />
                    <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">No hay configuración activa</h3>
                    <p class="text-yellow-700 dark:text-yellow-300 mb-4">
                        Los usuarios no pueden crear candidaturas hasta que configures los campos del formulario.
                    </p>
                    <Button @click="startNewConfig" :disabled="showConfigForm">
                        <Settings class="mr-2 h-4 w-4" />
                        Crear Primera Configuración
                    </Button>
                </CardContent>
            </Card>

            <!-- Formulario de Configuración -->
            <Card v-if="showConfigForm">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Settings class="h-5 w-5" />
                        {{ hasActiveConfig ? 'Modificar' : 'Crear' }} Configuración de Candidaturas
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <DynamicFormBuilder
                        v-model="form.campos"
                        title="Campos del Formulario de Candidaturas"
                        description="Configura los campos que aparecerán en el formulario que completarán los usuarios para crear su perfil de candidatura"
                        :disabled="form.processing"
                        :show-editable-option="true"
                        :show-convocatoria-config="true"
                        :context="'candidatura'"
                    />

                    <!-- Errores del formulario -->
                    <div v-if="form.errors.campos" class="p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-red-800 dark:text-red-200 text-sm">{{ form.errors.campos }}</p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-between">
                        <Button variant="outline" @click="cancelConfig" :disabled="form.processing">
                            Cancelar
                        </Button>
                        <Button 
                            @click="saveConfig" 
                            :disabled="!canSave || form.processing"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Guardando...' : 'Guardar Configuración' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Historial de Configuraciones -->
            <Card v-if="historial.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="h-5 w-5" />
                        Historial de Configuraciones
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="config in historial"
                            :key="config.id"
                            class="border rounded-lg p-4 flex items-center justify-between"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <Badge variant="outline">Versión {{ config.version }}</Badge>
                                    <Badge :class="config.color_estado">{{ config.estado }}</Badge>
                                </div>
                                
                                <div class="grid gap-2 md:grid-cols-3 text-sm">
                                    <div>
                                        <span class="font-medium">Resumen:</span>
                                        <span class="text-muted-foreground ml-1">{{ config.resumen }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Creada por:</span>
                                        <span class="text-muted-foreground ml-1">{{ config.created_by.name }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Fecha:</span>
                                        <span class="text-muted-foreground ml-1">{{ config.fecha_creacion }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 ml-4">
                                <Button
                                    v-if="config.estado !== 'Activa'"
                                    variant="outline"
                                    size="sm"
                                    @click="activateConfig(config.id)"
                                >
                                    <CheckCircle class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>