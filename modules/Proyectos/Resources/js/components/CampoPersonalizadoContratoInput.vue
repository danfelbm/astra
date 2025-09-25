<script setup lang="ts">
import { computed, watch } from 'vue';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@modules/Core/Resources/js/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Info } from 'lucide-vue-next';
import type { CampoPersonalizadoContrato } from '@modules/Proyectos/Resources/js/types/contratos';

// Props
const props = defineProps<{
    campo: CampoPersonalizadoContrato;
    modelValue: any;
    error?: string;
    disabled?: boolean;
}>();

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: any];
}>();

// Computed
const valor = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
});

const opcionesArray = computed(() => {
    if (!props.campo.opciones) return [];
    if (Array.isArray(props.campo.opciones)) return props.campo.opciones;
    if (typeof props.campo.opciones === 'string') {
        try {
            return JSON.parse(props.campo.opciones);
        } catch {
            return props.campo.opciones.split(',').map(o => o.trim());
        }
    }
    return [];
});

const checkboxValues = computed({
    get: () => {
        if (!valor.value) return [];
        if (Array.isArray(valor.value)) return valor.value;
        if (typeof valor.value === 'string') {
            try {
                return JSON.parse(valor.value);
            } catch {
                return valor.value.split(',').map(v => v.trim());
            }
        }
        return [];
    },
    set: (values) => {
        valor.value = JSON.stringify(values);
    }
});

// Métodos
const toggleCheckbox = (option: string) => {
    const currentValues = [...checkboxValues.value];
    const index = currentValues.indexOf(option);

    if (index > -1) {
        currentValues.splice(index, 1);
    } else {
        currentValues.push(option);
    }

    checkboxValues.value = currentValues;
};

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        // Aquí podrías implementar la lógica de carga del archivo
        // Por ahora, solo guardamos el nombre del archivo
        valor.value = file.name;
    }
};
</script>

<template>
    <div class="space-y-2">
        <!-- Label -->
        <Label
            :for="`campo-${campo.id}`"
            :class="{ 'text-destructive': !!error }"
        >
            {{ campo.nombre }}
            <span v-if="campo.es_requerido" class="text-destructive ml-1">*</span>
        </Label>

        <!-- Descripción -->
        <p v-if="campo.descripcion" class="text-sm text-muted-foreground">
            {{ campo.descripcion }}
        </p>

        <!-- Campo según tipo -->
        <div v-if="campo.tipo === 'text'">
            <Input
                :id="`campo-${campo.id}`"
                v-model="valor"
                type="text"
                :placeholder="campo.placeholder"
                :disabled="disabled"
                :class="{ 'border-destructive': !!error }"
            />
        </div>

        <div v-else-if="campo.tipo === 'email'">
            <Input
                :id="`campo-${campo.id}`"
                v-model="valor"
                type="email"
                :placeholder="campo.placeholder || 'email@ejemplo.com'"
                :disabled="disabled"
                :class="{ 'border-destructive': !!error }"
            />
        </div>

        <div v-else-if="campo.tipo === 'url'">
            <Input
                :id="`campo-${campo.id}`"
                v-model="valor"
                type="url"
                :placeholder="campo.placeholder || 'https://ejemplo.com'"
                :disabled="disabled"
                :class="{ 'border-destructive': !!error }"
            />
        </div>

        <div v-else-if="campo.tipo === 'number'">
            <Input
                :id="`campo-${campo.id}`"
                v-model.number="valor"
                type="number"
                :placeholder="campo.placeholder"
                :disabled="disabled"
                :class="{ 'border-destructive': !!error }"
            />
        </div>

        <div v-else-if="campo.tipo === 'date'">
            <Input
                :id="`campo-${campo.id}`"
                v-model="valor"
                type="date"
                :disabled="disabled"
                :class="{ 'border-destructive': !!error }"
            />
        </div>

        <div v-else-if="campo.tipo === 'textarea'">
            <Textarea
                :id="`campo-${campo.id}`"
                v-model="valor"
                :placeholder="campo.placeholder"
                :disabled="disabled"
                :class="{ 'border-destructive': !!error }"
                rows="4"
            />
        </div>

        <div v-else-if="campo.tipo === 'select'">
            <Select v-model="valor" :disabled="disabled">
                <SelectTrigger :class="{ 'border-destructive': !!error }">
                    <SelectValue :placeholder="campo.placeholder || 'Seleccionar...'" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="opcion in opcionesArray"
                        :key="opcion"
                        :value="opcion"
                    >
                        {{ opcion }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-else-if="campo.tipo === 'checkbox'">
            <div v-if="opcionesArray.length > 1" class="space-y-2">
                <div
                    v-for="opcion in opcionesArray"
                    :key="opcion"
                    class="flex items-center space-x-2"
                >
                    <Checkbox
                        :id="`campo-${campo.id}-${opcion}`"
                        :checked="checkboxValues.includes(opcion)"
                        @update:checked="toggleCheckbox(opcion)"
                        :disabled="disabled"
                    />
                    <Label
                        :for="`campo-${campo.id}-${opcion}`"
                        class="text-sm font-normal cursor-pointer"
                    >
                        {{ opcion }}
                    </Label>
                </div>
            </div>
            <div v-else class="flex items-center space-x-2">
                <Checkbox
                    :id="`campo-${campo.id}`"
                    :checked="valor === 'true' || valor === true"
                    @update:checked="(checked) => valor = checked.toString()"
                    :disabled="disabled"
                />
                <Label
                    :for="`campo-${campo.id}`"
                    class="text-sm font-normal cursor-pointer"
                >
                    {{ opcionesArray[0] || 'Sí' }}
                </Label>
            </div>
        </div>

        <div v-else-if="campo.tipo === 'radio'">
            <RadioGroup v-model="valor" :disabled="disabled">
                <div class="space-y-2">
                    <div
                        v-for="opcion in opcionesArray"
                        :key="opcion"
                        class="flex items-center space-x-2"
                    >
                        <RadioGroupItem
                            :id="`campo-${campo.id}-${opcion}`"
                            :value="opcion"
                        />
                        <Label
                            :for="`campo-${campo.id}-${opcion}`"
                            class="text-sm font-normal cursor-pointer"
                        >
                            {{ opcion }}
                        </Label>
                    </div>
                </div>
            </RadioGroup>
        </div>

        <div v-else-if="campo.tipo === 'file'">
            <div class="space-y-2">
                <Input
                    :id="`campo-${campo.id}`"
                    type="file"
                    @change="handleFileUpload"
                    :disabled="disabled"
                    :class="{ 'border-destructive': !!error }"
                />
                <p v-if="valor" class="text-sm text-muted-foreground">
                    Archivo seleccionado: {{ valor }}
                </p>
            </div>
        </div>

        <!-- Tipo no soportado -->
        <div v-else>
            <Alert>
                <Info class="h-4 w-4" />
                <AlertDescription>
                    Tipo de campo no soportado: {{ campo.tipo }}
                </AlertDescription>
            </Alert>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>