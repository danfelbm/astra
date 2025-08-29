<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import GeographicSelector from '@/components/forms/GeographicSelector.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import UserLayout from "@/layouts/UserLayout.vue";
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { AlertCircle, CheckCircle, Clock, MapPin, FileText, Mail, Phone } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        telefono?: string;
        territorio_id?: number;
        territorio?: { id: number; nombre: string };
        departamento_id?: number;
        departamento?: { id: number; nombre: string };
        municipio_id?: number;
        municipio?: { id: number; nombre: string };
        localidad_id?: number;
        localidad?: { id: number; nombre: string };
    };
    hasPendingRequest: boolean;
    pendingRequest?: {
        id: number;
        status: string;
        created_at: string;
        new_email?: string;
        new_telefono?: string;
        new_territorio?: { id: number; nombre: string };
        new_departamento?: { id: number; nombre: string };
        new_municipio?: { id: number; nombre: string };
        new_localidad?: { id: number; nombre: string };
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings/profile',
    },
    {
        title: 'Actualización de Datos',
        href: '/settings/update-data',
    },
];

// Formulario para actualización de datos
const form = useForm({
    email: props.user.email || '',
    telefono: props.user.telefono || '',
    territorio_id: props.user.territorio_id || undefined,
    departamento_id: props.user.departamento_id || undefined,
    municipio_id: props.user.municipio_id || undefined,
    localidad_id: props.user.localidad_id || undefined,
    documentos: [] as File[],
});

// Estado para archivos
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);

// Ubicación actual como texto
const currentLocation = computed(() => {
    const parts = [];
    if (props.user.departamento?.nombre) parts.push(props.user.departamento.nombre);
    if (props.user.municipio?.nombre) parts.push(props.user.municipio.nombre);
    if (props.user.localidad?.nombre) parts.push(props.user.localidad.nombre);
    return parts.length > 0 ? parts.join(', ') : 'No definida';
});

// Ubicación nueva solicitada como texto
const pendingLocation = computed(() => {
    if (!props.pendingRequest) return '';
    const parts = [];
    if (props.pendingRequest.new_departamento?.nombre) parts.push(props.pendingRequest.new_departamento.nombre);
    if (props.pendingRequest.new_municipio?.nombre) parts.push(props.pendingRequest.new_municipio.nombre);
    if (props.pendingRequest.new_localidad?.nombre) parts.push(props.pendingRequest.new_localidad.nombre);
    return parts.length > 0 ? parts.join(', ') : 'No definida';
});

// Manejar actualización de ubicación geográfica
const handleGeographicUpdate = (value: any) => {
    form.territorio_id = value.territorio_id;
    form.departamento_id = value.departamento_id;
    form.municipio_id = value.municipio_id;
    form.localidad_id = value.localidad_id;
};

// Manejar selección de archivos
const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        const files = Array.from(input.files);
        
        // Validar cantidad máxima (3 archivos)
        if (selectedFiles.value.length + files.length > 3) {
            toast.error('Puedes subir máximo 3 documentos');
            return;
        }
        
        // Validar tamaño (5MB por archivo)
        for (const file of files) {
            if (file.size > 5 * 1024 * 1024) {
                toast.error(`El archivo ${file.name} supera los 5MB permitidos`);
                return;
            }
        }
        
        selectedFiles.value = [...selectedFiles.value, ...files];
        form.documentos = selectedFiles.value;
    }
};

// Eliminar archivo seleccionado
const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    form.documentos = selectedFiles.value;
};

