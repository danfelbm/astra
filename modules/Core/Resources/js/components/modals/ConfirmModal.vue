<script setup lang="ts">
/**
 * ConfirmModal - Componente genérico de confirmación
 *
 * Uso con trigger:
 * <ConfirmModal variant="destructive" title="¿Eliminar?" @confirm="handleDelete">
 *   <template #trigger><Button>Eliminar</Button></template>
 * </ConfirmModal>
 *
 * Uso con v-model:
 * <ConfirmModal v-model:open="showDialog" title="¿Confirmar?" @confirm="handleConfirm" />
 */
import { computed, ref } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@modules/Core/Resources/js/components/ui/alert-dialog';
import { AlertTriangle, AlertCircle, Info, Loader2 } from 'lucide-vue-next';

// Tipos de variantes disponibles
type Variant = 'destructive' | 'warning' | 'info' | 'default';

// Props del componente
interface Props {
    // Estado del modal (v-model:open)
    open?: boolean;

    // Contenido
    title: string;
    description?: string;

    // Variante visual
    variant?: Variant;

    // Textos de botones
    confirmText?: string;
    cancelText?: string;

    // Estado de carga
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: undefined,
    variant: 'default',
    cancelText: 'Cancelar',
    loading: false,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

// Estado interno (para cuando se usa con trigger)
const internalOpen = ref(false);

// Estado controlado: usa prop si está definida, sino usa estado interno
const isOpen = computed({
    get: () => props.open !== undefined ? props.open : internalOpen.value,
    set: (value) => {
        if (props.open !== undefined) {
            emit('update:open', value);
        } else {
            internalOpen.value = value;
        }
    }
});

// Configuración por variante
const variantConfig = computed(() => {
    const configs: Record<Variant, {
        icon: typeof AlertTriangle | typeof AlertCircle | typeof Info | null;
        iconClass: string;
        buttonClass: string;
        defaultConfirmText: string;
    }> = {
        destructive: {
            icon: AlertTriangle,
            iconClass: 'text-destructive',
            // Usar !important para forzar texto blanco sobre fondo rojo
            buttonClass: 'bg-destructive hover:bg-destructive/90 !text-white',
            defaultConfirmText: 'Eliminar',
        },
        warning: {
            icon: AlertCircle,
            iconClass: 'text-orange-600',
            buttonClass: 'bg-orange-600 hover:bg-orange-700 !text-white',
            defaultConfirmText: 'Continuar',
        },
        info: {
            icon: Info,
            iconClass: 'text-primary',
            buttonClass: 'bg-primary hover:bg-primary/90 text-primary-foreground',
            defaultConfirmText: 'Aceptar',
        },
        default: {
            icon: null,
            iconClass: '',
            buttonClass: 'bg-primary hover:bg-primary/90 text-primary-foreground',
            defaultConfirmText: 'Confirmar',
        },
    };
    return configs[props.variant];
});

// Texto del botón de confirmación
const confirmButtonText = computed(() =>
    props.confirmText || variantConfig.value.defaultConfirmText
);

// Handler para confirmar
const handleConfirm = () => {
    emit('confirm');
    // No cerramos automáticamente si está en loading
    if (!props.loading) {
        isOpen.value = false;
    }
};

// Handler para cancelar
const handleCancel = () => {
    emit('cancel');
    isOpen.value = false;
};
</script>

<template>
    <AlertDialog v-model:open="isOpen">
        <!-- Slot para trigger (opcional) -->
        <AlertDialogTrigger v-if="$slots.trigger" as-child>
            <slot name="trigger" />
        </AlertDialogTrigger>

        <AlertDialogContent>
            <AlertDialogHeader>
                <!-- Header con icono y título -->
                <div class="flex gap-3">
                    <!-- Icono de variante -->
                    <component
                        v-if="variantConfig.icon"
                        :is="variantConfig.icon"
                        class="h-5 w-5 mt-0.5 flex-shrink-0"
                        :class="variantConfig.iconClass"
                        aria-hidden="true"
                    />

                    <div class="flex-1">
                        <AlertDialogTitle>{{ title }}</AlertDialogTitle>

                        <!-- Descripción: slot o prop -->
                        <AlertDialogDescription v-if="$slots.description || description" class="mt-2">
                            <slot name="description">
                                {{ description }}
                            </slot>
                        </AlertDialogDescription>
                    </div>
                </div>
            </AlertDialogHeader>

            <!-- Slot default para contenido adicional -->
            <div v-if="$slots.default" class="py-2">
                <slot />
            </div>

            <AlertDialogFooter>
                <AlertDialogCancel
                    @click="handleCancel"
                    :disabled="loading"
                >
                    {{ cancelText }}
                </AlertDialogCancel>

                <AlertDialogAction
                    @click="handleConfirm"
                    :class="variantConfig.buttonClass"
                    :disabled="loading"
                >
                    <Loader2
                        v-if="loading"
                        class="mr-2 h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />
                    {{ confirmButtonText }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
