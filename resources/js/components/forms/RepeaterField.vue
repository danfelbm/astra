<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Plus, Trash2, GripVertical } from 'lucide-vue-next';
import DatePickerField from './DatePickerField.vue';
import { ref, computed, watch } from 'vue';
import type { FormField } from '@/types/forms';

interface RepeaterItem {
    id: string;
    data: Record<string, any>;
}

interface Props {
    modelValue: RepeaterItem[] | null;
    label: string;
    description?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    minItems?: number;
    maxItems?: number;
    itemName?: string;
    addButtonText?: string;
    removeButtonText?: string;
    fields: FormField[];
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    disabled: false,
    minItems: 0,
    maxItems: 10,
    itemName: 'Elemento',
    addButtonText: 'Agregar elemento',
    removeButtonText: 'Eliminar',
});

const emit = defineEmits<{
    'update:modelValue': [value: RepeaterItem[]];
}>();

// Items del repetidor
const items = ref<RepeaterItem[]>([]);

// Inicializar con valores existentes o crear item inicial si es requerido
if (props.modelValue && Array.isArray(props.modelValue)) {
    items.value = props.modelValue;
} else if (props.minItems > 0) {
    // Crear items mínimos requeridos
    for (let i = 0; i < props.minItems; i++) {
        items.value.push(createNewItem());
    }
}

// Observar cambios en el modelValue
watch(() => props.modelValue, (newValue) => {
    if (newValue && Array.isArray(newValue)) {
        items.value = newValue;
    }
});

// Emitir cambios
watch(items, (newItems) => {
    emit('update:modelValue', newItems);
}, { deep: true });

