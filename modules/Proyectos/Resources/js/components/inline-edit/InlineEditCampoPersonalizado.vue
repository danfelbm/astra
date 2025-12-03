<script setup lang="ts">
/**
 * InlineEditCampoPersonalizado - Componente para edición inline de campos personalizados
 * Renderiza el editor apropiado según el tipo de campo
 */
import { ref, computed, watch, nextTick } from 'vue';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@modules/Core/Resources/js/components/ui/radio-group';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import InlineEditWrapper from './InlineEditWrapper.vue';
import { format, parseISO, isValid } from 'date-fns';
import { es } from 'date-fns/locale';
import { FileText, Eye, Download, X, Upload } from 'lucide-vue-next';

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string; // text, number, date, select, checkbox, textarea, radio, file, email, url, phone
    es_requerido: boolean;
    descripcion?: string;
    opciones?: Array<{ value: string; label: string }> | string[];
}

interface Props {
    campo: CampoPersonalizado;
    modelValue: any;
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    canEdit: true,
    loading: false,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: any];
    'save': [campoId: number, value: any];
}>();

// Referencia al wrapper
const wrapperRef = ref<InstanceType<typeof InlineEditWrapper> | null>(null);
// Referencia al componente Input/Textarea (usamos $el para acceder al elemento nativo)
const inputRef = ref<InstanceType<typeof Input> | InstanceType<typeof Textarea> | null>(null);

// Valor temporal durante edición
const tempValue = ref(props.modelValue);

// Función para convertir valor a boolean (manejar "0", "1", true, false, etc.)
const toBoolean = (val: any): boolean => {
    if (val === true || val === 1 || val === '1' || val === 'true') return true;
    return false;
};

// Estado local del checkbox (para feedback visual inmediato)
const localChecked = ref(toBoolean(props.modelValue));

// Estado para manejar archivo
const quiereReemplazar = ref(false);
const archivoSeleccionado = ref<File | null>(null);

// Estado para rastrear si estamos esperando respuesta de guardado
const pendingSave = ref(false);

// Computed para verificar si hay archivo existente
const archivoExistente = computed(() => {
    if (props.campo.tipo === 'file' && typeof props.modelValue === 'string' && props.modelValue) {
        return props.modelValue;
    }
    return null;
});

// Normalizar opciones a formato {value, label}
const normalizedOptions = computed(() => {
    if (!props.campo.opciones) return [];
    return props.campo.opciones.map(opt => {
        if (typeof opt === 'string') {
            return { value: opt, label: opt };
        }
        return opt;
    });
});

// Determinar el tipo de input
const inputType = computed(() => {
    switch (props.campo.tipo) {
        case 'email': return 'email';
        case 'url': return 'url';
        case 'phone': return 'tel';
        case 'number': return 'number';
        case 'date': return 'date';
        default: return 'text';
    }
});

// Formatear valor para display
const displayValue = computed(() => {
    const val = props.modelValue;

    if (val === null || val === undefined || val === '') {
        return '';
    }

    switch (props.campo.tipo) {
        case 'checkbox':
            return toBoolean(val) ? 'Sí' : 'No';

        case 'date':
            try {
                const date = parseISO(val);
                if (isValid(date)) {
                    return format(date, 'dd/MM/yyyy', { locale: es });
                }
            } catch {
                // Ignorar
            }
            return val;

        case 'select':
        case 'radio':
            const option = normalizedOptions.value.find(opt => opt.value === val);
            return option?.label || val;

        default:
            return String(val);
    }
});

// Sincronizar cuando cambia el valor externo
watch(() => props.modelValue, (newVal) => {
    // Si estábamos esperando respuesta de guardado y el valor cambió, cerrar edición
    if (pendingSave.value) {
        pendingSave.value = false;
        wrapperRef.value?.closeEditing();
        tempValue.value = newVal;
    } else if (!wrapperRef.value?.isEditing) {
        tempValue.value = newVal;
    }
    // Sincronizar checkbox local con conversión correcta
    if (props.campo.tipo === 'checkbox') {
        localChecked.value = toBoolean(newVal);
    }
    // Limpiar archivo seleccionado después de upload exitoso
    if (props.campo.tipo === 'file' && archivoSeleccionado.value && typeof newVal === 'string' && newVal) {
        archivoSeleccionado.value = null;
        quiereReemplazar.value = false;
    }
});

