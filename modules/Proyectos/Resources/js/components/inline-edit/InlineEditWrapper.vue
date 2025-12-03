<script setup lang="ts">
/**
 * InlineEditWrapper - Componente wrapper para edición inline
 * Proporciona la lógica común: toggle modo edición, auto-save, cancelar, loading
 */
import { ref, computed, watch, useSlots } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Pencil, X, Loader2, Check } from 'lucide-vue-next';

interface Props {
    // Valor actual (solo para mostrar en modo lectura)
    displayValue?: string;
    // Indica si se puede editar
    canEdit?: boolean;
    // Estado de carga durante guardado
    loading?: boolean;
    // Deshabilitar edición temporalmente
    disabled?: boolean;
    // Label del campo (para accesibilidad)
    label?: string;
    // Placeholder cuando no hay valor
    placeholder?: string;
    // Mostrar botón de confirmación (en lugar de auto-save)
    showConfirmButton?: boolean;
    // Clase CSS adicional para el contenedor
    wrapperClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Sin valor',
    showConfirmButton: false,
    wrapperClass: '',
});

const emit = defineEmits<{
    // Emite cuando se activa modo edición
    'edit-start': [];
    // Emite cuando se cancela edición
    'edit-cancel': [];
    // Emite cuando se confirma el valor (para guardar)
    'save': [];
}>();

// Estado interno
const isEditing = ref(false);

// Computed para determinar si mostrar valor o placeholder
const displayText = computed(() => {
    if (props.displayValue === null || props.displayValue === undefined || props.displayValue === '') {
        return props.placeholder;
    }
    return props.displayValue;
});

const isPlaceholder = computed(() => {
    return props.displayValue === null || props.displayValue === undefined || props.displayValue === '';
});

// Activar modo edición
const startEditing = () => {
    if (!props.canEdit || props.disabled || props.loading) return;
    isEditing.value = true;
    emit('edit-start');
};

// Cancelar edición
const cancelEditing = () => {
    isEditing.value = false;
    emit('edit-cancel');
};

// Guardar (confirmar)
const save = () => {
    emit('save');
    // El padre decidirá si cerrar el modo edición después de guardar exitoso
};

// Cerrar modo edición (llamado externamente después de guardar exitoso)
const closeEditing = () => {
    isEditing.value = false;
};

// Manejar Escape para cancelar
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        cancelEditing();
    }
};

// Exponer métodos para el padre
defineExpose({
    startEditing,
    cancelEditing,
    closeEditing,
    isEditing,
});
</script>

<template>
    <div
        :class="[
            'group inline-edit-wrapper relative',
            wrapperClass
        ]"
        @keydown="handleKeydown"
    >
        <!-- Modo Lectura -->
        <div
            v-if="!isEditing"
            class="flex items-center gap-2"
        >
            <span
                :class="[
                    'flex-1',
                    isPlaceholder ? 'text-muted-foreground italic' : ''
                ]"
            >
                <!-- Slot para mostrar valor personalizado -->
                <slot name="display">
                    {{ displayText }}
                </slot>
            </span>

            <!-- Botón editar (visible en hover si canEdit) -->
            <Button
                v-if="canEdit && !disabled"
                variant="ghost"
                size="icon"
                class="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                :disabled="loading"
                @click.stop="startEditing"
                :aria-label="`Editar ${label || 'campo'}`"
            >
                <Pencil class="h-3.5 w-3.5" />
            </Button>
        </div>

        <!-- Modo Edición -->
        <div
            v-else
            class="flex items-start gap-2"
        >
            <!-- Slot para el input/control de edición -->
            <div class="flex-1 min-w-0">
                <slot name="edit" :save="save" :cancel="cancelEditing" />
            </div>

            <!-- Controles de edición -->
            <div class="flex items-center gap-1 flex-shrink-0 pt-1">
                <!-- Botón confirmar (opcional) -->
                <Button
                    v-if="showConfirmButton"
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6 text-green-600 hover:text-green-700 hover:bg-green-50"
                    :disabled="loading"
                    @click.stop="save"
                    aria-label="Guardar"
                >
                    <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
                    <Check v-else class="h-3.5 w-3.5" />
                </Button>

                <!-- Botón cancelar -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6 text-muted-foreground hover:text-foreground"
                    :disabled="loading"
                    @click.stop="cancelEditing"
                    aria-label="Cancelar"
                >
                    <X class="h-3.5 w-3.5" />
                </Button>

                <!-- Indicador de carga (cuando no hay botón confirmar) -->
                <Loader2
                    v-if="loading && !showConfirmButton"
                    class="h-4 w-4 animate-spin text-muted-foreground"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.inline-edit-wrapper {
    min-height: 1.75rem;
}
</style>