// Enviar solicitud
const submit = () => {
    // Verificar que hay documentos
    if (selectedFiles.value.length === 0) {
        toast.error('Debes adjuntar al menos un documento de soporte');
        return;
    }
    
    // Verificar que hay cambios
    const hasChange = form.email !== props.user.email ||
                     form.telefono !== props.user.telefono ||
                     form.territorio_id !== props.user.territorio_id ||
                     form.departamento_id !== props.user.departamento_id ||
                     form.municipio_id !== props.user.municipio_id ||
                     form.localidad_id !== props.user.localidad_id;
    
    if (!hasChange) {
        toast.error('Debes realizar al menos un cambio en tus datos');
        return;
    }
    
    form.post(route('update-data.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Solicitud enviada correctamente');
            selectedFiles.value = [];
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onError: (errors) => {
            if (errors.error) {
                toast.error(errors.error);
            } else {
                toast.error('Error al enviar la solicitud');
            }
        },
    });
};

// Cancelar solicitud pendiente
const cancelRequest = () => {
    if (confirm('¿Estás seguro de que deseas cancelar tu solicitud de actualización de datos?')) {
        form.delete(route('update-data.cancel'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Solicitud cancelada exitosamente');
            },
            onError: () => {
                toast.error('Error al cancelar la solicitud');
            },
        });
    }
};

