<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import FileUploadField from '@/components/forms/FileUploadField.vue';
import ConvocatoriaSelector from '@/components/forms/ConvocatoriaSelector.vue';
import DatePickerField from '@/components/forms/DatePickerField.vue';
import DisclaimerField from '@/components/forms/DisclaimerField.vue';
import RepeaterField from '@/components/forms/RepeaterField.vue';
import { type BreadcrumbItemType } from '@/types';
import { type FormField } from '@/types/forms';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, AlertCircle, Clock, CheckCircle, PanelLeft } from 'lucide-vue-next';
import { computed, ref, watch, reactive, onMounted, onUnmounted } from 'vue';
import { useFileUpload } from '@/composables/useFileUpload';
import { useConditionalFields } from '@/composables/useConditionalFields';
import { useAutoSave } from '@/composables/useAutoSave';
import { toast } from 'vue-sonner';

interface Candidatura {
    id: number;
    formulario_data: Record<string, any>;
    estado: string;
    estado_label: string;
    version: number;
    comentarios_admin?: string;
}

interface Props {
    candidatura: Candidatura | null;
    configuracion_campos: FormField[];
    is_editing: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mi Candidatura', href: '/candidaturas' },
    { title: props.is_editing ? 'Editar' : 'Crear', href: '#' },
];

// Composable para manejo de archivos
const { uploadFiles } = useFileUpload();

// Archivos pendientes de subir por campo
const pendingFiles = ref<Record<string, File[]>>({});

// Detectar si los datos existentes no corresponden a la configuración actual (sin uso pero IMPORTANTE)
const datosDesactualizados = computed(() => {
    if (!props.is_editing || !props.candidatura?.formulario_data) return false;
    
    // Obtener las claves de los datos existentes
    const keysExistentes = Object.keys(props.candidatura.formulario_data);
    
    // Obtener los IDs de campos de la configuración actual
    const idsCamposActuales = props.configuracion_campos.map(c => c.id);
    
    // Verificar si hay campos en los datos que no existen en la configuración actual
    const camposObsoletos = keysExistentes.some(key => !idsCamposActuales.includes(key));
    
    // Verificar si hay campos nuevos requeridos sin datos
    const camposNuevosSinDatos = props.configuracion_campos.some(campo => 
        campo.required && !props.candidatura.formulario_data[campo.id]
    );
    
    return camposObsoletos || camposNuevosSinDatos;
});

// Inicializar datos del formulario como en Votar.vue
const initializeFormData = () => {
    const data: Record<string, any> = {};
    
    props.configuracion_campos.forEach(campo => {
        if (campo.type === 'checkbox') {
            data[campo.id] = [];
        } else if (campo.type === 'file') {
            data[campo.id] = []; // Array para almacenar las rutas de archivos
        } else if (campo.type === 'convocatoria') {
            data[campo.id] = undefined; // Campo para ID de convocatoria
        } else if (campo.type === 'disclaimer') {
            data[campo.id] = null; // Campo para disclaimer (objeto con accepted y timestamp)
        } else if (campo.type === 'repeater') {
            data[campo.id] = []; // Array para elementos del repetidor
        } else if (campo.type === 'datepicker') {
            data[campo.id] = null; // Campo para fecha
        } else {
            data[campo.id] = '';
        }
    });
    
    // Si estamos editando, sobrescribir con datos existentes
    if (props.candidatura?.formulario_data) {
        Object.assign(data, props.candidatura.formulario_data);
        
        // Asegurar que los campos de tipo file sean arrays
        props.configuracion_campos.forEach(campo => {
            if (campo.type === 'file' && data[campo.id]) {
                // Si el valor no es un array, convertirlo a array
                if (!Array.isArray(data[campo.id])) {
                    data[campo.id] = [data[campo.id]];
                }
            }
        });
    }
    
    return { formulario_data: data };
};

const form = useForm(initializeFormData());

// Usar el composable de campos condicionales
const fieldsRef = ref(props.configuracion_campos);
const formDataRef = computed(() => form.formulario_data);

