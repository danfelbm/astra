<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, Eye, X, GitBranch } from 'lucide-vue-next';
import { watch, computed, reactive } from 'vue';

// Importar configuraciones específicas
import SelectFieldConfig from './fieldconfigs/SelectFieldConfig.vue';
import FileFieldConfig from './fieldconfigs/FileFieldConfig.vue';
import DatePickerFieldConfig from './fieldconfigs/DatePickerFieldConfig.vue';
import NumberFieldConfig from './fieldconfigs/NumberFieldConfig.vue';
import DisclaimerFieldConfig from './fieldconfigs/DisclaimerFieldConfig.vue';
import ConvocatoriaFieldConfig from './fieldconfigs/ConvocatoriaFieldConfig.vue';
import PerfilCandidaturaFieldConfig from './fieldconfigs/PerfilCandidaturaFieldConfig.vue';
import RepeaterFieldConfig from './fieldconfigs/RepeaterFieldConfig.vue';
import ConditionalFieldConfig from '../ConditionalFieldConfig.vue';

import type { FormField } from '@/types/forms';
import { FIELD_TYPES } from '@/types/forms';
import { useFieldDefaults } from './composables/useFieldDefaults';
import { useFieldValidation } from './composables/useFieldValidation';

type FormMode = 'create' | 'edit';

interface Props {
    mode: FormMode;
    field?: FormField; // Para modo edición
    disabled?: boolean;
    showEditableOption?: boolean;
    showPerfilCandidaturaConfig?: boolean;
    showConvocatoriaConfig?: boolean;
    cargos?: Array<{ id: number; nombre: string; ruta_jerarquica?: string }>;
    periodosElectorales?: Array<{ id: number; nombre: string; fecha_inicio: string; fecha_fin: string }>;
    convocatorias?: Array<{ 
        id: number; 
        nombre: string; 
        cargo?: { nombre: string; ruta_jerarquica?: string };
        periodo_electoral?: { nombre: string };
        estado_temporal?: string;
    }>;
    context?: 'convocatoria' | 'votacion' | 'candidatura';
    availableFields?: FormField[]; // Campos disponibles para condicionales
}

interface Emits {
    (e: 'save', field: FormField): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    showEditableOption: false,
    showPerfilCandidaturaConfig: false,
    showConvocatoriaConfig: false,
    cargos: () => [],
    periodosElectorales: () => [],
    convocatorias: () => [],
    context: 'convocatoria',
    availableFields: () => [],
});

const emit = defineEmits<Emits>();

// Composables
const { getDefaultField } = useFieldDefaults();
const { isFieldValid, getFirstError } = useFieldValidation();

// Estado local del formulario - inicializar con todas las configuraciones
const createEmptyField = (): FormField => ({
    id: `field_${Date.now()}`,
    type: 'text',
    title: '',
    description: '',
    required: false,
    options: [],
    editable: false,
    conditionalConfig: {
        enabled: false,
        showWhen: 'all',
        conditions: [],
    },
    convocatoriaConfig: {
        convocatoria_id: undefined,
        multiple: false,
        mostrarVotoBlanco: true,
        ordenCandidatos: 'aleatorio',
        vistaPreferida: 'lista',
    },
    perfilCandidaturaConfig: {
        cargo_id: undefined,
        periodo_electoral_id: undefined,
        territorio_id: undefined,
        departamento_id: undefined,
        municipio_id: undefined,
        localidad_id: undefined,
        territorios_ids: [],
        departamentos_ids: [],
        municipios_ids: [],
        localidades_ids: [],
        multiple: false,
        mostrarVotoBlanco: true,
    },
    fileConfig: {
        multiple: false,
        maxFiles: 5,
        maxFileSize: 10,
        accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
    },
    datepickerConfig: {
        minDate: undefined,
        maxDate: undefined,
        format: 'DD/MM/YYYY',
        allowPastDates: true,
        allowFutureDates: true,
    },
    disclaimerConfig: {
        disclaimerText: '',
        modalTitle: 'Términos y Condiciones',
        acceptButtonText: 'Acepto',
        declineButtonText: 'No acepto',
    },
    repeaterConfig: {
        minItems: 0,
        maxItems: 10,
        itemName: 'Elemento',
        addButtonText: 'Agregar elemento',
        removeButtonText: 'Eliminar',
        fields: [],
    },
    numberConfig: {
        min: undefined,
        max: undefined,
        step: 1,
        decimals: 0,
    },
});

const localField = reactive<FormField>(
    props.mode === 'edit' && props.field 
        ? { ...props.field }
        : createEmptyField()
);

// Computed
const formTitle = computed(() => props.mode === 'create' ? 'Añadir Nuevo Campo' : 'Editar Campo');
const submitButtonText = computed(() => props.mode === 'create' ? 'Añadir Campo' : 'Guardar Cambios');

// Métodos para manejar opciones
const addOption = () => {
    if (!localField.options) localField.options = [];
    localField.options.push('');
};

const removeOption = (index: number) => {
    localField.options?.splice(index, 1);
};

const handleSave = (event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('FieldForm handleSave called with:', localField);
    
    if (!isFieldValid(localField)) {
        const error = getFirstError(localField);
        console.error('Validation error:', error);
        alert(`Error de validación: ${error}`);
        return;
    }

    emit('save', { ...localField });
};

