<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import DynamicFormRenderer from '@/components/forms/DynamicFormRenderer.vue';
import { useAutoSave } from '@/composables/useAutoSave';
import { Save, Send, AlertCircle, CheckCircle2, Clock, Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import type { FormField } from '@/types/forms';

// Props
interface Props {
    formulario: {
        id: number;
        titulo: string;
        descripcion?: string;
        slug: string;
        configuracion_campos: FormField[];
        configuracion_general?: any;
        requiere_captcha: boolean;
        categoria?: {
            id: number;
            nombre: string;
        };
        mensaje_confirmacion?: string;
    };
    respuestaBorrador?: {
        id: number;
        respuestas: Record<string, any>;
    } | null;
    esVisitante: boolean;
}

const props = defineProps<Props>();

// Form data
const form = useForm({
    respuestas: props.respuestaBorrador?.respuestas || {},
    nombre_visitante: '',
    email_visitante: '',
    telefono_visitante: '',
    documento_visitante: '',
    captcha: '',
});

// Referencias para el formulario dinámico - asegurar que siempre sea un objeto
// Inicializar con valores por defecto para todos los campos
const initializeFormData = () => {
    const data: Record<string, any> = {};
    // Si hay respuestas previas, usarlas como base
    if (props.respuestaBorrador?.respuestas) {
        Object.assign(data, props.respuestaBorrador.respuestas);
    }
    // Asegurar que cada campo tenga un valor inicial
    if (props.formulario.configuracion_campos && Array.isArray(props.formulario.configuracion_campos)) {
        props.formulario.configuracion_campos.forEach(field => {
            if (!(field.id in data)) {
                // Asignar valor por defecto según el tipo de campo
                switch (field.type) {
                    case 'checkbox':
                        data[field.id] = false;
                        break;
                    case 'number':
                        data[field.id] = 0;
                        break;
                    case 'select':
                    case 'radio':
                        data[field.id] = '';
                        break;
                    default:
                        data[field.id] = '';
                }
            }
        });
    }
    return data;
};

const formDataRef = ref(initializeFormData());
const currentStep = ref(0);
const totalSteps = computed(() => {
    // Calcular pasos si hay secciones
    return 1; // Por simplicidad, un solo paso
});

// Validar que el formulario tenga campos configurados
const tieneCamposConfigurados = computed(() => {
    return props.formulario.configuracion_campos && 
           Array.isArray(props.formulario.configuracion_campos) && 
           props.formulario.configuracion_campos.length > 0;
});

// Estado local
const isSubmitting = ref(false);
const showFloatingBar = ref(true);
const lastSavedTime = ref<Date | null>(null);

// Progress calculation
const progress = computed(() => {
    const totalFields = props.formulario.configuracion_campos.length;
    const filledFields = Object.keys(form.respuestas).filter(key => {
        const value = form.respuestas[key];
        return value !== null && value !== '' && value !== undefined;
    }).length;
    
    return totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;
});

// Autoguardado usando el composable
const {
    state: autoSaveState,
    isSaving,
    hasSaved,
    saveNow,
    startWatching,
    stopWatching,
    clearLocalStorage,
} = useAutoSave(formDataRef || ref({}), {
    url: route('api.formularios.autosave'),
    resourceId: props.respuestaBorrador?.id || null,
    resourceIdField: 'respuesta_id',
    debounceTime: 3000,
    showNotifications: false,
    useLocalStorage: true,
    localStorageKey: `formulario_${props.formulario.slug}_draft`,
    additionalData: {
        formulario_id: props.formulario.id,
    },
});

// Observar cambios en los datos del formulario
watch(formDataRef, (newData) => {
    if (newData) {
        form.respuestas = newData;
    }
}, { deep: true });

// Observar estado de autoguardado
watch(() => autoSaveState.value.status, (status) => {
    if (status === 'saved') {
        lastSavedTime.value = autoSaveState.value.lastSaved;
    }
});

// Inicializar autoguardado
onMounted(() => {
    // Solo iniciar autoguardado si el usuario está autenticado
    if (!props.esVisitante) {
        startWatching();
    }
    
    // Detectar si el usuario intenta salir con cambios sin guardar
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
    if (!props.esVisitante) {
        stopWatching();
    }
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

// Manejar salida de la página
const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (isSaving.value || (progress.value > 0 && progress.value < 100)) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
    }
};

// Validar formulario
const validateForm = (): boolean => {
    // Verificar si hay campos configurados
    const campos = props.formulario.configuracion_campos || [];
    
    // Validar campos requeridos del formulario dinámico
    for (const field of campos) {
        if (field.required && !form.respuestas[field.id]) {
            toast.error(`El campo "${field.title}" es requerido`);
            return false;
        }
    }
    
    // Validar datos del visitante si es necesario
    if (props.esVisitante) {
        if (!form.nombre_visitante || !form.email_visitante) {
            toast.error('Por favor completa tu nombre y email');
            return false;
        }
        
        // Validar formato de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(form.email_visitante)) {
            toast.error('Por favor ingresa un email válido');
            return false;
        }
    }
    
    return true;
};

