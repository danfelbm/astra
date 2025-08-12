<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import PerfilCandidaturaField from './PerfilCandidaturaField.vue';
import CandidatosVotacionField from './CandidatosVotacionField.vue';
import ConvocatoriaVotacionField from './ConvocatoriaVotacionField.vue';
import FileUploadField from './FileUploadField.vue';
import DatePickerField from './DatePickerField.vue';
import DisclaimerField from './DisclaimerField.vue';
import RepeaterField from './RepeaterField.vue';
import type { FormField } from '@/types/forms';
import { computed, watch, ref } from 'vue';
import { useConditionalFields } from '@/composables/useConditionalFields';

interface CandidaturaAprobada {
    id: number;
    version: number;
    fecha_aprobacion: string;
    resumen: string;
}

interface CandidatoElegible {
    id: number;
    name: string;
    email?: string;
    cargo?: string;
    territorio?: string;
    departamento?: string;
    municipio?: string;
    localidad?: string;
}

interface Props {
    fields: FormField[];
    modelValue: Record<string, any>;
    candidaturasAprobadas?: CandidaturaAprobada[];
    candidaturaSeleccionada?: number | null;
    candidatosElegibles?: Record<string, CandidatoElegible[]>; // Para votaciones
    errors?: Record<string, string>;
    disabled?: boolean;
    title?: string;
    description?: string;
    context?: 'postulacion' | 'votacion'; // Contexto del formulario
    files?: Record<string, File[]>; // Archivos seleccionados para subir
}