const handleCancel = (event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('FieldForm handleCancel called');
    emit('cancel');
};

// NO WATCHER - evitar loops infinitos
// Las configuraciones se inicializan en createEmptyField() y eso es suficiente
</script>

<template>
    <Card class="mb-4" :data-form-type="mode === 'create' ? 'new-field' : 'edit-field'">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <CardTitle class="text-lg">{{ formTitle }}</CardTitle>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="handleCancel($event)"
                    class="h-8 w-8 p-0"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>
        
        <CardContent class="space-y-4">
            <!-- Selector de tipo de campo -->
            <div>
                <Label>Tipo de Campo *</Label>
                <Select v-model="localField.type" :disabled="disabled">
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona el tipo de campo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="fieldType in FIELD_TYPES"
                            :key="fieldType.value"
                            :value="fieldType.value"
                        >
                            <div class="flex items-center gap-2">
                                <component :is="fieldType.icon" class="h-4 w-4" />
                                <span>{{ fieldType.label }}</span>
                            </div>
                        </SelectItem>
                    </SelectContent>
                </Select>
                
                <!-- Campo requerido -->
                <div class="flex items-center space-x-2 mt-2">
                    <Checkbox
                        id="field_required"
                        :checked="localField.required"
                        @update:checked="(checked) => localField.required = checked"
                        :disabled="disabled"
                    />
                    <Label for="field_required" class="text-sm">Campo requerido</Label>
                </div>
                
                <!-- Opción solo para candidaturas -->
                <div v-if="showEditableOption" class="flex items-center space-x-2 mt-2">
                    <Checkbox
                        id="field_editable"
                        :checked="localField.editable"
                        @update:checked="(checked) => localField.editable = checked"
                        :disabled="disabled"
                    />
                    <Label class="text-sm">
                        Campo editable en candidaturas aprobadas
                        <span class="text-muted-foreground block text-xs">
                            Los usuarios podrán editar este campo incluso con candidatura aprobada
                        </span>
                    </Label>
                </div>
            </div>

            <!-- Título del campo -->
            <div>
                <Label>Título del Campo *</Label>
                <Input
                    v-model="localField.title"
                    placeholder="Ej: ¿Cuál es tu candidato preferido?"
                    :disabled="disabled"
                />
            </div>

            <!-- Descripción -->
            <div>
                <Label>Descripción (opcional)</Label>
                <Textarea
                    v-model="localField.description"
                    placeholder="Descripción adicional del campo"
                    rows="2"
                    :disabled="disabled"
                />
            </div>

            <!-- Configuración específica por tipo de campo -->
            
            <!-- Opciones para select, radio, checkbox -->
            <SelectFieldConfig
                v-if="['select', 'radio', 'checkbox'].includes(localField.type)"
                v-model="localField.options"
                :disabled="disabled"
                @add-option="addOption"
                @remove-option="removeOption"
            />
            
            <!-- Configuración de archivo -->
            <FileFieldConfig
                v-if="localField.type === 'file'"
                v-model="localField.fileConfig"
                :disabled="disabled"
            />
            
            <!-- Configuración de número -->
            <NumberFieldConfig
                v-if="localField.type === 'number'"
                v-model="localField.numberConfig"
                :disabled="disabled"
            />
            
            <!-- Configuración de datepicker -->
            <DatePickerFieldConfig
                v-if="localField.type === 'datepicker'"
                v-model="localField.datepickerConfig"
                :disabled="disabled"
            />
            
            <!-- Configuración de disclaimer -->
            <DisclaimerFieldConfig
                v-if="localField.type === 'disclaimer'"
                v-model="localField.disclaimerConfig"
                :disabled="disabled"
            />
            
            <!-- Configuración de repeater -->
            <RepeaterFieldConfig
                v-if="localField.type === 'repeater'"
                v-model="localField.repeaterConfig"
                :disabled="disabled"
            />
            
            <!-- Configuración de convocatoria - CONDICIÓN ORIGINAL RESTAURADA -->
            <ConvocatoriaFieldConfig
                v-if="localField.type === 'convocatoria' && showConvocatoriaConfig && localField.convocatoriaConfig"
                v-model="localField.convocatoriaConfig"
                :convocatorias="convocatorias"
                :disabled="disabled"
            />
            
            <!-- Configuración de perfil candidatura -->
            <PerfilCandidaturaFieldConfig
                v-if="localField.type === 'perfil_candidatura'"
                v-model="localField.perfilCandidaturaConfig"
                :cargos="cargos"
                :periodos-electorales="periodosElectorales"
                :context="context"
                :disabled="disabled"
            />
            
            <!-- Configuración condicional -->
            <ConditionalFieldConfig
                v-if="localField.conditionalConfig"
                v-model="localField.conditionalConfig"
                :fields="availableFields"
                :disabled="disabled"
            />

            <!-- Botones de acción -->
            <div class="flex items-center gap-2 pt-4">
                <Button type="button" @click="handleSave($event)" :disabled="disabled">
                    {{ submitButtonText }}
                </Button>
                <Button type="button" variant="outline" @click="handleCancel($event)">
                    Cancelar
                </Button>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
/* Estilos específicos para el formulario de campo */
</style>