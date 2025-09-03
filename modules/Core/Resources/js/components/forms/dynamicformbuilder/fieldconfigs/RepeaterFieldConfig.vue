<script setup lang="ts">
import { Badge } from "../../../ui/badge";
import { Input } from "../../../ui/input";
import { Label } from "../../../ui/label";
import RepeaterBuilder from '../../RepeaterBuilder.vue';
import { computed } from 'vue';
import type { FormField } from "@modules/Core/Resources/js/types/forms";

interface RepeaterConfig {
    minItems?: number;
    maxItems?: number;
    itemName?: string;
    addButtonText?: string;
    removeButtonText?: string;
    fields?: FormField[];
}

interface Props {
    modelValue: RepeaterConfig;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: RepeaterConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
        minItems: 0,
        maxItems: 10,
        itemName: 'Elemento',
        addButtonText: 'Agregar elemento',
        removeButtonText: 'Eliminar',
        fields: []
    },
    set: (value: RepeaterConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof RepeaterConfig, value: any) => {
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value
    });
};

// Computed para validación
const isConfigValid = computed(() => {
    const config = localConfig.value;
    if (config.minItems !== undefined && config.maxItems !== undefined) {
        return config.minItems <= config.maxItems;
    }
    return true;
});
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-cyan-50 dark:bg-cyan-950/20 rounded-lg border border-cyan-200 dark:border-cyan-800 space-y-4">
            <h4 class="font-medium text-cyan-900 dark:text-cyan-100 mb-3 flex items-center gap-2">
                <Badge variant="secondary" class="text-xs">Configuración del Repetidor</Badge>
            </h4>
            <p class="text-sm text-cyan-700 dark:text-cyan-300 mb-4">
                Define los subcampos y límites del repetidor. Los usuarios podrán agregar múltiples instancias de estos campos.
            </p>
            
            <div class="space-y-4">
                <!-- Número mínimo de elementos -->
                <div>
                    <Label>Número mínimo de elementos</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.minItems"
                        @update:model-value="(value) => updateConfig('minItems', Number(value))"
                        min="0"
                        max="50"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Cantidad mínima de elementos que debe tener el usuario
                    </p>
                </div>
                
                <!-- Número máximo de elementos -->
                <div>
                    <Label>Número máximo de elementos</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.maxItems"
                        @update:model-value="(value) => updateConfig('maxItems', Number(value))"
                        min="1"
                        max="50"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Cantidad máxima de elementos que puede agregar el usuario
                    </p>
                </div>
                
                <!-- Nombre del elemento -->
                <div>
                    <Label>Nombre del elemento</Label>
                    <Input
                        :model-value="localConfig.itemName"
                        @update:model-value="(value) => updateConfig('itemName', value)"
                        placeholder="Elemento"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Cómo se llamará cada instancia (ej: "Referencia", "Experiencia", "Documento")
                    </p>
                </div>
                
                <!-- Texto del botón agregar -->
                <div>
                    <Label>Texto del botón agregar</Label>
                    <Input
                        :model-value="localConfig.addButtonText"
                        @update:model-value="(value) => updateConfig('addButtonText', value)"
                        placeholder="Agregar elemento"
                        :disabled="disabled"
                    />
                </div>
                
                <!-- Texto del botón eliminar -->
                <div>
                    <Label>Texto del botón eliminar</Label>
                    <Input
                        :model-value="localConfig.removeButtonText"
                        @update:model-value="(value) => updateConfig('removeButtonText', value)"
                        placeholder="Eliminar"
                        :disabled="disabled"
                    />
                </div>
                
                <!-- Subcampos del repetidor -->
                <div>
                    <Label class="text-sm font-medium mb-2">Configuración de subcampos</Label>
                    <p class="text-xs text-muted-foreground mb-3">
                        Define los campos que se repetirán dentro del repetidor.
                    </p>
                    <RepeaterBuilder
                        :model-value="localConfig.fields"
                        @update:model-value="(value) => updateConfig('fields', value)"
                        :disabled="disabled"
                    />
                </div>
            </div>
            
            <!-- Validación -->
            <div v-if="!isConfigValid" class="p-3 bg-destructive/10 dark:bg-destructive/20 rounded-md">
                <p class="text-sm text-destructive">
                    ⚠️ El número mínimo no puede ser mayor al máximo
                </p>
            </div>
            
            <!-- Resumen de configuración -->
            <div v-if="isConfigValid" class="mt-4 p-3 bg-muted/30 dark:bg-muted/10 rounded-md">
                <p class="text-sm text-muted-foreground">
                    <strong>Resumen:</strong>
                    Entre {{ localConfig.minItems }} y {{ localConfig.maxItems }} {{ localConfig.itemName?.toLowerCase() }}s.
                    <span v-if="localConfig.fields && localConfig.fields.length > 0">
                        <br>
                        {{ localConfig.fields.length }} subcampo{{ localConfig.fields.length === 1 ? '' : 's' }} configurado{{ localConfig.fields.length === 1 ? '' : 's' }}.
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de repeater */
</style>