interface Emits {
    (e: 'update:modelValue', value: Record<string, any>): void;
    (e: 'update:candidaturaSeleccionada', value: number | null): void;
    (e: 'filesSelected', fieldId: string, files: File[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    candidaturasAprobadas: () => [],
    candidatosElegibles: () => ({}),
    errors: () => ({}),
    disabled: false,
    title: 'Formulario de Postulación',
    description: 'Completa los siguientes campos para enviar tu postulación',
    context: 'postulacion',
});

const emit = defineEmits<Emits>();

const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const candidatura = computed({
    get: () => props.candidaturaSeleccionada,
    set: (value) => emit('update:candidaturaSeleccionada', value)
});

const tienePerfilCandidatura = computed(() => {
    return props.fields.some(field => field.type === 'perfil_candidatura');
});

// Usar el composable de campos condicionales
const fieldsRef = ref(props.fields);
watch(() => props.fields, (newFields) => {
    fieldsRef.value = newFields;
}, { deep: true });

const {
    visibleFields,
    shouldShowField,
    clearHiddenFieldValues
} = useConditionalFields(fieldsRef, formData);

// Limpiar valores de campos ocultos cuando cambian las condiciones
watch(visibleFields, () => {
    clearHiddenFieldValues();
});

const updateField = (fieldId: string, value: any) => {
    const newData = { ...formData.value };
    newData[fieldId] = value;
    formData.value = newData;
};

const handleCheckboxChange = (fieldId: string, option: string, checked: boolean) => {
    const currentValue = formData.value[fieldId] || [];
    let newValue;
    
    if (checked) {
        newValue = [...currentValue, option];
    } else {
        newValue = currentValue.filter((item: string) => item !== option);
    }
    
    updateField(fieldId, newValue);
};

// Validar campos requeridos (solo considerar campos visibles)
const camposRequeridos = computed(() => {
    return visibleFields.value.filter(field => field.required).map(field => field.id);
});

const camposIncompletos = computed(() => {
    return camposRequeridos.value.filter(fieldId => {
        // Caso especial para perfil_candidatura
        const field = visibleFields.value.find(f => f.id === fieldId);
        if (field?.type === 'perfil_candidatura') {
            // Para perfil_candidatura, verificar candidaturaSeleccionada
            return !props.candidaturaSeleccionada;
        }
        
        // Para otros campos, verificar en formData
        const value = formData.value[fieldId];
        return !value || (Array.isArray(value) && value.length === 0);
    });
});

// Validar perfil candidatura si es requerido
const perfilCandidaturaRequerido = computed(() => {
    const perfilField = props.fields.find(field => field.type === 'perfil_candidatura');
    return perfilField?.required || false;
});

const formularioCompleto = computed(() => {
    // Usar la misma lógica que camposIncompletos ya corregida
    return camposIncompletos.value.length === 0;
});

defineExpose({
    formularioCompleto,
    camposIncompletos,
    perfilCandidaturaRequerido
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header del formulario -->
        <div v-if="title || description">
            <h3 class="text-lg font-semibold">{{ title }}</h3>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <!-- Campos del formulario -->
        <div class="space-y-4">
            <TransitionGroup name="fade-field">
                <template v-for="field in visibleFields" :key="field.id">
                <!-- Campo especial: Convocatoria (nuevo para votaciones) -->
                <div v-if="field.type === 'convocatoria' && context === 'votacion'">
                    <ConvocatoriaVotacionField
                        v-model="formData[field.id]"
                        :candidatos="candidatosElegibles[field.id]?.candidatos || []"
                        :convocatoria-nombre="candidatosElegibles[field.id]?.convocatoria?.nombre"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :multiple="field.convocatoriaConfig?.multiple || false"
                        :mostrar-voto-blanco="field.convocatoriaConfig?.mostrarVotoBlanco ?? true"
                        :error="errors[field.id]"
                        :disabled="disabled"
                    />
                </div>

                <!-- Campo especial: Perfil de Candidatura (deprecated - solo para compatibilidad) -->
                <div v-else-if="field.type === 'perfil_candidatura'">
                    <!-- En contexto de postulación: mostrar candidaturas del usuario actual -->
                    <PerfilCandidaturaField
                        v-if="context === 'postulacion'"
                        v-model="candidatura"
                        :candidaturas-aprobadas="candidaturasAprobadas"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :error="errors['candidatura_id']"
                        :disabled="disabled"
                    />
                    <!-- En contexto de votación: mostrar candidatos elegibles (deprecated) -->
                    <CandidatosVotacionField
                        v-else-if="context === 'votacion'"
                        v-model="formData[field.id]"
                        :candidatos-elegibles="candidatosElegibles[field.id] || []"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :multiple="field.perfilCandidaturaConfig?.multiple || false"
                        :mostrar-voto-blanco="field.perfilCandidaturaConfig?.mostrarVotoBlanco ?? true"
                        :error="errors[field.id]"
                        :disabled="disabled"
                    />
                </div>

                <!-- Campo de texto -->
                <div v-else-if="field.type === 'text'">
                    <Label :for="field.id" class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <Input
                        :id="field.id"
                        :model-value="formData[field.id] || ''"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :placeholder="`Ingresa ${field.title.toLowerCase()}`"
                        :disabled="disabled"
                        :class="[errors[`formulario_data.${field.id}`] ? 'border-red-300' : '']"
                    />
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de texto largo -->
                <div v-else-if="field.type === 'textarea'">
                    <Label :for="field.id" class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <Textarea
                        :id="field.id"
                        :model-value="formData[field.id] || ''"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :placeholder="`Describe ${field.title.toLowerCase()}`"
                        :disabled="disabled"
                        rows="4"
                        :class="[errors[`formulario_data.${field.id}`] ? 'border-red-300' : '']"
                    />
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de número -->
                <div v-else-if="field.type === 'number'">
                    <Label :for="field.id" class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <Input
                        :id="field.id"
                        type="number"
                        :model-value="formData[field.id] || ''"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :disabled="disabled"
                        :min="field.numberConfig?.min"
                        :max="field.numberConfig?.max"
                        :step="field.numberConfig?.step || 1"
                        :placeholder="field.placeholder || ''"
                        :class="[errors[`formulario_data.${field.id}`] ? 'border-red-300' : '']"
                    />
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de email -->
                <div v-else-if="field.type === 'email'">
                    <Label :for="field.id" class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <Input
                        :id="field.id"
                        type="email"
                        :model-value="formData[field.id] || ''"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :placeholder="'ejemplo@correo.com'"
                        :disabled="disabled"
                        :class="[errors[`formulario_data.${field.id}`] ? 'border-red-300' : '']"
                    />
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de fecha -->
                <div v-else-if="field.type === 'date'">
                    <Label :for="field.id" class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <Input
                        :id="field.id"
                        type="date"
                        :model-value="formData[field.id] || ''"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :disabled="disabled"
                        :class="[errors[`formulario_data.${field.id}`] ? 'border-red-300' : '']"
                    />
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de selección -->
                <div v-else-if="field.type === 'select'">
                    <Label :for="field.id" class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <Select
                        :model-value="formData[field.id] || ''"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :disabled="disabled"
                    >
                        <SelectTrigger :class="[errors[`formulario_data.${field.id}`] ? 'border-red-300' : '']">
                            <SelectValue :placeholder="`Selecciona ${field.title.toLowerCase()}`" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in field.options"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de radio -->
                <div v-else-if="field.type === 'radio'">
                    <Label class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="option in field.options"
                            :key="option"
                            class="flex items-center space-x-2"
                        >
                            <input
                                :id="`${field.id}-${option}`"
                                type="radio"
                                :name="field.id"
                                :value="option"
                                :checked="formData[field.id] === option"
                                @change="() => updateField(field.id, option)"
                                :disabled="disabled"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <Label :for="`${field.id}-${option}`" class="text-sm">
                                {{ option }}
                            </Label>
                        </div>
                    </div>
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de checkbox -->
                <div v-else-if="field.type === 'checkbox'">
                    <Label class="text-sm font-medium">
                        {{ field.title }}
                        <span v-if="field.required" class="text-red-500 ml-1">*</span>
                    </Label>
                    <p v-if="field.description" class="text-sm text-muted-foreground mb-2">
                        {{ field.description }}
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="option in field.options"
                            :key="option"
                            class="flex items-center space-x-2"
                        >
                            <Checkbox
                                :id="`${field.id}-${option}`"
                                :checked="(formData[field.id] || []).includes(option)"
                                @update:checked="(checked) => handleCheckboxChange(field.id, option, checked)"
                                :disabled="disabled"
                            />
                            <Label :for="`${field.id}-${option}`" class="text-sm">
                                {{ option }}
                            </Label>
                        </div>
                    </div>
                    <p v-if="errors[`formulario_data.${field.id}`]" class="text-sm text-red-600 mt-1">
                        {{ errors[`formulario_data.${field.id}`] }}
                    </p>
                </div>

                <!-- Campo de archivo -->
                <div v-else-if="field.type === 'file'">
                    <FileUploadField
                        :model-value="formData[field.id] || []"
                        @update:model-value="(value) => updateField(field.id, value)"
                        @filesSelected="(files) => emit('filesSelected', field.id, files)"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :multiple="field.fileConfig?.multiple || false"
                        :max-files="field.fileConfig?.maxFiles || 5"
                        :max-file-size="field.fileConfig?.maxFileSize || 10"
                        :accept="field.fileConfig?.accept || '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif'"
                        :error="errors[`formulario_data.${field.id}`]"
                        :disabled="disabled"
                    />
                </div>

                <!-- Campo datepicker mejorado -->
                <div v-else-if="field.type === 'datepicker'">
                    <DatePickerField
                        :model-value="formData[field.id] || null"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :disabled="disabled"
                        :error="errors[`formulario_data.${field.id}`]"
                        :min-date="field.datepickerConfig?.minDate"
                        :max-date="field.datepickerConfig?.maxDate"
                        :format="field.datepickerConfig?.format || 'DD/MM/YYYY'"
                        :allow-past-dates="field.datepickerConfig?.allowPastDates ?? true"
                        :allow-future-dates="field.datepickerConfig?.allowFutureDates ?? true"
                    />
                </div>

                <!-- Campo disclaimer -->
                <div v-else-if="field.type === 'disclaimer'">
                    <DisclaimerField
                        :model-value="formData[field.id] || null"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :disabled="disabled"
                        :error="errors[`formulario_data.${field.id}`]"
                        :disclaimer-text="field.disclaimerConfig?.disclaimerText || ''"
                        :modal-title="field.disclaimerConfig?.modalTitle || 'Términos y Condiciones'"
                        :accept-button-text="field.disclaimerConfig?.acceptButtonText || 'Acepto'"
                        :decline-button-text="field.disclaimerConfig?.declineButtonText || 'No acepto'"
                    />
                </div>

                <!-- Campo repetidor -->
                <div v-else-if="field.type === 'repeater'">
                    <RepeaterField
                        :model-value="formData[field.id] || []"
                        @update:model-value="(value) => updateField(field.id, value)"
                        :label="field.title"
                        :description="field.description"
                        :required="field.required"
                        :disabled="disabled"
                        :error="errors[`formulario_data.${field.id}`]"
                        :min-items="field.repeaterConfig?.minItems || 0"
                        :max-items="field.repeaterConfig?.maxItems || 10"
                        :item-name="field.repeaterConfig?.itemName || 'Elemento'"
                        :add-button-text="field.repeaterConfig?.addButtonText || 'Agregar elemento'"
                        :remove-button-text="field.repeaterConfig?.removeButtonText || 'Eliminar'"
                        :fields="field.repeaterConfig?.fields || []"
                    />
                </div>
            </template>
            </TransitionGroup>
        </div>

        <!-- Resumen de campos requeridos (solo para postulaciones) -->
        <Card v-if="context === 'postulacion' && camposIncompletos.length > 0" class="border-amber-200 bg-amber-50">
            <CardHeader class="pb-3">
                <CardTitle class="text-base text-amber-800">
                    Campos requeridos pendientes
                </CardTitle>
                <CardDescription class="text-amber-700">
                    Completa los siguientes campos obligatorios antes de enviar tu postulación.
                </CardDescription>
            </CardHeader>
            <CardContent class="pt-0">
                <ul class="list-disc list-inside space-y-1 text-sm text-amber-800">
                    <li v-for="fieldId in camposIncompletos" :key="fieldId">
                        {{ fields.find(f => f.id === fieldId)?.title }}
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
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