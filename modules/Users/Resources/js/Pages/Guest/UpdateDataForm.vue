<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import GuestLayout from "@modules/Core/Resources/js/layouts/GuestLayout.vue";
import GeographicSelector from "@modules/Core/Resources/js/components/forms/GeographicSelector.vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { SPhoneInput } from "@modules/Core/Resources/js/components/ui/phone-input";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Loader2, Upload, X, FileText, CheckCircle, AlertCircle, MapPin } from 'lucide-vue-next';
import axios from 'axios';
import { toast } from 'vue-sonner';

const props = defineProps<{
    verification: {
        id: number;
        is_verified: boolean;
    };
    user: {
        name: string;
        email: string;
        telefono: string | null;
        documento_identidad: string;
        territorio_id?: number;
        territorio?: { id: number; nombre: string };
        departamento_id?: number;
        departamento?: { id: number; nombre: string };
        municipio_id?: number;
        municipio?: { id: number; nombre: string };
        localidad_id?: number;
        localidad?: { id: number; nombre: string };
    };
}>();

// Función para censurar nombre (ej: "Da**** Be*****")
const censorName = (name: string): string => {
    if (!name) return '';
    const parts = name.split(' ');
    return parts.map(part => {
        if (part.length <= 2) return part;
        const firstTwo = part.substring(0, 2);
        const rest = '*'.repeat(part.length - 2);
        return firstTwo + rest;
    }).join(' ');
};

// Función para censurar email
const censorEmail = (email: string): string => {
    if (!email) return '';
    const [username, domain] = email.split('@');
    if (!domain) return email;
    
    const [domainName, ...tldParts] = domain.split('.');
    const tld = tldParts.join('.');
    
    // Censurar username: primeros 2 caracteres y último
    let censoredUsername: string;
    if (username.length <= 3) {
        censoredUsername = username;
    } else {
        const firstTwo = username.substring(0, 2);
        const lastOne = username.substring(username.length - 1);
        const stars = '*'.repeat(username.length - 3);
        censoredUsername = firstTwo + stars + lastOne;
    }
    
    // Censurar dominio: primera letra y resto con asteriscos
    const censoredDomain = domainName[0] + '*'.repeat(domainName.length - 1);
    
    return `${censoredUsername}@${censoredDomain}.${tld}`;
};

// Función para censurar teléfono
const censorPhone = (phone: string): string => {
    if (!phone) return '';
    // Mantener los primeros 5 caracteres y el último
    if (phone.length <= 6) return phone;
    
    const first = phone.substring(0, 5);
    const last = phone.substring(phone.length - 1);
    const stars = '*'.repeat(phone.length - 6);
    
    return first + stars + last;
};

// Estados del formulario
const isSubmitting = ref(false);
const showUpdateForm = ref(!props.verification.is_verified); // Solo mostrar formulario si NO está verificado
const showSuccessMessage = ref(false); // Para mostrar mensaje de éxito después del envío
const formData = ref({
    email: '', // No prellenar por seguridad
    telefono: '', // No prellenar por seguridad
    territorio_id: props.user.territorio_id || undefined,
    departamento_id: props.user.departamento_id || undefined,
    municipio_id: props.user.municipio_id || undefined,
    localidad_id: props.user.localidad_id || undefined
});

// Ubicación actual como texto
const currentLocation = computed(() => {
    const parts = [];
    if (props.user.departamento?.nombre) parts.push(props.user.departamento.nombre);
    if (props.user.municipio?.nombre) parts.push(props.user.municipio.nombre);
    if (props.user.localidad?.nombre) parts.push(props.user.localidad.nombre);
    return parts.length > 0 ? parts.join(', ') : 'No definida';
});

// Manejar actualización de ubicación geográfica
const handleGeographicUpdate = (value: any) => {
    formData.value.territorio_id = value.territorio_id;
    formData.value.departamento_id = value.departamento_id;
    formData.value.municipio_id = value.municipio_id;
    formData.value.localidad_id = value.localidad_id;
};

