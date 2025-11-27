<script setup lang="ts">
import { ref, computed } from 'vue';
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Checkbox } from "@modules/Core/Resources/js/components/ui/checkbox";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { RadioGroup, RadioGroupItem } from "@modules/Core/Resources/js/components/ui/radio-group";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import { AlertCircle, FileText, Eye, Download, X } from 'lucide-vue-next';

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
    modelValue: any;
    error?: string;
    disabled?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: any]
}>();

// Estado para manejar el archivo actual y si se quiere reemplazar
const quiereReemplazar = ref(false);

// Computed para verificar si el valor actual es un string (archivo existente)
const archivoExistente = computed(() => {
    if (props.campo.tipo === 'file' && typeof props.modelValue === 'string' && props.modelValue) {
        return props.modelValue;
    }
    return null;
});

// Manejar cambio de valor
const handleChange = (newValue: any) => {
    emit('update:modelValue', newValue);
};

// Manejar cambio de checkbox
const handleCheckboxChange = (checked: boolean) => {
    emit('update:modelValue', checked);
};

// Ver archivo existente
const verArchivo = () => {
    if (archivoExistente.value) {
        window.open(`/storage/campos-personalizados/${archivoExistente.value}`, '_blank');
    }
};

// Descargar archivo existente
const descargarArchivo = () => {
    if (archivoExistente.value) {
        const link = document.createElement('a');
        link.href = `/storage/campos-personalizados/${archivoExistente.value}`;
        link.download = archivoExistente.value;
        link.click();
    }
};

// Cancelar reemplazo de archivo
const cancelarReemplazo = () => {
    quiereReemplazar.value = false;
    // No emitimos nada, el valor original se mantiene
};

// Manejar selección de nuevo archivo
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        emit('update:modelValue', target.files[0]);
    }
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
            :value="modelValue"
            :placeholder="campo.placeholder"
            :required="campo.es_requerido"
            :disabled="disabled"
            :class="{ 'border-red-500': error }"
            @input="handleChange($event.target.value)"
        />

        <!-- Input de número -->
        <Input
            v-else-if="campo.tipo === 'number'"
            :id="`campo-${campo.id}`"
            type="number"
            :value="modelValue"
            :placeholder="campo.placeholder"
            :required="campo.es_requerido"
            :disabled="disabled"
            :class="{ 'border-red-500': error }"
            @input="handleChange($event.target.value)"
        />

        <!-- Input de fecha -->
        <Input
            v-else-if="campo.tipo === 'date'"
            :id="`campo-${campo.id}`"
            type="date"
            :value="modelValue"
            :required="campo.es_requerido"
            :disabled="disabled"
            :class="{ 'border-red-500': error }"
            @input="handleChange($event.target.value)"
        />

        <!-- Textarea -->
        <Textarea
            v-else-if="campo.tipo === 'textarea'"
            :id="`campo-${campo.id}`"
            :model-value="modelValue"
            :placeholder="campo.placeholder"
            :required="campo.es_requerido"
            :disabled="disabled"
            :class="{ 'border-red-500': error }"
            rows="4"
            @update:model-value="handleChange"
        />

        <!-- Select -->
        <Select
            v-else-if="campo.tipo === 'select'"
            :model-value="modelValue"
            :disabled="disabled"
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
                :checked="modelValue"
                :disabled="disabled"
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
            :model-value="modelValue"
            :disabled="disabled"
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
        <div v-else-if="campo.tipo === 'file'" class="space-y-2">
            <!-- Mostrar archivo existente si hay uno -->
            <div v-if="archivoExistente && !quiereReemplazar" class="flex items-center gap-2 p-3 rounded-lg border bg-gray-50 dark:bg-gray-800">
                <FileText class="h-5 w-5 text-gray-500 flex-shrink-0" />
                <span class="text-sm flex-1 truncate">{{ archivoExistente }}</span>
                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="verArchivo"
                        title="Ver archivo"
                    >
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="descargarArchivo"
                        title="Descargar archivo"
                    >
                        <Download class="h-4 w-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
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
                        :id="`campo-${campo.id}`"
                        type="file"
                        :required="campo.es_requerido && !archivoExistente"
                        :class="{ 'border-red-500': error, 'flex-1': quiereReemplazar }"
                        @change="handleFileChange"
                    />
                    <Button
                        v-if="quiereReemplazar"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="cancelarReemplazo"
                        title="Cancelar"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
                <p v-if="quiereReemplazar" class="text-xs text-gray-500 mt-1">
                    Selecciona un nuevo archivo para reemplazar el actual
                </p>
            </div>
        </div>

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