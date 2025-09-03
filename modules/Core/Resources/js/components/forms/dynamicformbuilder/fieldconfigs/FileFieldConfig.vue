<script setup lang="ts">
import { Badge } from "../../../ui/badge";
import { Checkbox } from "../../../ui/checkbox";
import { Input } from "../../../ui/input";
import { Label } from "../../../ui/label";
import { computed } from 'vue';

interface FileConfig {
    multiple?: boolean;
    maxFiles?: number;
    maxFileSize?: number;
    accept?: string;
}

interface Props {
    modelValue: FileConfig;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: FileConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
        multiple: false,
        maxFiles: 5,
        maxFileSize: 10,
        accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif'
    },
    set: (value: FileConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof FileConfig, value: any) => {
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/50 dark:bg-muted/20 rounded-lg space-y-4">
            <h4 class="font-medium flex items-center gap-2">
                <Badge variant="secondary" class="text-xs">Configuración de Archivo</Badge>
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Archivos múltiples -->
                <div>
                    <Label>Archivos múltiples</Label>
                    <div class="flex items-center space-x-2 mt-1">
                        <Checkbox
                            :checked="localConfig.multiple"
                            @update:checked="(checked) => updateConfig('multiple', checked)"
                            :disabled="disabled"
                        />
                        <Label class="text-sm">Permitir múltiples archivos</Label>
                    </div>
                </div>
                
                <!-- Máximo de archivos (solo si múltiples está habilitado) -->
                <div v-if="localConfig.multiple">
                    <Label>Máximo de archivos</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.maxFiles"
                        @update:model-value="(value) => updateConfig('maxFiles', Number(value))"
                        min="1"
                        max="20"
                        placeholder="5"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Entre 1 y 20 archivos
                    </p>
                </div>
                
                <!-- Tamaño máximo por archivo -->
                <div>
                    <Label>Tamaño máximo por archivo (MB)</Label>
                    <Input
                        type="number"
                        :model-value="localConfig.maxFileSize"
                        @update:model-value="(value) => updateConfig('maxFileSize', Number(value))"
                        min="1"
                        max="100"
                        placeholder="10"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Entre 1 y 100 MB
                    </p>
                </div>
                
                <!-- Tipos de archivo permitidos -->
                <div>
                    <Label>Tipos de archivo permitidos</Label>
                    <Input
                        :model-value="localConfig.accept"
                        @update:model-value="(value) => updateConfig('accept', value)"
                        placeholder=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Separados por comas, ej: .pdf,.jpg,.doc
                    </p>
                </div>
            </div>
            
            <!-- Vista previa de configuración -->
            <div class="mt-4 p-3 bg-muted/30 dark:bg-muted/10 rounded-md">
                <p class="text-sm text-muted-foreground">
                    <strong>Resumen:</strong>
                    {{ localConfig.multiple ? `Hasta ${localConfig.maxFiles} archivos` : 'Un solo archivo' }} 
                    de máximo {{ localConfig.maxFileSize }}MB cada uno.
                    <br>
                    Formatos: {{ localConfig.accept }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de archivo */
</style>