// Estados de archivos
const selectedFiles = ref<File[]>([]);
const uploadErrors = ref<string[]>([]);
const uploadProgress = ref<number>(0);
const isUploading = ref(false);
const maxFiles = 5;
const maxFileSize = 10 * 1024 * 1024; // 10MB
const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/png', 'image/jpeg'];
const allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];

// Manejo de archivos
const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files || []);
    
    uploadErrors.value = [];
    
    // Validar cantidad de archivos
    if (selectedFiles.value.length + files.length > maxFiles) {
        uploadErrors.value.push(`Máximo ${maxFiles} archivos permitidos`);
        return;
    }
    
    // Validar cada archivo
    for (const file of files) {
        // Validar tamaño
        if (file.size > maxFileSize) {
            uploadErrors.value.push(`${file.name} excede el tamaño máximo de 10MB`);
            continue;
        }
        
        // Validar tipo
        const extension = file.name.split('.').pop()?.toLowerCase();
        if (!allowedTypes.includes(file.type) && (!extension || !allowedExtensions.includes(extension))) {
            uploadErrors.value.push(`${file.name} tiene un formato no permitido`);
            continue;
        }
        
        // Agregar archivo si pasa validaciones
        selectedFiles.value.push(file);
    }
    
    // Limpiar input
    input.value = '';
};

// Remover archivo
const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    uploadErrors.value = [];
};

// Formatear tamaño de archivo
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

// Enviar formulario
const submitForm = async () => {
    // Validar que haya archivos (obligatorio)
    if (selectedFiles.value.length === 0) {
        toast.error('Por favor adjunta al menos un documento de soporte');
        return;
    }
    
    // Validar que haya cambios o archivos
    const hasEmailChange = formData.value.email && formData.value.email !== props.user.email;
    const hasPhoneChange = formData.value.telefono && formData.value.telefono !== props.user.telefono;
    const hasLocationChange = formData.value.territorio_id !== props.user.territorio_id ||
                             formData.value.departamento_id !== props.user.departamento_id ||
                             formData.value.municipio_id !== props.user.municipio_id ||
                             formData.value.localidad_id !== props.user.localidad_id;
    
    if (!hasEmailChange && !hasPhoneChange && !hasLocationChange && selectedFiles.value.length === 0) {
        toast.error('No se detectaron cambios para actualizar');
        return;
    }
    
    // Validar formato de email
    if (formData.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
        toast.error('Por favor ingresa un email válido');
        return;
    }
    
    isSubmitting.value = true;
    isUploading.value = true;
    uploadProgress.value = 0;
    
    try {
        // Crear FormData para enviar archivos
        const data = new FormData();
        data.append('verification_id', props.verification.id.toString());
        
        if (formData.value.email && formData.value.email !== props.user.email) {
            data.append('email', formData.value.email);
        }
        
        if (formData.value.telefono && formData.value.telefono !== props.user.telefono) {
            data.append('telefono', formData.value.telefono);
        }
        
        // Agregar campos geográficos si hay cambios
        if (formData.value.territorio_id !== props.user.territorio_id) {
            data.append('territorio_id', formData.value.territorio_id?.toString() || '');
        }
        if (formData.value.departamento_id !== props.user.departamento_id) {
            data.append('departamento_id', formData.value.departamento_id?.toString() || '');
        }
        if (formData.value.municipio_id !== props.user.municipio_id) {
            data.append('municipio_id', formData.value.municipio_id?.toString() || '');
        }
        if (formData.value.localidad_id !== props.user.localidad_id) {
            data.append('localidad_id', formData.value.localidad_id?.toString() || '');
        }
        
        // Agregar archivos
        selectedFiles.value.forEach((file, index) => {
            data.append(`documentos[${index}]`, file);
        });
        
        const response = await axios.post('/confirmar-registro/actualizar-datos', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                const percentCompleted = progressEvent.total 
                    ? Math.round((progressEvent.loaded * 100) / progressEvent.total)
                    : 0;
                uploadProgress.value = percentCompleted;
            }
        });
        
        if (response.data.success) {
            toast.success('Solicitud enviada correctamente', {
                description: 'Te notificaremos cuando sea procesada'
            });
            
            // Mostrar mensaje de éxito en lugar de redirigir
            showSuccessMessage.value = true;
            showUpdateForm.value = false;
        }
    } catch (error: any) {
        const message = error.response?.data?.message || 'Error al enviar la solicitud';
        const errors = error.response?.data?.errors;
        
        if (errors && Array.isArray(errors)) {
            errors.forEach((err: string) => toast.error(err));
        } else {
            toast.error(message);
        }
    } finally {
        isSubmitting.value = false;
        isUploading.value = false;
        uploadProgress.value = 0;
    }
};