// Formatear tamaño de archivo
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<template>
    <UserLayout :breadcrumbs="breadcrumbs">
        <Head title="Actualización de Datos" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall 
                    title="Actualización de Datos" 
                    description="Solicita una actualización de tus datos personales. La solicitud será revisada por un administrador." 
                />

                <!-- Ubicación Actual -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base flex items-center gap-2">
                            <MapPin class="h-4 w-4" />
                            Ubicación Actual
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">{{ currentLocation }}</p>
                    </CardContent>
                </Card>

                <!-- Solicitud Pendiente -->
                <Alert v-if="hasPendingRequest && pendingRequest" class="border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-950/50">
                    <Clock class="h-4 w-4 text-yellow-600 dark:text-yellow-500" />
                    <AlertTitle class="text-yellow-800 dark:text-yellow-200">Solicitud Pendiente</AlertTitle>
                    <AlertDescription class="text-yellow-700 dark:text-yellow-300">
                        <p>Tienes una solicitud de actualización de datos pendiente de aprobación.</p>
                        <p class="mt-2" v-if="pendingRequest.new_email">
                            <strong>Nuevo email:</strong> {{ pendingRequest.new_email }}
                        </p>
                        <p class="mt-2" v-if="pendingRequest.new_telefono">
                            <strong>Nuevo teléfono:</strong> {{ pendingRequest.new_telefono }}
                        </p>
                        <p class="mt-2" v-if="pendingLocation">
                            <strong>Nueva ubicación:</strong> {{ pendingLocation }}
                        </p>
                        <p class="mt-1 text-xs opacity-80">
                            Enviada el: {{ new Date(pendingRequest.created_at).toLocaleDateString('es-CO', { 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) }}
                        </p>
                        <Button 
                            @click="cancelRequest" 
                            variant="outline" 
                            size="sm" 
                            class="mt-3 border-yellow-600 dark:border-yellow-500 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-900/30"
                            :disabled="form.processing"
                        >
                            Cancelar Solicitud
                        </Button>
                    </AlertDescription>
                </Alert>

                <!-- Formulario de Actualización -->
                <form @submit.prevent="submit" class="space-y-6" v-if="!hasPendingRequest">
                    <!-- Campos de Email y Teléfono -->
                    <div class="space-y-4">
                        <div>
                            <Label class="text-base mb-1 block flex items-center gap-2">
                                <Mail class="h-4 w-4" />
                                Correo Electrónico
                            </Label>
                            <Input 
                                type="email" 
                                v-model="form.email" 
                                placeholder="ejemplo@correo.com"
                                :disabled="form.processing"
                                class="w-full"
                            />
                            <p class="text-sm text-muted-foreground mt-1">Actual: {{ user.email }}</p>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        
                        <div>
                            <Label class="text-base mb-1 block flex items-center gap-2">
                                <Phone class="h-4 w-4" />
                                Teléfono
                            </Label>
                            <Input 
                                type="tel" 
                                v-model="form.telefono" 
                                placeholder="+57 300 1234567"
                                :disabled="form.processing"
                                class="w-full"
                            />
                            <p class="text-sm text-muted-foreground mt-1">Actual: {{ user.telefono || 'No registrado' }}</p>
                            <InputError class="mt-2" :message="form.errors.telefono" />
                        </div>
                    </div>
                    <!-- Selector de Nueva Ubicación -->
                    <div>
                        <Label class="text-base mb-3 block">Nueva Ubicación de Residencia</Label>
                        <GeographicSelector
                            :model-value="{
                                territorio_id: form.territorio_id,
                                departamento_id: form.departamento_id,
                                municipio_id: form.municipio_id,
                                localidad_id: form.localidad_id,
                            }"
                            @update:model-value="handleGeographicUpdate"
                            mode="single"
                            :show-card="false"
                            :disabled="form.processing"
                        />
                        <InputError class="mt-2" :message="form.errors.territorio_id" />
                        <InputError class="mt-2" :message="form.errors.departamento_id" />
                        <InputError class="mt-2" :message="form.errors.municipio_id" />
                        <InputError class="mt-2" :message="form.errors.localidad_id" />
                    </div>

                    <!-- Documentos de Soporte (Obligatorio) -->
                    <div>
                        <Label class="text-base mb-1 block">
                            Documentos de Soporte (Obligatorio)
                        </Label>
                        <p class="text-sm text-muted-foreground mb-3">
                            Debes adjuntar al menos 1 documento que respalde tu solicitud (máximo 3). Formatos: PDF, JPG, PNG - Máx 5MB c/u
                        </p>
                        
                        <!-- Lista de archivos seleccionados -->
                        <div v-if="selectedFiles.length > 0" class="mb-3 space-y-2">
                            <div v-for="(file, index) in selectedFiles" :key="index" 
                                class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-gray-500" />
                                    <span class="text-sm">{{ file.name }}</span>
                                    <span class="text-xs text-gray-500">({{ formatFileSize(file.size) }})</span>
                                </div>
                                <Button 
                                    @click="removeFile(index)" 
                                    variant="ghost" 
                                    size="sm"
                                    type="button"
                                >
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                        
                        <!-- Input de archivos -->
                        <input
                            ref="fileInput"
                            type="file"
                            @change="handleFileSelect"
                            accept=".pdf,.jpg,.jpeg,.png"
                            multiple
                            class="hidden"
                            :disabled="form.processing || selectedFiles.length >= 3"
                        />
                        <Button 
                            @click="fileInput?.click()" 
                            variant="outline" 
                            type="button"
                            :disabled="form.processing || selectedFiles.length >= 3"
                        >
                            Seleccionar Documentos
                        </Button>
                        <InputError class="mt-2" :message="form.errors.documentos" />
                    </div>

                    <!-- Información Importante -->
                    <Alert>
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Información Importante</AlertTitle>
                        <AlertDescription>
                            <ul class="list-disc list-inside space-y-1 mt-2">
                                <li>Tu solicitud será revisada por un administrador</li>
                                <li>Recibirás una notificación por correo cuando sea procesada</li>
                                <li>Los cambios no se aplicarán hasta que sean aprobados</li>
                                <li>Solo puedes tener una solicitud pendiente a la vez</li>
                            </ul>
                        </AlertDescription>
                    </Alert>

                    <!-- Botón de Envío -->
                    <div class="flex items-center gap-4">
                        <Button 
                            type="submit" 
                            :disabled="form.processing || selectedFiles.length === 0"
                        >
                            Enviar Solicitud
                        </Button>

                        <span v-if="form.processing" class="text-sm text-muted-foreground">
                            Enviando solicitud...
                        </span>
                    </div>
                </form>

                <!-- Mensaje cuando hay solicitud pendiente -->
                <Alert v-else class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/50">
                    <AlertCircle class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    <AlertDescription class="text-blue-700 dark:text-blue-300">
                        No puedes crear una nueva solicitud mientras tengas una pendiente de aprobación.
                    </AlertDescription>
                </Alert>
            </div>
        </SettingsLayout>
    </UserLayout>
</template>