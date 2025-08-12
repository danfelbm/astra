<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ref, watch } from 'vue';

interface Props {
    modelValue: boolean | { accepted: boolean; timestamp?: string } | null;
    label: string;
    description?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    disclaimerText: string;
    modalTitle?: string;
    acceptButtonText?: string;
    declineButtonText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    disabled: false,
    modalTitle: 'Términos y Condiciones',
    acceptButtonText: 'Acepto',
    declineButtonText: 'No acepto',
});

const emit = defineEmits<{
    'update:modelValue': [value: { accepted: boolean; timestamp?: string } | null];
}>();

// Estado del modal
const showModal = ref(false);

// Valor interno del checkbox
const isAccepted = ref(false);
const acceptedTimestamp = ref<string | null>(null);

// Inicializar valor desde props
if (props.modelValue) {
    if (typeof props.modelValue === 'boolean') {
        isAccepted.value = props.modelValue;
    } else if (typeof props.modelValue === 'object' && props.modelValue.accepted) {
        isAccepted.value = props.modelValue.accepted;
        acceptedTimestamp.value = props.modelValue.timestamp || null;
    }
}

// Actualizar cuando cambia el prop
watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        if (typeof newValue === 'boolean') {
            isAccepted.value = newValue;
        } else if (typeof newValue === 'object' && newValue.accepted !== undefined) {
            isAccepted.value = newValue.accepted;
            acceptedTimestamp.value = newValue.timestamp || null;
        }
    } else {
        isAccepted.value = false;
        acceptedTimestamp.value = null;
    }
});

// Manejar clic en el checkbox
const handleCheckboxClick = (checked: boolean) => {
    if (checked && !isAccepted.value && !props.disabled) {
        // Si se intenta marcar y no está aceptado, abrir modal
        showModal.value = true;
    } else if (!checked) {
        // Si se desmarca, actualizar el estado
        handleDecline();
    }
};

// Manejar aceptación en el modal
const handleAccept = () => {
    isAccepted.value = true;
    acceptedTimestamp.value = new Date().toISOString();
    
    emit('update:modelValue', {
        accepted: true,
        timestamp: acceptedTimestamp.value,
    });
    
    showModal.value = false;
};

// Manejar rechazo en el modal
const handleDecline = () => {
    isAccepted.value = false;
    acceptedTimestamp.value = null;
    
    emit('update:modelValue', null);
    
    showModal.value = false;
};

// Generar ID único para el campo
const fieldId = `disclaimer-${Math.random().toString(36).substr(2, 9)}`;

// Formatear fecha de aceptación para mostrar
const formatAcceptedDate = (timestamp: string): string => {
    const date = new Date(timestamp);
    return date.toLocaleString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="space-y-2">
        <!-- Checkbox principal -->
        <div class="flex items-start space-x-3">
            <Checkbox
                :id="fieldId"
                :checked="isAccepted"
                @update:checked="handleCheckboxClick"
                :disabled="disabled"
                :aria-invalid="!!error"
                :aria-describedby="error ? `${fieldId}-error` : undefined"
                class="mt-1"
            />
            
            <div class="flex-1 space-y-1">
                <Label 
                    :for="fieldId" 
                    class="text-sm font-medium cursor-pointer leading-relaxed"
                    @click="() => !disabled && !isAccepted && (showModal = true)"
                >
                    {{ label }}
                    <span v-if="required" class="text-red-500 ml-1">*</span>
                </Label>
                
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
                
                <!-- Mostrar timestamp de aceptación si existe -->
                <p v-if="isAccepted && acceptedTimestamp" class="text-xs text-green-600">
                    ✓ Aceptado el {{ formatAcceptedDate(acceptedTimestamp) }}
                </p>
                
                <!-- Enlace para ver disclaimer nuevamente -->
                <button
                    v-if="!disabled"
                    type="button"
                    @click="showModal = true"
                    class="text-xs text-blue-600 hover:text-blue-800 underline"
                >
                    {{ isAccepted ? 'Ver términos aceptados' : 'Leer términos y condiciones' }}
                </button>
            </div>
        </div>
        
        <!-- Mensaje de error -->
        <p v-if="error" :id="`${fieldId}-error`" class="text-sm text-red-600 ml-6">
            {{ error }}
        </p>
        
        <!-- Modal con el disclaimer -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ modalTitle }}</DialogTitle>
                    <DialogDescription>
                        Por favor, lea atentamente los siguientes términos y condiciones:
                    </DialogDescription>
                </DialogHeader>
                
                <!-- Contenido del disclaimer -->
                <div class="my-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div 
                        class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap"
                        v-html="disclaimerText"
                    />
                </div>
                
                <!-- Footer con botones -->
                <DialogFooter class="flex gap-2 sm:gap-0">
                    <Button
                        v-if="!isAccepted || !disabled"
                        type="button"
                        variant="outline"
                        @click="handleDecline"
                        :disabled="disabled"
                    >
                        {{ declineButtonText }}
                    </Button>
                    
                    <Button
                        v-if="!isAccepted || !disabled"
                        type="button"
                        @click="handleAccept"
                        :disabled="disabled"
                    >
                        {{ acceptButtonText }}
                    </Button>
                    
                    <Button
                        v-if="isAccepted && disabled"
                        type="button"
                        variant="outline"
                        @click="showModal = false"
                    >
                        Cerrar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>