const {
    visibleFields,
    shouldShowField,
    clearHiddenFieldValues
} = useConditionalFields(fieldsRef, formDataRef);

// Limpiar valores de campos ocultos cuando cambian las condiciones
watch(visibleFields, () => {
    clearHiddenFieldValues();
});

// Configurar autoguardado
const {
    state: autoSaveState,
    isSaving,
    hasSaved,
    hasError,
    saveNow,
    startWatching,
    stopAutoSave,
    restoreDraft,
    clearLocalStorage
} = useAutoSave(formDataRef, {
    url: '/candidaturas/autosave',
    resourceId: props.candidatura?.id || null,
    resourceIdField: 'candidatura_id', // Campo esperado en la respuesta del servidor
    debounceTime: 3000, // 3 segundos
    showNotifications: true,
    useLocalStorage: true,
    localStorageKey: `candidatura_draft_${props.candidatura?.id || 'new'}`
});

// Variable para rastrear si el watcher está activo
let autoSaveWatcher: any = null;

// Inicializar autoguardado cuando el componente se monta
onMounted(() => {
    // Solo activar autoguardado si es un borrador o está siendo rechazado
    const estadosEditables = ['borrador', 'rechazado'];
    const puedeAutoguardar = !props.candidatura || estadosEditables.includes(props.candidatura.estado);
    
    if (puedeAutoguardar) {
        // Intentar recuperar borrador guardado localmente
        if (!props.is_editing) {
            const draft = restoreDraft();
            if (draft && draft.formulario_data) {
                // Preguntar al usuario si quiere recuperar el borrador
                toast.info('Borrador recuperado', {
                    description: 'Se encontró un borrador guardado localmente. Se ha restaurado automáticamente.',
                    action: {
                        label: 'Descartar',
                        onClick: () => {
                            clearLocalStorage();
                            form.reset();
                            toast.success('Borrador descartado');
                        }
                    },
                    duration: 5000,
                });
                
                // Restaurar los datos del borrador
                Object.assign(form.formulario_data, draft.formulario_data);
            }
        }
        
        // Iniciar el watcher para autoguardado
        autoSaveWatcher = startWatching();
    }
});

// Limpiar cuando el componente se desmonta
onUnmounted(() => {
    if (autoSaveWatcher) {
        autoSaveWatcher(); // Detener el watcher
    }
    stopAutoSave(); // Cancelar cualquier autoguardado pendiente
});

// Función para guardar manualmente
const saveManually = async () => {
    if (isSaving.value) {
        toast.warning('Ya se está guardando...');
        return;
    }
    
    await saveNow();
    
    if (!hasError.value) {
        toast.success('Borrador guardado manualmente');
    }
};

// Verificar si el formulario está completo (solo validar campos visibles)
const isFormValid = computed(() => {
    const validationResults = camposVisibles.value.map(campo => {
        if (!campo.required) return { campo: campo.id, valid: true };
        
        const value = form.formulario_data[campo.id];
        let isValid = false;
        
        // Para checkboxes, verificar que haya al menos una opción seleccionada
        if (campo.type === 'checkbox') {
            isValid = Array.isArray(value) && value.length > 0;
        }
        // Para archivos, verificar si hay archivos nuevos o existentes
        else if (campo.type === 'file') {
            // Verificar si hay archivos en el valor actual del formulario
            if (Array.isArray(value) && value.length > 0) {
                isValid = true;
            }
            // También verificar si hay archivos pendientes de subir
            else if (pendingFiles.value[campo.id] && pendingFiles.value[campo.id].length > 0) {
                isValid = true;
            }
        }
        // Para convocatoria, verificar que tenga un ID válido
        else if (campo.type === 'convocatoria') {
            isValid = value !== null && value !== undefined && value !== '';
        }
        // Para disclaimer, verificar que esté aceptado
        else if (campo.type === 'disclaimer') {
            isValid = value && value.accepted === true;
        }
        // Para repeater, verificar que tenga el mínimo de elementos
        else if (campo.type === 'repeater') {
            const minItems = campo.repeaterConfig?.minItems || 0;
            isValid = Array.isArray(value) && value.length >= minItems;
        }
        // Para datepicker, verificar que tenga una fecha válida
        else if (campo.type === 'datepicker') {
            isValid = value !== null && value !== undefined && value !== '';
        }
        // Para otros tipos de campos
        else {
            isValid = value && value.toString().trim() !== '';
        }
        
        // Log para depuración
        // Campo inválido detectado - información disponible para debug si es necesario
        if (!isValid && campo.required) {
            // Debug info: campo.id, campo.type, value, pendingFiles, required
        }
        
        return { campo: campo.id, valid: isValid };
    });
    
    const allValid = validationResults.every(r => r.valid);
    // Debug: validationResults contiene info de validación si es necesario
    
    return allValid;
});

