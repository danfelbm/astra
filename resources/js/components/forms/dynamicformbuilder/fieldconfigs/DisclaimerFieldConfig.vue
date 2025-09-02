<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { computed } from 'vue';

interface DisclaimerConfig {
    disclaimerText?: string;
    modalTitle?: string;
    acceptButtonText?: string;
    declineButtonText?: string;
}

interface Props {
    modelValue: DisclaimerConfig;
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: DisclaimerConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed para manejar el modelValue
const localConfig = computed({
    get: () => props.modelValue || {
        disclaimerText: '',
        modalTitle: 'Términos y Condiciones',
        acceptButtonText: 'Acepto',
        declineButtonText: 'No acepto'
    },
    set: (value: DisclaimerConfig) => emit('update:modelValue', value)
});

const updateConfig = (key: keyof DisclaimerConfig, value: string) => {
    emit('update:modelValue', {
        ...localConfig.value,
        [key]: value
    });
};

// Computed para validación
const isConfigValid = computed(() => {
    return localConfig.value.disclaimerText?.trim().length > 0;
});
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/50 dark:bg-muted/20 rounded-lg space-y-4">
            <h4 class="font-medium flex items-center gap-2">
                <Badge variant="secondary" class="text-xs">Configuración de Disclaimer</Badge>
            </h4>
            
            <div class="space-y-4">
                <!-- Texto del disclaimer -->
                <div>
                    <Label>Texto del disclaimer *</Label>
                    <Textarea
                        :model-value="localConfig.disclaimerText"
                        @update:model-value="(value) => updateConfig('disclaimerText', value)"
                        placeholder="Ingresa aquí los términos y condiciones, política de privacidad, o el texto legal que los usuarios deben aceptar..."
                        rows="6"
                        class="min-h-[120px]"
                        :disabled="disabled"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Este texto se mostrará en un modal cuando el usuario haga clic en el campo
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Título del modal -->
                    <div>
                        <Label>Título del modal</Label>
                        <Input
                            :model-value="localConfig.modalTitle"
                            @update:model-value="(value) => updateConfig('modalTitle', value)"
                            placeholder="Términos y Condiciones"
                            :disabled="disabled"
                        />
                    </div>
                    
                    <!-- Texto del botón aceptar -->
                    <div>
                        <Label>Texto botón aceptar</Label>
                        <Input
                            :model-value="localConfig.acceptButtonText"
                            @update:model-value="(value) => updateConfig('acceptButtonText', value)"
                            placeholder="Acepto"
                            :disabled="disabled"
                        />
                    </div>
                    
                    <!-- Texto del botón rechazar -->
                    <div class="md:col-span-2">
                        <Label>Texto botón rechazar</Label>
                        <Input
                            :model-value="localConfig.declineButtonText"
                            @update:model-value="(value) => updateConfig('declineButtonText', value)"
                            placeholder="No acepto"
                            :disabled="disabled"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Validación -->
            <div v-if="!isConfigValid" class="p-3 bg-destructive/10 dark:bg-destructive/20 rounded-md">
                <p class="text-sm text-destructive">
                    ⚠️ El texto del disclaimer es requerido
                </p>
            </div>
            
            <!-- Vista previa de configuración -->
            <div v-if="isConfigValid" class="mt-4 p-3 bg-muted/30 dark:bg-muted/10 rounded-md">
                <p class="text-sm text-muted-foreground">
                    <strong>Resumen:</strong>
                    Modal "{{ localConfig.modalTitle }}" con {{ localConfig.disclaimerText?.length }} caracteres.
                    <br>
                    Botones: "{{ localConfig.acceptButtonText }}" / "{{ localConfig.declineButtonText }}"
                </p>
            </div>
            
            <!-- Vista previa del texto (primeras líneas) -->
            <div v-if="localConfig.disclaimerText && localConfig.disclaimerText.length > 100" 
                 class="mt-2 p-2 bg-background border border-border rounded text-xs">
                <strong class="block mb-1">Vista previa del texto:</strong>
                <p class="text-muted-foreground">
                    {{ localConfig.disclaimerText.substring(0, 150) }}
                    <span v-if="localConfig.disclaimerText.length > 150">...</span>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de disclaimer */
</style>