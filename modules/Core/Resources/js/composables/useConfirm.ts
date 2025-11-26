/**
 * useConfirm - Composable para confirmaciones programáticas
 *
 * Uso:
 * const { confirm, ConfirmProvider } = useConfirm();
 *
 * const handleDelete = async () => {
 *   const confirmed = await confirm({
 *     title: '¿Eliminar?',
 *     description: 'Esta acción no se puede deshacer.',
 *     variant: 'destructive'
 *   });
 *   if (confirmed) {
 *     router.delete('/items/1');
 *   }
 * };
 *
 * // En template (una vez por página):
 * <ConfirmProvider />
 */
import { ref, defineComponent, h, type Ref, type Component } from 'vue';
import ConfirmModal from '@modules/Core/Resources/js/components/modals/ConfirmModal.vue';

// Tipos de variantes
type Variant = 'destructive' | 'warning' | 'info' | 'default';

// Opciones para el método confirm
export interface ConfirmOptions {
    title: string;
    description?: string;
    variant?: Variant;
    confirmText?: string;
    cancelText?: string;
}

// Estado global compartido (singleton)
const isOpen = ref(false);
const isLoading = ref(false);
const currentOptions: Ref<ConfirmOptions | null> = ref(null);

// Promise resolve/reject handlers
let resolvePromise: ((value: boolean) => void) | null = null;

/**
 * Componente Provider que renderiza el modal
 * Debe incluirse una vez en el template de cada página que use useConfirm
 */
const ConfirmProvider = defineComponent({
    name: 'ConfirmProvider',
    setup() {
        // Handler cuando se confirma
        const handleConfirm = () => {
            if (resolvePromise) {
                resolvePromise(true);
                resolvePromise = null;
            }
            isOpen.value = false;
            currentOptions.value = null;
        };

        // Handler cuando se cancela
        const handleCancel = () => {
            if (resolvePromise) {
                resolvePromise(false);
                resolvePromise = null;
            }
            isOpen.value = false;
            currentOptions.value = null;
        };

        // Handler cuando se cierra (ej: ESC o click fuera)
        const handleUpdateOpen = (value: boolean) => {
            if (!value) {
                handleCancel();
            }
        };

        return () => {
            if (!currentOptions.value) {
                return null;
            }

            return h(ConfirmModal, {
                open: isOpen.value,
                'onUpdate:open': handleUpdateOpen,
                title: currentOptions.value.title,
                description: currentOptions.value.description,
                variant: currentOptions.value.variant || 'default',
                confirmText: currentOptions.value.confirmText,
                cancelText: currentOptions.value.cancelText,
                loading: isLoading.value,
                onConfirm: handleConfirm,
                onCancel: handleCancel,
            });
        };
    },
});

/**
 * Método para mostrar el diálogo de confirmación
 * Retorna una Promise que resuelve true si se confirma, false si se cancela
 */
const confirm = (options: ConfirmOptions): Promise<boolean> => {
    return new Promise((resolve) => {
        // Guardar el resolver para usarlo después
        resolvePromise = resolve;

        // Configurar opciones y abrir
        currentOptions.value = options;
        isOpen.value = true;
    });
};

/**
 * Cerrar el diálogo programáticamente
 */
const close = () => {
    if (resolvePromise) {
        resolvePromise(false);
        resolvePromise = null;
    }
    isOpen.value = false;
    currentOptions.value = null;
};

/**
 * Establecer estado de loading
 */
const setLoading = (value: boolean) => {
    isLoading.value = value;
};

// Interfaz del retorno del composable
export interface UseConfirmReturn {
    isOpen: Ref<boolean>;
    isLoading: Ref<boolean>;
    options: Ref<ConfirmOptions | null>;
    confirm: (options: ConfirmOptions) => Promise<boolean>;
    close: () => void;
    setLoading: (value: boolean) => void;
    ConfirmProvider: Component;
}

/**
 * Hook principal
 */
export function useConfirm(): UseConfirmReturn {
    return {
        isOpen,
        isLoading,
        options: currentOptions,
        confirm,
        close,
        setLoading,
        ConfirmProvider,
    };
}

export default useConfirm;