// Crear nuevo item vacío
function createNewItem(): RepeaterItem {
    const newItem: RepeaterItem = {
        id: `item-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        data: {},
    };
    
    // Inicializar valores por defecto para cada campo
    props.fields.forEach(field => {
        if (field.type === 'checkbox') {
            newItem.data[field.id] = [];
        } else if (field.type === 'repeater') {
            newItem.data[field.id] = [];
        } else {
            newItem.data[field.id] = '';
        }
    });
    
    return newItem;
}

// Agregar nuevo item
const addItem = () => {
    if (items.value.length < props.maxItems) {
        items.value.push(createNewItem());
    }
};

// Eliminar item
const removeItem = (index: number) => {
    if (items.value.length > props.minItems) {
        items.value.splice(index, 1);
    }
};

// Actualizar campo de un item
const updateItemField = (itemIndex: number, fieldId: string, value: any) => {
    if (items.value[itemIndex]) {
        items.value[itemIndex].data[fieldId] = value;
    }
};

// Manejar cambios en checkboxes
const handleCheckboxChange = (itemIndex: number, fieldId: string, option: string, checked: boolean) => {
    const currentValue = items.value[itemIndex].data[fieldId] || [];
    let newValue;
    
    if (checked) {
        newValue = [...currentValue, option];
    } else {
        newValue = currentValue.filter((item: string) => item !== option);
    }
    
    updateItemField(itemIndex, fieldId, newValue);
};

// Verificar si se puede agregar más items
const canAddMore = computed(() => items.value.length < props.maxItems);

// Verificar si se puede eliminar items
const canRemove = computed(() => items.value.length > props.minItems);

// Mensaje de información sobre límites
const limitsMessage = computed(() => {
    const messages = [];
    
    if (props.minItems > 0) {
        messages.push(`Mínimo ${props.minItems} elemento${props.minItems > 1 ? 's' : ''}`);
    }
    
    if (props.maxItems < Infinity) {
        messages.push(`Máximo ${props.maxItems} elemento${props.maxItems > 1 ? 's' : ''}`);
    }
    
    return messages.length > 0 ? messages.join(' | ') : '';
});
</script>

<template>
    <div class="space-y-4">
        <!-- Header del repetidor -->
        <div>
            <Label class="text-base font-semibold">
                {{ label }}
                <span v-if="required" class="text-red-500 ml-1">*</span>
            </Label>
            
            <p v-if="description" class="text-sm text-muted-foreground mt-1">
                {{ description }}
            </p>
            
            <p v-if="limitsMessage" class="text-xs text-muted-foreground mt-1">
                {{ limitsMessage }}
            </p>
        </div>
        
        <!-- Items del repetidor -->
        <div class="space-y-3">
            <Card 
                v-for="(item, itemIndex) in items" 
                :key="item.id"
                class="relative"
                :class="[disabled ? 'opacity-60' : '']"
            >
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <GripVertical class="h-4 w-4 text-muted-foreground" />
                            <span class="text-sm font-medium">
                                {{ itemName }} {{ itemIndex + 1 }}
                            </span>
                        </div>
                        
                        <Button
                            v-if="canRemove"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeItem(itemIndex)"
                            :disabled="disabled"
                            class="h-8 px-2"
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </CardHeader>
                
                <CardContent class="space-y-4">
                    <!-- Renderizar campos del repetidor -->
                    <div v-for="field in fields" :key="field.id" class="space-y-2">
                        <!-- Campo de texto -->
                        <template v-if="field.type === 'text'">
                            <Label :for="`${item.id}-${field.id}`" class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <Input
                                :id="`${item.id}-${field.id}`"
                                :model-value="item.data[field.id] || ''"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                :placeholder="field.description"
                                :disabled="disabled"
                            />
                        </template>
                        
                        <!-- Campo de texto largo -->
                        <template v-else-if="field.type === 'textarea'">
                            <Label :for="`${item.id}-${field.id}`" class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <Textarea
                                :id="`${item.id}-${field.id}`"
                                :model-value="item.data[field.id] || ''"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                :placeholder="field.description"
                                :disabled="disabled"
                                rows="3"
                            />
                        </template>
                        
                        <!-- Campo numérico -->
                        <template v-else-if="field.type === 'number'">
                            <Label :for="`${item.id}-${field.id}`" class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <Input
                                :id="`${item.id}-${field.id}`"
                                type="number"
                                :model-value="item.data[field.id] || ''"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                :placeholder="field.description"
                                :disabled="disabled"
                            />
                        </template>
                        
                        <!-- Campo de email -->
                        <template v-else-if="field.type === 'email'">
                            <Label :for="`${item.id}-${field.id}`" class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <Input
                                :id="`${item.id}-${field.id}`"
                                type="email"
                                :model-value="item.data[field.id] || ''"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                placeholder="ejemplo@correo.com"
                                :disabled="disabled"
                            />
                        </template>
                        
                        <!-- Campo de fecha HTML5 -->
                        <template v-else-if="field.type === 'date'">
                            <Label :for="`${item.id}-${field.id}`" class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <Input
                                :id="`${item.id}-${field.id}`"
                                type="date"
                                :model-value="item.data[field.id] || ''"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                :disabled="disabled"
                            />
                        </template>
                        
                        <!-- Campo DatePicker mejorado -->
                        <template v-else-if="field.type === 'datepicker'">
                            <DatePickerField
                                :model-value="item.data[field.id] || null"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                :label="field.title"
                                :description="field.description"
                                :required="field.required"
                                :disabled="disabled"
                                :minDate="field.datepickerConfig?.minDate"
                                :maxDate="field.datepickerConfig?.maxDate"
                                :format="field.datepickerConfig?.format"
                                :allowPastDates="field.datepickerConfig?.allowPastDates"
                                :allowFutureDates="field.datepickerConfig?.allowFutureDates"
                            />
                        </template>
                        
                        <!-- Campo de selección -->
                        <template v-else-if="field.type === 'select'">
                            <Label :for="`${item.id}-${field.id}`" class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <Select
                                :model-value="item.data[field.id] || ''"
                                @update:model-value="(value) => updateItemField(itemIndex, field.id, value)"
                                :disabled="disabled"
                            >
                                <SelectTrigger>
                                    <SelectValue :placeholder="`Seleccionar ${field.title.toLowerCase()}`" />
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
                        </template>
                        
                        <!-- Campo de radio -->
                        <template v-else-if="field.type === 'radio'">
                            <Label class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <div class="space-y-2">
                                <div
                                    v-for="option in field.options"
                                    :key="option"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        :id="`${item.id}-${field.id}-${option}`"
                                        type="radio"
                                        :name="`${item.id}-${field.id}`"
                                        :value="option"
                                        :checked="item.data[field.id] === option"
                                        @change="() => updateItemField(itemIndex, field.id, option)"
                                        :disabled="disabled"
                                        class="h-4 w-4"
                                    />
                                    <Label :for="`${item.id}-${field.id}-${option}`" class="text-sm font-normal">
                                        {{ option }}
                                    </Label>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Campo de checkbox -->
                        <template v-else-if="field.type === 'checkbox'">
                            <Label class="text-sm">
                                {{ field.title }}
                                <span v-if="field.required" class="text-red-500 ml-1">*</span>
                            </Label>
                            <div class="space-y-2">
                                <div
                                    v-for="option in field.options"
                                    :key="option"
                                    class="flex items-center space-x-2"
                                >
                                    <Checkbox
                                        :id="`${item.id}-${field.id}-${option}`"
                                        :checked="(item.data[field.id] || []).includes(option)"
                                        @update:checked="(checked) => handleCheckboxChange(itemIndex, field.id, option, checked)"
                                        :disabled="disabled"
                                    />
                                    <Label :for="`${item.id}-${field.id}-${option}`" class="text-sm font-normal">
                                        {{ option }}
                                    </Label>
                                </div>
                            </div>
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
        
        <!-- Botón para agregar más items -->
        <Button
            v-if="canAddMore"
            type="button"
            variant="outline"
            @click="addItem"
            :disabled="disabled"
            class="w-full"
        >
            <Plus class="h-4 w-4 mr-2" />
            {{ addButtonText }}
        </Button>
        
        <!-- Mensaje cuando se alcanza el límite -->
        <p v-if="!canAddMore" class="text-sm text-muted-foreground text-center">
            Has alcanzado el límite máximo de {{ maxItems }} elementos
        </p>
        
        <!-- Mensaje de error -->
        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>