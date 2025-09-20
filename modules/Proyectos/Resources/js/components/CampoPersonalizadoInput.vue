<script setup lang="ts">
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Checkbox } from "@modules/Core/Resources/js/components/ui/checkbox";
import { RadioGroup, RadioGroupItem } from "@modules/Core/Resources/js/components/ui/radio-group";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import { AlertCircle } from 'lucide-vue-next';

interface Campo {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: Array<{value: string; label: string}>;
    es_requerido: boolean;
    placeholder?: string;
    descripcion?: string;
}

interface Props {
    campo: Campo;
    value: any;
    error?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    update: [value: any]
}>();

// Manejar cambio de valor
const handleChange = (newValue: any) => {
    emit('update', newValue);
};

// Manejar cambio de checkbox
const handleCheckboxChange = (checked: boolean) => {
    emit('update', checked);
};
</script>

<template>
    <div class="space-y-2">
        <!-- Label -->
        <Label :for="`campo-${campo.id}`">
            {{ campo.nombre }}
            <span v-if="campo.es_requerido" class="text-red-500">*</span>
        </Label>

        <!-- Descripción -->
        <p v-if="campo.descripcion" class="text-sm text-gray-500">
            {{ campo.descripcion }}
        </p>

        <!-- Input de texto -->
        <Input
            v-if="campo.tipo === 'text'"
            :id="`campo-${campo.id}`"
            type="text"
            :value="value"
            :placeholder="campo.placeholder"
            :required="campo.es_requerido"
            :class="{ 'border-red-500': error }"
            @input="handleChange($event.target.value)"
        />

        <!-- Input de número -->
        <Input
            v-else-if="campo.tipo === 'number'"
            :id="`campo-${campo.id}`"
            type="number"
            :value="value"
            :placeholder="campo.placeholder"
            :required="campo.es_requerido"
            :class="{ 'border-red-500': error }"
            @input="handleChange($event.target.value)"
        />

        <!-- Input de fecha -->
        <Input
            v-else-if="campo.tipo === 'date'"
            :id="`campo-${campo.id}`"
            type="date"
            :value="value"
            :required="campo.es_requerido"
            :class="{ 'border-red-500': error }"
            @input="handleChange($event.target.value)"
        />

        <!-- Textarea -->
        <Textarea
            v-else-if="campo.tipo === 'textarea'"
            :id="`campo-${campo.id}`"
            :model-value="value"
            :placeholder="campo.placeholder"
            :required="campo.es_requerido"
            :class="{ 'border-red-500': error }"
            rows="4"
            @update:model-value="handleChange"
        />

        <!-- Select -->
        <Select
            v-else-if="campo.tipo === 'select'"
            :model-value="value"
            @update:model-value="handleChange"
        >
            <SelectTrigger :class="{ 'border-red-500': error }">
                <SelectValue :placeholder="campo.placeholder || 'Seleccione una opción'" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem 
                    v-for="opcion in campo.opciones" 
                    :key="opcion.value"
                    :value="opcion.value"
                >
                    {{ opcion.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <!-- Checkbox -->
        <div v-else-if="campo.tipo === 'checkbox'" class="flex items-center space-x-2">
            <Checkbox
                :id="`campo-${campo.id}`"
                :checked="value"
                @update:checked="handleCheckboxChange"
            />
            <Label 
                :for="`campo-${campo.id}`" 
                class="text-sm font-normal cursor-pointer"
            >
                {{ campo.placeholder || 'Marcar esta opción' }}
            </Label>
        </div>

        <!-- Radio buttons -->
        <RadioGroup
            v-else-if="campo.tipo === 'radio'"
            :model-value="value"
            @update:model-value="handleChange"
        >
            <div class="space-y-2">
                <div 
                    v-for="opcion in campo.opciones" 
                    :key="opcion.value"
                    class="flex items-center space-x-2"
                >
                    <RadioGroupItem 
                        :value="opcion.value" 
                        :id="`campo-${campo.id}-${opcion.value}`" 
                    />
                    <Label 
                        :for="`campo-${campo.id}-${opcion.value}`"
                        class="text-sm font-normal cursor-pointer"
                    >
                        {{ opcion.label }}
                    </Label>
                </div>
            </div>
        </RadioGroup>

        <!-- Input de archivo -->
        <Input
            v-else-if="campo.tipo === 'file'"
            :id="`campo-${campo.id}`"
            type="file"
            :required="campo.es_requerido"
            :class="{ 'border-red-500': error }"
            @change="handleChange($event.target.files[0])"
        />

        <!-- Tipo no soportado -->
        <div v-else class="p-3 bg-gray-100 dark:bg-gray-800 rounded-md">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Tipo de campo "{{ campo.tipo }}" no soportado
            </p>
        </div>

        <!-- Error message -->
        <div v-if="error" class="flex items-center gap-2 text-sm text-red-600">
            <AlertCircle class="h-4 w-4" />
            <span>{{ error }}</span>
        </div>
    </div>
</template>