// Computed para contar campos llenos vs total
const progressInfo = computed(() => {
    const totalCampos = camposVisibles.value.length;
    let camposLlenos = 0;
    
    camposVisibles.value.forEach(campo => {
        const value = form.formulario_data[campo.id];
        let estaLleno = false;
        
        // Lógica similar a isFormValid pero para TODOS los campos (no solo requeridos)
        if (campo.type === 'checkbox') {
            estaLleno = Array.isArray(value) && value.length > 0;
        } else if (campo.type === 'file') {
            estaLleno = (Array.isArray(value) && value.length > 0) || 
                       (pendingFiles.value[campo.id] && pendingFiles.value[campo.id].length > 0);
        } else if (campo.type === 'convocatoria') {
            estaLleno = value !== null && value !== undefined && value !== '';
        } else if (campo.type === 'disclaimer') {
            estaLleno = value && value.accepted === true;
        } else if (campo.type === 'repeater') {
            estaLleno = Array.isArray(value) && value.length > 0;
        } else if (campo.type === 'datepicker') {
            estaLleno = value !== null && value !== undefined && value !== '';
        } else {
            estaLleno = value && value.toString().trim() !== '';
        }
        
        if (estaLleno) camposLlenos++;
    });
    
    return {
        llenos: camposLlenos,
        total: totalCampos,
        porcentaje: totalCampos > 0 ? Math.round((camposLlenos / totalCampos) * 100) : 0
    };
});

const pageTitle = computed(() => {
    return props.is_editing ? 'Editar Candidatura' : 'Crear Candidatura';
});

const submitButtonText = computed(() => {
    if (form.processing) return 'Guardando...';
    if (props.is_editing) {
        if (props.candidatura?.estado === 'rechazado') {
            return 'Reenviar Candidatura';
        } else if (props.candidatura?.estado === 'aprobado') {
            return 'Solicitar Revisión';
        } else {
            return 'Enviar Candidatura';
        }
    }
    return 'Crear Candidatura';
});

// Filtrar campos según estado de candidatura y condiciones
const camposVisibles = computed(() => {
    let campos = [...visibleFields.value]; // Usar campos visibles según condiciones
    
    // Si estamos editando, excluir campos de tipo 'convocatoria' SOLO si:
    // - La candidatura está en estado pendiente o aprobado (ya no se puede cambiar)
    // - En borrador o rechazado SÍ permitir editar convocatoria
    if (props.is_editing && props.candidatura) {
        const estadosNoEditablesConvocatoria = ['pendiente', 'aprobado'];
        if (estadosNoEditablesConvocatoria.includes(props.candidatura.estado)) {
            campos = campos.filter(campo => campo.type !== 'convocatoria');
        }
    }
    
    // Si está editando una candidatura aprobada, mostrar solo campos editables
    if (props.is_editing && props.candidatura?.estado === 'aprobado') {
        campos = campos.filter(campo => campo.editable === true);
    }
    
    return campos;
});

