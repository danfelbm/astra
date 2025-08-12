<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, GripVertical, ChevronUp, ChevronDown } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import type { FormField } from '@/types/forms';

interface Props {
    modelValue: FormField[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: FormField[]];
}>();

// Tipos de campos disponibles para subcampos del repetidor
const REPEATER_FIELD_TYPES = [
    { value: 'text', label: 'Texto corto' },
    { value: 'number', label: 'Campo numérico' },
    { value: 'email', label: 'Correo electrónico' },
    { value: 'date', label: 'Fecha' },
    { value: 'textarea', label: 'Texto largo' },
    { value: 'select', label: 'Lista desplegable' },
    { value: 'datepicker', label: 'Selector de fecha avanzado' },
];

// Campos locales
const fields = ref<FormField[]>(props.modelValue || []);

// Formulario para nuevo campo
const newField = ref<FormField>({
    id: '',
    type: 'text',
    title: '',
    description: '',
    required: false,
    options: [],
    placeholder: '',
});

// Estado del formulario
const showAddForm = ref(false);
const editingIndex = ref<number | null>(null);

// Sincronizar con modelValue
watch(() => props.modelValue, (newValue) => {
    fields.value = newValue || [];
});

// Emitir cambios
watch(fields, (newFields) => {
    emit('update:modelValue', newFields);
}, { deep: true });

// Resetear formulario
const resetForm = () => {
    newField.value = {
        id: '',
        type: 'text',
        title: '',
        description: '',
        required: false,
        options: [],
        placeholder: '',
    };
    showAddForm.value = false;
    editingIndex.value = null;
};