// Cerrar edición cuando loading pasa de true a false (API terminó)
watch(() => props.loading, (newLoading, oldLoading) => {
    if (oldLoading && !newLoading && pendingSave.value) {
        // API terminó, cerrar edición
        pendingSave.value = false;
        wrapperRef.value?.closeEditing();
    }
});

// Guardar valor
const save = () => {
    // Solo emitir si cambió el valor
    if (tempValue.value !== props.modelValue) {
        pendingSave.value = true;
        emit('save', props.campo.id, tempValue.value);
    } else {
        wrapperRef.value?.closeEditing();
    }
};

// Manejar inicio de edición
const handleEditStart = () => {
    tempValue.value = props.modelValue;

    // Enfocar input después de renderizar (accedemos al $el del componente Vue)
    nextTick(() => {
        const el = inputRef.value?.$el as HTMLInputElement | HTMLTextAreaElement | undefined;
        if (el) {
            el.focus();
            if (el instanceof HTMLInputElement && props.campo.tipo === 'text') {
                el.select();
            }
        }
    });
};

// Manejar cancelación
const handleEditCancel = () => {
    tempValue.value = props.modelValue;
};

// Manejar Enter (para inputs simples)
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter' && props.campo.tipo !== 'textarea') {
        e.preventDefault();
        save();
    }
};

// Manejar blur (auto-save para inputs simples)
const handleBlur = (e: FocusEvent) => {
    const relatedTarget = e.relatedTarget as HTMLElement;
    if (relatedTarget?.closest('.inline-edit-wrapper')) {
        return;
    }
    save();
};

// Manejar cambio de checkbox (actualiza UI optimistamente)
const handleCheckboxChange = (checked: boolean) => {
    // Actualizar estado local para feedback visual inmediato
    localChecked.value = checked;
    tempValue.value = checked;
    // Emitir save
    emit('save', props.campo.id, checked);
};

// Manejar cambio de select
const handleSelectChange = (value: string) => {
    tempValue.value = value;
    save();
};

// Manejar cambio de radio (auto-save igual que select)
const handleRadioChange = (value: string) => {
    tempValue.value = value;
    save();
};

// Funciones para manejo de archivos
const verArchivo = () => {
    if (archivoExistente.value) {
        window.open(`/storage/campos-personalizados/${archivoExistente.value}`, '_blank');
    }
};

const descargarArchivo = () => {
    if (archivoExistente.value) {
        const link = document.createElement('a');
        link.href = `/storage/campos-personalizados/${archivoExistente.value}`;
        link.download = archivoExistente.value;
        link.click();
    }
};

const cancelarReemplazo = () => {
    quiereReemplazar.value = false;
    archivoSeleccionado.value = null;
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        archivoSeleccionado.value = target.files[0];
    }
};

// Subir archivo seleccionado
const uploadFile = () => {
    if (!archivoSeleccionado.value) return;
    pendingSave.value = true;
    emit('save', props.campo.id, archivoSeleccionado.value);
};

// Cerrar edición después de guardar exitoso
const closeAfterSave = () => {
    wrapperRef.value?.closeEditing();
};

// Determinar si usar botón de confirmación
const showConfirmButton = computed(() => {
    return props.campo.tipo === 'textarea';
});

defineExpose({
    closeAfterSave,
});
</script>