// Cancelar y volver
const goBack = () => {
    window.location.href = '/confirmar-registro';
};
</script>

<template>
    <Head title="Actualizar Datos" />
    
    <GuestLayout 
        title="Actualizar Datos de Contacto"
        :description="verification.is_verified ? 'Tu identidad ha sido verificada. Puedes actualizar tus datos.' : 'Actualiza tus datos adjuntando documentación de soporte.'"
    >
        <div class="max-w-2xl mx-auto">
            <Card class="shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        {{ showSuccessMessage 
                            ? '✅ Solicitud Enviada' 
                            : verification.is_verified && !showUpdateForm 
                                ? '✅ Identidad Verificada' 
                                : '📝 Actualización de Datos' }}
                    </CardTitle>
                    <CardDescription>
                        {{ showSuccessMessage 
                            ? 'Tu solicitud ha sido recibida exitosamente'
                            : verification.is_verified && !showUpdateForm 
                                ? 'Tu identidad ha sido confirmada exitosamente'
                                : 'Actualiza tu información de contacto para mantener tus datos al día' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Vista de éxito después del envío de solicitud -->
                    <template v-if="showSuccessMessage">
                        <div class="space-y-6">
                            <!-- Mensaje de éxito prominente -->
                            <Alert class="border-green-200 bg-green-50 dark:bg-green-900/20">
                                <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                                <AlertDescription class="text-green-800 dark:text-green-200">
                                    <strong>¡Solicitud enviada exitosamente!</strong><br>
                                    Tu solicitud de actualización de datos ha sido enviada correctamente.
                                </AlertDescription>
                            </Alert>

                            <!-- Información sobre el proceso -->
                            <div class="bg-muted p-4 rounded-lg">
                                <h3 class="font-semibold mb-3 text-lg">¿Qué sigue ahora?</h3>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3">
                                        <div class="rounded-full bg-primary/10 p-1 mt-0.5">
                                            <CheckCircle class="h-4 w-4 text-primary" />
                                        </div>
                                        <div>
                                            <p class="font-medium">Revisión administrativa</p>
                                            <p class="text-sm text-muted-foreground">Tu solicitud será revisada por un administrador</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="rounded-full bg-primary/10 p-1 mt-0.5">
                                            <CheckCircle class="h-4 w-4 text-primary" />
                                        </div>
                                        <div>
                                            <p class="font-medium">Notificación por email y WhatsApp</p>
                                            <p class="text-sm text-muted-foreground">Te informaremos cuando tu solicitud sea procesada</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="rounded-full bg-primary/10 p-1 mt-0.5">
                                            <CheckCircle class="h-4 w-4 text-primary" />
                                        </div>
                                        <div>
                                            <p class="font-medium">Actualización de datos</p>
                                            <p class="text-sm text-muted-foreground">Si es aprobada, tus datos serán actualizados automáticamente</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <Button
                                    @click="goBack"
                                    type="button"
                                    variant="default"
                                    class="flex-1"
                                >
                                    Volver al inicio
                                </Button>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Mostrar contenido diferente según si está verificado y si quiere actualizar -->
                    <template v-else-if="verification.is_verified && !showUpdateForm">
                        <!-- Vista de éxito cuando está verificado -->
                        <div class="space-y-6">
                            <!-- Mensaje de éxito prominente -->
                            <Alert class="border-green-200 bg-green-50 dark:bg-green-900/20">
                                <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                                <AlertDescription class="text-green-800 dark:text-green-200">
                                    <strong>¡Verificación exitosa!</strong><br>
                                    Tu identidad ha sido confirmada correctamente.
                                </AlertDescription>
                            </Alert>

                            <!-- Información del usuario verificado -->
                            <div class="bg-muted p-4 rounded-lg">
                                <h3 class="font-semibold mb-3 text-lg">Datos Verificados</h3>
                                <div class="space-y-2">
                                    <p class="flex justify-between items-center">
                                        <span class="text-muted-foreground">Nombre:</span>
                                        <span class="font-medium">{{ censorName(user.name) }}</span>
                                    </p>
                                    <p class="flex justify-between items-center">
                                        <span class="text-muted-foreground">Documento:</span>
                                        <span class="font-medium">{{ user.documento_identidad }}</span>
                                    </p>
                                    <p class="flex justify-between items-center">
                                        <span class="text-muted-foreground">Email:</span>
                                        <span class="font-medium">{{ user.email ? censorEmail(user.email) : 'No registrado' }}</span>
                                    </p>
                                    <p class="flex justify-between items-center">
                                        <span class="text-muted-foreground">Teléfono:</span>
                                        <span class="font-medium">{{ user.telefono ? censorPhone(user.telefono) : 'No registrado' }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <Button
                                    @click="showUpdateForm = true"
                                    type="button"
                                    variant="outline"
                                    class="flex-1"
                                >
                                    ¿Deseas actualizar datos?
                                </Button>
                                <Button
                                    @click="goBack"
                                    type="button"
                                    variant="default"
                                    class="flex-1"
                                >
                                    Finalizar
                                </Button>
                            </div>
                        </div>
                    </template>

                    <!-- Formulario de actualización -->
                    <template v-else>
                        <!-- Información del usuario -->
                        <div class="bg-muted p-4 rounded-lg mb-6">
                            <h3 class="font-semibold mb-2">Datos Actuales</h3>
                            <div class="space-y-1 text-sm">
                                <p><strong>Nombre:</strong> {{ censorName(user.name) }}</p>
                                <p><strong>Documento:</strong> {{ user.documento_identidad }}</p>
                                <p><strong>Email:</strong> {{ user.email ? censorEmail(user.email) : 'No registrado' }}</p>
                                <p><strong>Teléfono:</strong> {{ user.telefono ? censorPhone(user.telefono) : 'No registrado' }}</p>
                                <p><strong>Ubicación:</strong> {{ currentLocation }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Email -->
                        <div>
                            <Label for="email">Nuevo Email</Label>
                            <Input
                                id="email"
                                v-model="formData.email"
                                type="email"
                                placeholder="ejemplo@correo.com"
                                class="mt-1"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Deja vacío si no deseas cambiar el email
                            </p>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <Label for="telefono">Nuevo Teléfono</Label>
                            <SPhoneInput
                                id="telefono"
                                v-model="formData.telefono"
                                :default-country="'CO'"
                                placeholder="300 123 4567"
                                class="mt-1"
                                @keydown.enter.prevent
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Deja vacío si no deseas cambiar el teléfono
                            </p>
                        </div>

                        <!-- Ubicación Geográfica -->
                        <div>
                            <Label class="mb-2 flex items-center gap-2">
                                <MapPin class="h-4 w-4" />
                                Nueva Ubicación de Residencia
                            </Label>
                            <GeographicSelector
                                :model-value="{
                                    territorio_id: formData.territorio_id,
                                    departamento_id: formData.departamento_id,
                                    municipio_id: formData.municipio_id,
                                    localidad_id: formData.localidad_id,
                                }"
                                @update:model-value="handleGeographicUpdate"
                                mode="single"
                                :show-card="false"
                                :disabled="isSubmitting"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Selecciona tu ubicación actual si ha cambiado
                            </p>
                        </div>

                        <!-- Documentos de soporte -->
                        <div>
                            <Label>Documentos de Soporte <span class="text-red-500">*</span> (Obligatorio)</Label>
                            <p class="text-sm text-muted-foreground mb-2">
                                Debes adjuntar al menos un documento de identidad o comprobante para validar la actualización
                            </p>
                            
                            <div class="space-y-3">
                                <!-- Barra de progreso de upload -->
                                <div v-if="isUploading" class="space-y-2">
                                    <div class="flex justify-between text-sm text-muted-foreground">
                                        <span>Subiendo archivos...</span>
                                        <span>{{ uploadProgress }}%</span>
                                    </div>
                                    <Progress :value="uploadProgress" class="w-full" />
                                </div>
                                
                                <!-- Input de archivos -->
                                <div class="flex items-center gap-2">
                                    <Input
                                        type="file"
                                        @change="handleFileSelect"
                                        :accept="allowedExtensions.map(ext => `.${ext}`).join(',')"
                                        multiple
                                        :disabled="isUploading"
                                        class="hidden"
                                        id="file-input"
                                    />
                                    <Label
                                        for="file-input"
                                        :class="[
                                            'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background h-10 px-4 py-2',
                                            isUploading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-accent hover:text-accent-foreground'
                                        ]"
                                    >
                                        <Upload class="mr-2 h-4 w-4" />
                                        {{ isUploading ? 'Subiendo...' : 'Seleccionar archivos' }}
                                    </Label>
                                    <span class="text-sm text-muted-foreground">
                                        {{ selectedFiles.length }}/{{ maxFiles }} archivos
                                    </span>
                                </div>

                                <!-- Lista de archivos seleccionados -->
                                <div v-if="selectedFiles.length > 0" class="space-y-2">
                                    <div
                                        v-for="(file, index) in selectedFiles"
                                        :key="index"
                                        class="flex items-center justify-between p-2 bg-muted rounded-md"
                                    >
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                            <div class="min-w-0">
                                                <p class="text-sm truncate">{{ file.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
                                            </div>
                                        </div>
                                        <Button
                                            @click="removeFile(index)"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Errores de upload -->
                                <Alert v-if="uploadErrors.length > 0" variant="destructive">
                                    <AlertCircle class="h-4 w-4" />
                                    <AlertDescription>
                                        <ul class="list-disc list-inside">
                                            <li v-for="error in uploadErrors" :key="error">{{ error }}</li>
                                        </ul>
                                    </AlertDescription>
                                </Alert>

                                <!-- Mensaje de archivo requerido -->
                                <Alert v-if="selectedFiles.length === 0" variant="default" class="border-orange-200 bg-orange-50 dark:bg-orange-900/20">
                                    <AlertCircle class="h-4 w-4 text-orange-600" />
                                    <AlertDescription class="text-orange-800 dark:text-orange-200">
                                        <strong>Importante:</strong> Debes adjuntar al menos un documento para procesar tu solicitud.
                                    </AlertDescription>
                                </Alert>

                                <p class="text-xs text-muted-foreground">
                                    Formatos permitidos: PDF, DOC, DOCX, PNG, JPG. Máximo 10MB por archivo.
                                </p>
                            </div>
                        </div>

                        <!-- Nota informativa -->
                        <Alert>
                            <AlertDescription class="text-foreground">
                                Tu solicitud será revisada por un administrador. Recibirás una notificación por email y WhatsApp cuando sea procesada.
                            </AlertDescription>
                        </Alert>

                        <!-- Botones de acción -->
                        <div class="flex gap-3">
                            <Button
                                type="submit"
                                :disabled="isSubmitting"
                                class="flex-1"
                            >
                                <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                                {{ isSubmitting ? 'Enviando...' : 'Enviar Solicitud' }}
                            </Button>
                            <Button
                                v-if="verification.is_verified"
                                @click="showUpdateForm = false"
                                type="button"
                                variant="outline"
                                :disabled="isSubmitting"
                            >
                                Volver
                            </Button>
                            <Button
                                v-else
                                @click="goBack"
                                type="button"
                                variant="outline"
                                :disabled="isSubmitting"
                            >
                                Cancelar
                            </Button>
                        </div>
                    </form>
                </template>
                </CardContent>
            </Card>
        </div>
    </GuestLayout>
</template>