// Generar ID único para campo
const generateFieldId = () => {
    return `field_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
};

// Agregar campo
const addField = () => {
    if (!newField.value.title) {
        alert('Por favor ingresa un título para el campo');
        return;
    }
    
    // Generar ID si no existe
    if (!newField.value.id) {
        newField.value.id = generateFieldId();
    }
    
    // Preparar el campo según el tipo
    const fieldToAdd: FormField = {
        id: newField.value.id,
        type: newField.value.type,
        title: newField.value.title,
        description: newField.value.description || undefined,
        required: newField.value.required,
        placeholder: newField.value.placeholder || undefined,
    };
    
    // Agregar opciones si es select
    if (newField.value.type === 'select' && newField.value.options && newField.value.options.length > 0) {
        fieldToAdd.options = newField.value.options;
    }
    
    // Agregar configuración para campo numérico
    if (newField.value.type === 'number') {
        fieldToAdd.numberConfig = {
            min: undefined,
            max: undefined,
            step: 1,
            decimals: 0,
        };
    }
    
    // Agregar configuración para datepicker
    if (newField.value.type === 'datepicker') {
        fieldToAdd.datepickerConfig = {
            minDate: undefined,
            maxDate: undefined,
            format: 'DD/MM/YYYY',
            allowPastDates: true,
            allowFutureDates: true,
        };
    }
    
    if (editingIndex.value !== null) {
        // Actualizar campo existente
        fields.value[editingIndex.value] = fieldToAdd;
    } else {
        // Agregar nuevo campo
        fields.value.push(fieldToAdd);
    }
    
    resetForm();
};

// Editar campo
const editField = (index: number) => {
    const field = fields.value[index];
    newField.value = {
        ...field,
        options: field.options || [],
    };
    editingIndex.value = index;
    showAddForm.value = true;
};

// Eliminar campo
const removeField = (index: number) => {
    if (confirm('¿Estás seguro de eliminar este campo?')) {
        fields.value.splice(index, 1);
    }
};

// Mover campo arriba
const moveFieldUp = (index: number) => {
    if (index > 0) {
        const temp = fields.value[index];
        fields.value[index] = fields.value[index - 1];
        fields.value[index - 1] = temp;
    }
};

// Mover campo abajo
const moveFieldDown = (index: number) => {
    if (index < fields.value.length - 1) {
        const temp = fields.value[index];
        fields.value[index] = fields.value[index + 1];
        fields.value[index + 1] = temp;
    }
};

// Manejar opciones para select
const optionsText = ref('');

watch(optionsText, (newText) => {
    if (newField.value.type === 'select') {
        newField.value.options = newText
            .split('\n')
            .map(opt => opt.trim())
            .filter(opt => opt !== '');
    }
});

// Cuando se edita un campo con opciones
watch(() => editingIndex.value, (newIndex) => {
    if (newIndex !== null && fields.value[newIndex]?.options) {
        optionsText.value = fields.value[newIndex].options!.join('\n');
    } else {
        optionsText.value = '';
    }
});

// Verificar si necesita opciones
const needsOptions = computed(() => {
    return newField.value.type === 'select';
});

// Obtener label del tipo
const getTypeLabel = (type: string) => {
    const fieldType = REPEATER_FIELD_TYPES.find(ft => ft.value === type);
    return fieldType ? fieldType.label : type;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Lista de campos existentes -->
        <div v-if="fields.length > 0" class="space-y-2">
            <Label class="text-sm font-medium">Subcampos configurados:</Label>
            <div class="space-y-2">
                <Card 
                    v-for="(field, index) in fields" 
                    :key="field.id"
                    class="relative"
                >
                    <CardContent class="p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-1">
                                <GripVertical class="h-4 w-4 text-muted-foreground" />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-sm">{{ field.title }}</span>
                                        <span class="text-xs text-muted-foreground">({{ getTypeLabel(field.type) }})</span>
                                        <span v-if="field.required" class="text-xs text-red-500">*Requerido</span>
                                    </div>
                                    <p v-if="field.description" class="text-xs text-muted-foreground mt-1">
                                        {{ field.description }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-1">
                                <!-- Botones de reordenar -->
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="moveFieldUp(index)"
                                    :disabled="index === 0"
                                    class="h-8 w-8 p-0"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="moveFieldDown(index)"
                                    :disabled="index === fields.length - 1"
                                    class="h-8 w-8 p-0"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </Button>
                                
                                <!-- Botón editar -->
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editField(index)"
                                    class="h-8 px-2"
                                >
                                    Editar
                                </Button>
                                
                                <!-- Botón eliminar -->
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeField(index)"
                                    class="h-8 w-8 p-0"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
        
        <!-- Mensaje cuando no hay campos -->
        <div v-else class="text-center py-4 text-sm text-muted-foreground">
            No hay subcampos configurados. Agrega al menos un campo.
        </div>
        
        <!-- Formulario para agregar/editar campo -->
        <Card v-if="showAddForm" class="border-primary">
            <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold">
                        {{ editingIndex !== null ? 'Editar' : 'Agregar' }} Subcampo
                    </h4>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="resetForm"
                        class="h-8 w-8 p-0"
                    >
                        ×
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Tipo de campo -->
                <div>
                    <Label for="field-type">Tipo de campo *</Label>
                    <Select v-model="newField.type">
                        <SelectTrigger id="field-type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="fieldType in REPEATER_FIELD_TYPES"
                                :key="fieldType.value"
                                :value="fieldType.value"
                            >
                                {{ fieldType.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                
                <!-- Título del campo -->
                <div>
                    <Label for="field-title">Título del campo *</Label>
                    <Input
                        id="field-title"
                        v-model="newField.title"
                        placeholder="Ej: Nombre completo"
                    />
                </div>
                
                <!-- Descripción -->
                <div>
                    <Label for="field-description">Descripción (opcional)</Label>
                    <Input
                        id="field-description"
                        v-model="newField.description"
                        placeholder="Texto de ayuda para el usuario"
                    />
                </div>
                
                <!-- Placeholder -->
                <div v-if="['text', 'number', 'email', 'textarea'].includes(newField.type)">
                    <Label for="field-placeholder">Placeholder (opcional)</Label>
                    <Input
                        id="field-placeholder"
                        v-model="newField.placeholder"
                        placeholder="Texto que aparece cuando el campo está vacío"
                    />
                </div>
                
                <!-- Opciones para select -->
                <div v-if="needsOptions">
                    <Label for="field-options">Opciones (una por línea) *</Label>
                    <Textarea
                        id="field-options"
                        v-model="optionsText"
                        placeholder="Opción 1&#10;Opción 2&#10;Opción 3"
                        rows="4"
                    />
                </div>
                
                <!-- Campo requerido -->
                <div class="flex items-center space-x-2">
                    <Checkbox
                        id="field-required"
                        v-model:checked="newField.required"
                    />
                    <Label for="field-required" class="text-sm font-normal">
                        Campo requerido
                    </Label>
                </div>
                
                <!-- Botones de acción -->
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="resetForm"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        @click="addField"
                    >
                        {{ editingIndex !== null ? 'Actualizar' : 'Agregar' }} Campo
                    </Button>
                </div>
            </CardContent>
        </Card>
        
        <!-- Botón para mostrar formulario -->
        <Button
            v-if="!showAddForm"
            type="button"
            variant="outline"
            size="sm"
            @click="showAddForm = true"
            class="w-full"
        >
            <Plus class="h-4 w-4 mr-2" />
            Agregar Subcampo
        </Button>
    </div>
</template>