<template>
    <!-- Checkbox tiene comportamiento especial (no usa wrapper) -->
    <div v-if="campo.tipo === 'checkbox'" class="group flex items-center gap-2">
        <Checkbox
            :checked="localChecked"
            :disabled="!canEdit || disabled || loading"
            @update:checked="handleCheckboxChange"
        />
        <span class="text-sm">{{ localChecked ? 'Sí' : 'No' }}</span>
    </div>

    <!-- Tipo file: manejo completo de archivos -->
    <div v-else-if="campo.tipo === 'file'" class="space-y-2">
        <!-- Mostrar archivo existente si hay uno -->
        <div v-if="archivoExistente && !quiereReemplazar" class="flex items-center gap-2 p-2 rounded-lg border bg-muted/50">
            <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <span class="text-sm flex-1 truncate">{{ archivoExistente }}</span>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 w-7 p-0"
                    @click="verArchivo"
                    title="Ver archivo"
                >
                    <Eye class="h-3.5 w-3.5" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 w-7 p-0"
                    @click="descargarArchivo"
                    title="Descargar archivo"
                >
                    <Download class="h-3.5 w-3.5" />
                </Button>
                <Button
                    v-if="canEdit && !disabled && !loading"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click="quiereReemplazar = true"
                >
                    Reemplazar
                </Button>
            </div>
        </div>

        <!-- Input para subir nuevo archivo -->
        <div v-if="!archivoExistente || quiereReemplazar">
            <div class="flex items-center gap-2">
                <Input
                    type="file"
                    :disabled="!canEdit || disabled || loading"
                    class="flex-1 h-8 text-sm"
                    @change="handleFileChange"
                />
                <Button
                    v-if="archivoSeleccionado"
                    type="button"
                    variant="default"
                    size="sm"
                    class="h-8"
                    :disabled="loading"
                    @click="uploadFile"
                >
                    <Upload v-if="!loading" class="h-4 w-4 mr-1" />
                    <span v-if="loading" class="flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Subiendo...
                    </span>
                    <span v-else>Subir</span>
                </Button>
                <Button
                    v-if="quiereReemplazar && !archivoSeleccionado && !loading"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0"
                    @click="cancelarReemplazo"
                    title="Cancelar"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
            <p v-if="archivoSeleccionado" class="text-xs text-muted-foreground mt-1">
                Archivo seleccionado: {{ archivoSeleccionado.name }}
            </p>
            <p v-else-if="quiereReemplazar" class="text-xs text-muted-foreground mt-1">
                Selecciona un nuevo archivo para reemplazar el actual
            </p>
            <p v-else-if="!archivoExistente" class="text-xs text-muted-foreground mt-1">
                Selecciona un archivo para adjuntar
            </p>
        </div>
    </div>

    <!-- Otros tipos usan el wrapper -->
    <InlineEditWrapper
        v-else
        ref="wrapperRef"
        :display-value="displayValue"
        :can-edit="canEdit"
        :loading="loading"
        :disabled="disabled"
        :label="campo.nombre"
        :placeholder="`Sin ${campo.nombre.toLowerCase()}`"
        :show-confirm-button="showConfirmButton"
        @edit-start="handleEditStart"
        @edit-cancel="handleEditCancel"
        @save="save"
    >
        <template #edit>
            <!-- Select -->
            <Select
                v-if="campo.tipo === 'select'"
                :model-value="tempValue || ''"
                :disabled="loading"
                @update:model-value="handleSelectChange"
            >
                <SelectTrigger class="h-8 w-auto min-w-[150px]">
                    <SelectValue placeholder="Seleccionar..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in normalizedOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Radio buttons -->
            <RadioGroup
                v-else-if="campo.tipo === 'radio'"
                :model-value="tempValue || ''"
                :disabled="loading"
                class="flex flex-col gap-2"
                @update:model-value="handleRadioChange"
            >
                <div
                    v-for="option in normalizedOptions"
                    :key="option.value"
                    class="flex items-center space-x-2"
                >
                    <RadioGroupItem
                        :value="option.value"
                        :id="`campo-${campo.id}-${option.value}`"
                    />
                    <Label
                        :for="`campo-${campo.id}-${option.value}`"
                        class="text-sm font-normal cursor-pointer"
                    >
                        {{ option.label }}
                    </Label>
                </div>
            </RadioGroup>

            <!-- Textarea -->
            <Textarea
                v-else-if="campo.tipo === 'textarea'"
                ref="inputRef"
                v-model="tempValue"
                :disabled="loading"
                rows="3"
                class="min-h-[80px] resize-y"
            />

            <!-- Input (text, number, date, email, url, phone) -->
            <Input
                v-else
                ref="inputRef"
                v-model="tempValue"
                :type="inputType"
                :disabled="loading"
                class="h-8"
                @keydown="handleKeydown"
                @blur="handleBlur"
            />
        </template>
    </InlineEditWrapper>
</template>