// Verificar si hay campos no editables ocultos
const hayCamposOcultos = computed(() => {
    if (!props.is_editing || !props.candidatura) {
        return false;
    }
    
    // Verificar si hay campos de convocatoria ocultos (solo en pendiente/aprobado)
    let hayConvocatoriaOculta = false;
    const estadosNoEditablesConvocatoria = ['pendiente', 'aprobado'];
    if (estadosNoEditablesConvocatoria.includes(props.candidatura.estado)) {
        hayConvocatoriaOculta = props.configuracion_campos.some(campo => campo.type === 'convocatoria');
    }
    
    // Si está editando una candidatura aprobada, también verificar campos no editables
    if (props.candidatura?.estado === 'aprobado') {
        const hayNoEditables = props.configuracion_campos.some(campo => !campo.editable && campo.type !== 'convocatoria');
        return hayConvocatoriaOculta || hayNoEditables;
    }
    
    return hayConvocatoriaOculta;
});

// Manejar selección de archivos con subida inmediata para autoguardado
const handleFilesSelected = async (fieldId: string, files: File[]) => {
    // Guardar temporalmente para validación
    pendingFiles.value[fieldId] = files;
    
    // Si hay archivos nuevos, subirlos inmediatamente para el autoguardado
    if (files && files.length > 0) {
        try {
            // Mostrar indicador de carga
            toast.info('Subiendo archivos...', {
                duration: 2000,
            });
            
            // Subir archivos inmediatamente
            const uploadedFiles = await uploadFiles(files, {
                module: 'candidaturas',
                fieldId: fieldId,
            });
            
            // Guardar las rutas de los archivos subidos
            const newPaths = uploadedFiles.map(f => f.path);
            
            // Si el campo ya tiene archivos, agregar los nuevos
            // Si no, crear un nuevo array
            if (Array.isArray(form.formulario_data[fieldId])) {
                // Reemplazar completamente con los nuevos archivos
                form.formulario_data[fieldId] = newPaths;
            } else {
                form.formulario_data[fieldId] = newPaths;
            }
            
            // Limpiar archivos pendientes ya que se subieron
            pendingFiles.value[fieldId] = [];
            
            toast.success('Archivos subidos correctamente', {
                duration: 2000,
            });
        } catch (error) {
            console.error('Error al subir archivos:', error);
            toast.error('Error al subir archivos', {
                description: 'Por favor, intente nuevamente',
                duration: 3000,
            });
            
            // En caso de error, mantener los archivos en pendingFiles
            // para que se puedan reintentar con el submit
        }
    } else {
        // Si no hay archivos (se eliminaron todos), limpiar el campo
        form.formulario_data[fieldId] = [];
    }
};

// Métodos
const handleSubmit = async () => {
    // Subir archivos pendientes antes de enviar el formulario
    // (Solo si quedaron archivos pendientes por algún error previo)
    for (const [fieldId, files] of Object.entries(pendingFiles.value)) {
        if (files && files.length > 0) {
            try {
                toast.info('Subiendo archivos pendientes...', {
                    duration: 2000,
                });
                
                const uploadedFiles = await uploadFiles(files, {
                    module: 'candidaturas',
                    fieldId: fieldId,
                });
                // Reemplazar el array completo en lugar de concatenar
                form.formulario_data[fieldId] = uploadedFiles.map(f => f.path);
                // Limpiar archivos pendientes después de subir
                pendingFiles.value[fieldId] = [];
            } catch (error) {
                console.error('Error al subir archivos:', error);
                toast.error('Error al subir archivos pendientes', {
                    description: 'No se pudo completar el envío',
                    duration: 3000,
                });
                return; // No continuar si hay error al subir archivos
            }
        }
    }
    
    // Limpiar datos antes de enviar: convertir cadenas vacías a null para campos opcionales
    const cleanedData = { ...form.formulario_data };
    props.configuracion_campos.forEach(campo => {
        const value = cleanedData[campo.id];
        
        // Solo procesar campos que no son requeridos
        if (!campo.required) {
            // Convertir cadenas vacías a null
            if (value === '' || value === undefined) {
                cleanedData[campo.id] = null;
            }
            // Para arrays vacíos en campos opcionales, dejar como null
            else if (Array.isArray(value) && value.length === 0 && campo.type !== 'checkbox' && campo.type !== 'file') {
                cleanedData[campo.id] = null;
            }
        }
    });
    
    // Actualizar los datos del formulario con los datos limpios
    form.formulario_data = cleanedData;
    
    // Detener autoguardado antes de enviar
    stopAutoSave();
    
    if (props.is_editing) {
        form.put(`/candidaturas/${props.candidatura!.id}`, {
            onSuccess: () => {
                // Limpiar localStorage al enviar exitosamente
                clearLocalStorage();
                toast.success('Candidatura actualizada exitosamente');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                // Reanudar autoguardado si hay error
                if (!props.candidatura || ['borrador', 'rechazado'].includes(props.candidatura.estado)) {
                    autoSaveWatcher = startWatching();
                }
            }
        });
    } else {
        form.post('/candidaturas', {
            onSuccess: () => {
                // Limpiar localStorage al crear exitosamente
                clearLocalStorage();
                toast.success('Candidatura creada exitosamente');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                // Reanudar autoguardado si hay error
                autoSaveWatcher = startWatching();
            }
        });
    }
};

