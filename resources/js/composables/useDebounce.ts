import { ref } from 'vue';

/**
 * Composable para crear funciones con debounce
 * Útil para optimizar llamadas a APIs evitando múltiples llamadas consecutivas
 */
export function useDebounce() {
    const timeouts = ref<Map<string, NodeJS.Timeout>>(new Map());
    
    /**
     * Crea una función con debounce
     * @param fn Función a ejecutar con debounce
     * @param delay Tiempo de espera en milisegundos
     * @param key Clave única para identificar el timeout
     */
    function debounce<T extends (...args: any[]) => any>(
        fn: T,
        delay: number = 300,
        key: string = 'default'
    ): (...args: Parameters<T>) => void {
        return (...args: Parameters<T>) => {
            // Cancelar timeout anterior si existe
            const existingTimeout = timeouts.value.get(key);
            if (existingTimeout) {
                clearTimeout(existingTimeout);
            }
            
            // Crear nuevo timeout
            const newTimeout = setTimeout(() => {
                fn(...args);
                timeouts.value.delete(key);
            }, delay);
            
            timeouts.value.set(key, newTimeout);
        };
    }
    
    /**
     * Cancela todos los timeouts pendientes
     */
    function cancelAll() {
        timeouts.value.forEach(timeout => clearTimeout(timeout));
        timeouts.value.clear();
    }
    
    /**
     * Cancela un timeout específico por su clave
     */
    function cancel(key: string) {
        const timeout = timeouts.value.get(key);
        if (timeout) {
            clearTimeout(timeout);
            timeouts.value.delete(key);
        }
    }
    
    return {
        debounce,
        cancelAll,
        cancel,
    };
}