// Enviar formulario
const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }
    
    isSubmitting.value = true;
    
    // Detener autoguardado si está activo
    if (!props.esVisitante) {
        stopWatching();
    }
    
    try {
        // Preparar datos para envío
        const dataToSubmit = {
            ...form.data(),
            formulario_id: props.formulario.id,
        };
        
        // Enviar formulario
        await router.post(
            route('formularios.store', props.formulario.slug),
            dataToSubmit,
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Limpiar localStorage
                    clearLocalStorage();
                    toast.success('Formulario enviado exitosamente');
                },
                onError: (errors) => {
                    toast.error('Error al enviar el formulario');
                    console.error(errors);
                    isSubmitting.value = false;
                    // Reiniciar autoguardado si hay error
                    if (!props.esVisitante) {
                        startWatching();
                    }
                },
            }
        );
    } catch (error) {
        toast.error('Error inesperado al enviar el formulario');
        isSubmitting.value = false;
        if (!props.esVisitante) {
            startWatching();
        }
    }
};

// Guardar borrador manualmente
const handleSaveDraft = () => {
    if (!props.esVisitante) {
        saveNow();
        toast.success('Borrador guardado');
    }
};

// Formatear tiempo de último guardado
const formatLastSaved = computed(() => {
    if (!lastSavedTime.value) return '';
    
    const now = new Date();
    const diff = Math.floor((now.getTime() - lastSavedTime.value.getTime()) / 1000);
    
    if (diff < 60) return 'hace unos segundos';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} minutos`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} horas`;
    return 'hace más de un día';
});

// Actualizar respuestas cuando cambian en el renderer
const handleFieldUpdate = (fieldId: string, value: any) => {
    if (!formDataRef.value) {
        formDataRef.value = {};
    }
    formDataRef.value = {
        ...formDataRef.value,
        [fieldId]: value,
    };
};

// Nuevo método para manejar actualizaciones del formulario completo
const handleFormUpdate = (newData: Record<string, any>) => {
    formDataRef.value = newData || {};
};
</script>

