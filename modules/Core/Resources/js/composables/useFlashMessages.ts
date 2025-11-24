import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

/**
 * Composable para manejar mensajes flash de Laravel y mostrarlos como toasts.
 * Los mensajes flash vienen desde el backend con ->with('success', 'mensaje')
 * o ->withErrors(['error' => 'mensaje'])
 */
export function useFlashMessages() {
    const page = usePage();

    // Watch para mensajes de éxito
    watch(
        () => page.props.flash?.success,
        (message) => {
            if (message) {
                toast.success(message as string);
            }
        }
    );

    // Watch para mensajes de error
    watch(
        () => page.props.flash?.error,
        (message) => {
            if (message) {
                toast.error(message as string);
            }
        }
    );

    // Watch para mensajes de advertencia
    watch(
        () => page.props.flash?.warning,
        (message) => {
            if (message) {
                toast.warning(message as string);
            }
        }
    );

    // Watch para mensajes informativos
    watch(
        () => page.props.flash?.info,
        (message) => {
            if (message) {
                toast.info(message as string);
            }
        }
    );
}