// Función helper para verificar errores
const hasFieldError = (fieldId: string) => {
    return !!form.errors[`formulario_data.${fieldId}`];
};

// Computed para controlar visibilidad del botón borrador
const mostrarBotonBorrador = computed(() => {
    return !props.candidatura || ['borrador', 'rechazado'].includes(props.candidatura.estado);
});

// Función para alternar el sidebar haciendo clic en el botón existente
const toggleSidebar = () => {
    // Buscar el botón del sidebar en el DOM y hacer clic en él
    const sidebarTrigger = document.querySelector('[data-sidebar="trigger"]') as HTMLElement;
    if (sidebarTrigger) {
        sidebarTrigger.click();
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 pb-24">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
                    <p class="text-muted-foreground">
                        {{ is_editing 
                            ? 'Actualiza la información de tu candidatura' 
                            : 'Completa tu perfil de candidatura para participar en convocatorias'
                        }}
                    </p>
                </div>
                <Button variant="outline" @click="router.visit('/candidaturas')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </div>

            <!-- Indicador de autoguardado -->
            <div v-if="!candidatura || ['borrador', 'rechazado'].includes(candidatura.estado)" 
                 class="flex items-center justify-end gap-2 text-sm text-muted-foreground">
                <!-- Estado guardando -->
                <div v-if="isSaving" class="flex items-center gap-1.5">
                    <Clock class="h-4 w-4 animate-spin" />
                    <span>Guardando automáticamente...</span>
                </div>
                <!-- Estado guardado -->
                <div v-else-if="hasSaved && !hasError" class="flex items-center gap-1.5">
                    <CheckCircle class="h-4 w-4 text-green-600" />
                    <span>Guardado automáticamente a las {{ autoSaveState.lastSaved?.toLocaleTimeString() }}</span>
                </div>
                <!-- Estado error -->
                <div v-else-if="hasError" class="flex items-center gap-1.5 text-amber-600">
                    <AlertCircle class="h-4 w-4" />
                    <span>Guardado localmente (sin conexión)</span>
                </div>
                <!-- Estado inicial -->
                <div v-else class="flex items-center gap-1.5">
                    <Clock class="h-4 w-4" />
                    <span>Autoguardado activado</span>
                </div>
            </div>

            <!-- Información de estado (solo en edición) -->
            <Card v-if="is_editing && candidatura?.comentarios_admin" class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20">
                <CardHeader>
                    <CardTitle class="text-blue-800 dark:text-blue-200 flex items-center gap-2">
                        <AlertCircle class="h-5 w-5" />
                        Comentarios de la comisión
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-blue-700 dark:text-blue-300">{{ candidatura.comentarios_admin }}</p>
                </CardContent>
            </Card>

            <!-- Formulario -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Información de Candidatura
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Aviso para candidaturas aprobadas -->
                        <div v-if="is_editing && candidatura?.estado === 'aprobado'" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <AlertCircle class="h-5 w-5 text-blue-600 mt-0.5" />
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium">Candidatura Aprobada - Edición Limitada</p>
                                    <p class="mt-1">Solo puedes editar los campos marcados como editables. Al hacer cambios, tu candidatura volverá a revisión administrativa.</p>
                                    <p v-if="hayCamposOcultos" class="mt-1 text-blue-600">
                                        Algunos campos están ocultos porque no son editables en candidaturas aprobadas o no pueden modificarse después del envío.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <TransitionGroup name="fade-field" tag="div" class="space-y-6">
                            <div
                                v-for="campo in camposVisibles"
                                :key="campo.id"
                                class="space-y-2"
                            >
                            <!-- Label del campo -->
                            <Label :for="campo.id" class="flex items-center gap-1">
                                {{ campo.title }}
                                <span v-if="campo.required" class="text-red-500">*</span>
                            </Label>
                            
                            <!-- Descripción del campo -->
                            <p v-if="campo.description" class="text-sm text-muted-foreground">
                                {{ campo.description }}
                            </p>

                            <!-- Campo Text -->
                            <Input
                                v-if="campo.type === 'text' || campo.type === 'email'"
                                :id="campo.id"
                                :type="campo.type"
                                v-model="form.formulario_data[campo.id]"
                                :placeholder="campo.placeholder || ''"
                                :class="{ 'border-destructive': hasFieldError(campo.id) }"
                            />

                            <!-- Campo Textarea -->
                            <Textarea
                                v-else-if="campo.type === 'textarea'"
                                :id="campo.id"
                                v-model="form.formulario_data[campo.id]"
                                :placeholder="campo.placeholder || ''"
                                :rows="4"
                                :class="{ 'border-destructive': hasFieldError(campo.id) }"
                            />

                            <!-- Campo Number -->
                            <Input
                                v-else-if="campo.type === 'number'"
                                :id="campo.id"
                                type="number"
                                v-model="form.formulario_data[campo.id]"
                                :placeholder="campo.placeholder || ''"
                                :class="{ 'border-destructive': hasFieldError(campo.id) }"
                            />

                            <!-- Campo Date -->
                            <Input
                                v-else-if="campo.type === 'date'"
                                :id="campo.id"
                                type="date"
                                v-model="form.formulario_data[campo.id]"
                                :class="{ 'border-destructive': hasFieldError(campo.id) }"
                            />

                            <!-- Campo Select -->
                            <Select
                                v-else-if="campo.type === 'select'"
                                v-model="form.formulario_data[campo.id]"
                            >
                                <SelectTrigger :class="{ 'border-destructive': hasFieldError(campo.id) }">
                                    <SelectValue :placeholder="campo.placeholder || 'Seleccionar...'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in campo.options"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Campo Radio -->
                            <div v-else-if="campo.type === 'radio'" class="space-y-2">
                                <div
                                    v-for="option in campo.options"
                                    :key="option"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        :id="`${campo.id}_${option}`"
                                        :name="campo.id"
                                        type="radio"
                                        :value="option"
                                        v-model="form.formulario_data[campo.id]"
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                    />
                                    <Label :for="`${campo.id}_${option}`" class="text-sm font-normal">
                                        {{ option }}
                                    </Label>
                                </div>
                            </div>

                            <!-- Campo Checkbox -->
                            <div v-else-if="campo.type === 'checkbox'" class="space-y-2">
                                <div
                                    v-for="option in campo.options"
                                    :key="option"
                                    class="flex items-center space-x-2"
                                >
                                    <Checkbox
                                        :id="`${campo.id}_${option}`"
                                        :checked="form.formulario_data[campo.id].includes(option)"
                                        @update:checked="(checked) => {
                                            if (checked) {
                                                if (!form.formulario_data[campo.id].includes(option)) {
                                                    form.formulario_data[campo.id].push(option);
                                                }
                                            } else {
                                                const index = form.formulario_data[campo.id].indexOf(option);
                                                if (index > -1) {
                                                    form.formulario_data[campo.id].splice(index, 1);
                                                }
                                            }
                                        }"
                                    />
                                    <Label :for="`${campo.id}_${option}`" class="text-sm font-normal">
                                        {{ option }}
                                    </Label>
                                </div>
                            </div>

                            <!-- Campo File -->
                            <div v-else-if="campo.type === 'file'">
                                <FileUploadField
                                    v-model="form.formulario_data[campo.id]"
                                    @filesSelected="(files) => handleFilesSelected(campo.id, files)"
                                    :label="''"
                                    :description="''"
                                    :required="campo.required"
                                    :multiple="campo.fileConfig?.multiple || false"
                                    :max-files="campo.fileConfig?.maxFiles || 5"
                                    :max-file-size="campo.fileConfig?.maxFileSize || 10"
                                    :accept="campo.fileConfig?.accept || '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif'"
                                    :error="form.errors[`formulario_data.${campo.id}`]"
                                    :disabled="form.processing"
                                    module="candidaturas"
                                    :field-id="campo.id"
                                    :auto-upload="false"
                                />
                            </div>

                            <!-- Campo Convocatoria -->
                            <div v-else-if="campo.type === 'convocatoria'">
                                <ConvocatoriaSelector
                                    v-model="form.formulario_data[campo.id]"
                                    :required="campo.required"
                                    :disabled="form.processing || (is_editing && candidatura?.estado !== 'borrador')"
                                    :filtrar-por-ubicacion="campo.convocatoriaConfig?.filtrarPorUbicacion ?? true"
                                    :show-postulacion-warning="true"
                                />
                                <div v-if="hasFieldError(campo.id)" class="text-red-500 text-sm mt-1">
                                    {{ form.errors[`formulario_data.${campo.id}`] }}
                                </div>
                            </div>

                            <!-- Campo DatePicker -->
                            <div v-else-if="campo.type === 'datepicker'">
                                <DatePickerField
                                    :model-value="form.formulario_data[campo.id] || null"
                                    @update:model-value="(value) => form.formulario_data[campo.id] = value"
                                    :label="''"
                                    :description="''"
                                    :required="campo.required"
                                    :disabled="form.processing"
                                    :error="form.errors[`formulario_data.${campo.id}`]"
                                    :min-date="campo.datepickerConfig?.minDate"
                                    :max-date="campo.datepickerConfig?.maxDate"
                                    :format="campo.datepickerConfig?.format || 'DD/MM/YYYY'"
                                    :allow-past-dates="campo.datepickerConfig?.allowPastDates ?? true"
                                    :allow-future-dates="campo.datepickerConfig?.allowFutureDates ?? true"
                                />
                            </div>

                            <!-- Campo Disclaimer -->
                            <div v-else-if="campo.type === 'disclaimer'">
                                <DisclaimerField
                                    :model-value="form.formulario_data[campo.id] || null"
                                    @update:model-value="(value) => form.formulario_data[campo.id] = value"
                                    :label="''"
                                    :description="''"
                                    :required="campo.required"
                                    :disabled="form.processing"
                                    :error="form.errors[`formulario_data.${campo.id}`]"
                                    :disclaimer-text="campo.disclaimerConfig?.disclaimerText || ''"
                                    :modal-title="campo.disclaimerConfig?.modalTitle || 'Términos y Condiciones'"
                                    :accept-button-text="campo.disclaimerConfig?.acceptButtonText || 'Acepto'"
                                    :decline-button-text="campo.disclaimerConfig?.declineButtonText || 'No acepto'"
                                />
                            </div>

                            <!-- Campo Repeater -->
                            <div v-else-if="campo.type === 'repeater'">
                                <RepeaterField
                                    :model-value="form.formulario_data[campo.id] || []"
                                    @update:model-value="(value) => form.formulario_data[campo.id] = value"
                                    :label="''"
                                    :description="''"
                                    :required="campo.required"
                                    :disabled="form.processing"
                                    :error="form.errors[`formulario_data.${campo.id}`]"
                                    :min-items="campo.repeaterConfig?.minItems || 0"
                                    :max-items="campo.repeaterConfig?.maxItems || 10"
                                    :item-name="campo.repeaterConfig?.itemName || 'Elemento'"
                                    :add-button-text="campo.repeaterConfig?.addButtonText || 'Agregar elemento'"
                                    :remove-button-text="campo.repeaterConfig?.removeButtonText || 'Eliminar'"
                                    :fields="campo.repeaterConfig?.fields || []"
                                />
                            </div>

                            <!-- Mensaje de error -->
                            <p v-if="hasFieldError(campo.id)" class="text-sm text-destructive">
                                {{ form.errors[`formulario_data.${campo.id}`] }}
                            </p>
                            </div>
                        </TransitionGroup>

                        <!-- Errores generales -->
                        <div v-if="form.errors.general" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-800 text-sm">{{ form.errors.general }}</p>
                        </div>

                        <!-- Espacio para la barra flotante -->
                        <div class="h-4"></div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <!-- Barra de acciones flotante con glassmorphing -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="translate-y-4 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-4 opacity-0"
            >
                <div v-if="true" class="fixed bottom-0 left-0 right-0 z-50 px-2 sm:px-4 pb-2 sm:pb-4">
                    <div class="mx-auto max-w-7xl">
                        <div class="backdrop-blur-lg bg-tertiary-60 dark:bg-gray-900/80 border border-gray-200/50 dark:border-gray-700/50 rounded-xl sm:rounded-2xl shadow-2xl p-3 sm:p-4">
                            <!-- Diseño responsive: grid en móvil, flex en desktop -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                                <!-- Botón Ocultar Sidebar - solo en desktop -->
                                <div class="hidden sm:flex items-center">
                                    <Button 
                                        variant="outline" 
                                        type="button"
                                        @click="toggleSidebar"
                                        class="backdrop-blur-sm flex-shrink-0 text-xs sm:text-sm py-2 px-3"
                                    >
                                        <PanelLeft class="h-4 w-4" />
                                        <span class="ml-2">Ocultar sidebar</span>
                                    </Button>
                                </div>
                                
                                <!-- Barra de progreso - ancho completo en móvil, centrado en desktop -->
                                <div class="flex-1 sm:max-w-xs lg:max-w-sm mx-0 sm:mx-4">
                                    <div class="flex items-center gap-3">
                                        <Progress :modelValue="progressInfo.porcentaje" class="flex-1" />
                                        <span class="text-xs sm:text-sm text-muted-foreground whitespace-nowrap font-medium">
                                            {{ progressInfo.llenos }} / {{ progressInfo.total }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1 text-center hidden sm:block">
                                        Campos completados
                                    </p>
                                </div>
                                
                                <!-- Grupo acciones principales -->
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <!-- Botón Guardar Borrador (condicional) -->
                                    <Button 
                                        v-if="mostrarBotonBorrador"
                                        @click="saveManually"
                                        :disabled="isSaving"
                                        variant="outline"
                                        class="backdrop-blur-sm flex-1 sm:flex-none text-xs sm:text-sm py-2 px-3"
                                    >
                                        <template v-if="isSaving">
                                            <Clock class="mr-2 h-4 w-4 animate-spin" />
                                            Guardando...
                                        </template>
                                        <template v-else>
                                            <Save class="mr-2 h-4 w-4" />
                                            Guardar borrador
                                        </template>
                                    </Button>
                                    
                                    <!-- Botón Enviar -->
                                    <Button 
                                        @click="handleSubmit"
                                        :disabled="!isFormValid || form.processing"
                                        class="bg-green-600 hover:bg-green-700 text-white border-green-600 hover:border-green-700 disabled:bg-gray-400 disabled:border-gray-400 flex-1 sm:flex-none text-xs sm:text-sm py-2 px-3"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                        <span class="ml-2 whitespace-nowrap">{{ submitButtonText }}</span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* Transiciones suaves para mostrar/ocultar campos */
.fade-field-enter-active,
.fade-field-leave-active {
    transition: all 0.3s ease;
}

.fade-field-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-field-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.fade-field-move {
    transition: transform 0.3s ease;
}
</style>