<template>
    <Head :title="formulario.titulo" />
    
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
        <!-- Header -->
        <div class="bg-white border-b">
            <div class="container mx-auto px-4 py-6">
                <div class="max-w-4xl mx-auto">
                    <Badge v-if="formulario.categoria" class="mb-2">
                        {{ formulario.categoria.nombre }}
                    </Badge>
                    <h1 class="text-3xl font-bold text-gray-900">{{ formulario.titulo }}</h1>
                    <p v-if="formulario.descripcion" class="text-gray-600 mt-2">
                        {{ formulario.descripcion }}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto">
                <!-- Barra de progreso -->
                <Card class="mb-6">
                    <CardContent class="py-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium">Progreso del formulario</span>
                            <span class="text-sm text-muted-foreground">{{ progress }}% completado</span>
                        </div>
                        <Progress :value="progress" class="h-2" />
                    </CardContent>
                </Card>
                
                <!-- Datos del visitante (si aplica) -->
                <Card v-if="esVisitante" class="mb-6">
                    <CardHeader>
                        <CardTitle>Información de contacto</CardTitle>
                        <CardDescription>
                            Por favor proporciona tus datos para poder contactarte
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="nombre">Nombre completo *</Label>
                                <Input
                                    id="nombre"
                                    v-model="form.nombre_visitante"
                                    placeholder="Tu nombre completo"
                                    :disabled="isSubmitting"
                                />
                            </div>
                            <div>
                                <Label htmlFor="email">Correo electrónico *</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email_visitante"
                                    placeholder="tu@email.com"
                                    :disabled="isSubmitting"
                                />
                            </div>
                            <div>
                                <Label htmlFor="telefono">Teléfono</Label>
                                <Input
                                    id="telefono"
                                    v-model="form.telefono_visitante"
                                    placeholder="Tu número de teléfono"
                                    :disabled="isSubmitting"
                                />
                            </div>
                            <div>
                                <Label htmlFor="documento">Documento de identidad</Label>
                                <Input
                                    id="documento"
                                    v-model="form.documento_visitante"
                                    placeholder="Tu número de documento"
                                    :disabled="isSubmitting"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <!-- Formulario dinámico -->
                <Card class="mb-20">
                    <CardHeader>
                        <CardTitle>Completa el formulario</CardTitle>
                        <CardDescription v-if="!esVisitante && hasSaved">
                            <div class="flex items-center gap-2 text-green-600">
                                <CheckCircle2 class="h-4 w-4" />
                                Autoguardado {{ formatLastSaved }}
                            </div>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Alert v-if="!tieneCamposConfigurados" variant="destructive" class="mb-4">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                Este formulario no tiene campos configurados. Por favor, contacta al administrador.
                            </AlertDescription>
                        </Alert>
                        
                        <DynamicFormRenderer
                            v-if="tieneCamposConfigurados"
                            :fields="formulario.configuracion_campos || []"
                            :model-value="formDataRef || {}"
                            :disabled="isSubmitting"
                            @update:modelValue="handleFormUpdate"
                        />
                    </CardContent>
                </Card>
                
                <!-- Errores -->
                <Alert v-if="form.errors && Object.keys(form.errors).length > 0" class="mb-6" variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        <ul class="list-disc list-inside mt-2">
                            <li v-for="(error, key) in form.errors" :key="key">
                                {{ error }}
                            </li>
                        </ul>
                    </AlertDescription>
                </Alert>
            </div>
        </div>
        
        <!-- Barra flotante inferior -->
        <div 
            v-show="showFloatingBar"
            class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t shadow-lg z-50"
        >
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto py-4">
                    <div class="flex items-center justify-between">
                        <!-- Información de estado -->
                        <div class="flex items-center gap-4">
                            <div v-if="isSaving" class="flex items-center gap-2 text-sm text-muted-foreground">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                Guardando...
                            </div>
                            <div v-else-if="hasSaved && !esVisitante" class="flex items-center gap-2 text-sm text-green-600">
                                <CheckCircle2 class="h-4 w-4" />
                                Guardado
                            </div>
                            
                            <div class="hidden md:flex items-center gap-2 text-sm text-muted-foreground">
                                <Clock class="h-4 w-4" />
                                {{ progress }}% completado
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="!esVisitante"
                                variant="outline"
                                size="sm"
                                @click="handleSaveDraft"
                                :disabled="isSaving || isSubmitting"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                Guardar borrador
                            </Button>
                            
                            <Button
                                size="sm"
                                @click="handleSubmit"
                                :disabled="isSubmitting || isSaving || progress === 0 || !tieneCamposConfigurados"
                            >
                                <Send v-if="!isSubmitting" class="mr-2 h-4 w-4" />
                                <Loader2 v-else class="mr-2 h-4 w-4 animate-spin" />
                                {{ isSubmitting ? 'Enviando...' : 'Enviar formulario' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animación para la barra flotante */
.fixed {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}
</style>