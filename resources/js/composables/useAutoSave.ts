import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, ref, watch, type Ref } from 'vue';
import { toast } from 'vue-sonner';

interface AutoSaveOptions {
    /** URL para el autoguardado */
    url: string;
    /** ID del recurso existente (opcional) - puede ser candidatura, formulario, etc. */
    resourceId?: number | null;
    /** Nombre del campo ID en la respuesta (default: 'candidatura_id') */
    resourceIdField?: string;
    /** Tiempo de debounce en milisegundos (default: 3000) */
    debounceTime?: number;
    /** Si mostrar notificaciones de autoguardado (default: true) */
    showNotifications?: boolean;
    /** Si guardar en localStorage como respaldo (default: true) */
    useLocalStorage?: boolean;
    /** Clave para localStorage */
    localStorageKey?: string;
    /** Datos adicionales para enviar con cada petición */
    additionalData?: Record<string, any>;
}

interface AutoSaveState {
    /** Estado actual del autoguardado */
    status: 'idle' | 'saving' | 'saved' | 'error';
    /** Último timestamp de guardado exitoso */
    lastSaved: Date | null;
    /** Mensaje del último error (si hubo) */
    lastError: string | null;
    /** ID del recurso (se actualiza después del primer guardado) */
    resourceId: number | null;
}

export function useAutoSave(
    formData: Ref<Record<string, any>>,
    options: AutoSaveOptions
) {
    const {
        url,
        resourceId: initialResourceId = null,
        resourceIdField = 'candidatura_id',
        debounceTime = 3000,
        showNotifications = true,
        useLocalStorage = true,
        localStorageKey = 'draft',
        additionalData = {},
    } = options;

    // Estado del autoguardado
    const state = ref<AutoSaveState>({
        status: 'idle',
        lastSaved: null,
        lastError: null,
        resourceId: initialResourceId,
    });

    // Estado computado para facilitar el acceso
    const isSaving = computed(() => state.value.status === 'saving');
    const hasSaved = computed(() => state.value.lastSaved !== null);
    const hasError = computed(() => state.value.status === 'error');

    /**
     * Guardar en localStorage como respaldo
     */
    const saveToLocalStorage = () => {
        if (!useLocalStorage) return;

        try {
            const dataToStore = {
                data: formData.value,
                timestamp: new Date().toISOString(),
                resourceId: state.value.resourceId,
                additionalData,
            };
            localStorage.setItem(localStorageKey, JSON.stringify(dataToStore));
        } catch (error) {
            console.error('Error guardando en localStorage:', error);
        }
    };

    /**
     * Recuperar datos de localStorage
     */
    const loadFromLocalStorage = () => {
        if (!useLocalStorage) return null;

        try {
            const stored = localStorage.getItem(localStorageKey);
            if (stored) {
                const parsed = JSON.parse(stored);
                // Solo recuperar si es reciente (menos de 24 horas)
                const storedDate = new Date(parsed.timestamp);
                const hoursDiff = (Date.now() - storedDate.getTime()) / (1000 * 60 * 60);
                
                if (hoursDiff < 24) {
                    return parsed;
                } else {
                    // Limpiar datos antiguos
                    localStorage.removeItem(localStorageKey);
                }
            }
        } catch (error) {
            console.error('Error cargando desde localStorage:', error);
        }
        
        return null;
    };

    /**
     * Limpiar localStorage
     */
    const clearLocalStorage = () => {
        if (useLocalStorage) {
            localStorage.removeItem(localStorageKey);
        }
    };

    /**
     * Renovar token CSRF
     */
    const refreshCSRFToken = async (): Promise<string | null> => {
        try {
            // Hacer una petición GET para obtener un nuevo token CSRF
            const response = await fetch(window.location.href, {
                method: 'GET',
                credentials: 'same-origin',
            });
            
            if (response.ok) {
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const tokenElement = doc.querySelector('meta[name="csrf-token"]');
                
                if (tokenElement) {
                    const newToken = tokenElement.getAttribute('content');
                    
                    // Actualizar el token en el DOM actual
                    const currentTokenElement = document.querySelector('meta[name="csrf-token"]');
                    if (currentTokenElement && newToken) {
                        currentTokenElement.setAttribute('content', newToken);
                        return newToken;
                    }
                }
            }
        } catch (error) {
            console.error('Error renovando token CSRF:', error);
        }
        
        return null;
    };

    /**
     * Realizar petición con manejo automático de CSRF
     */
    const makeRequest = async (saveUrl: string, token: string, retryCount = 0): Promise<Response> => {
        const response = await fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                ...additionalData,
                formulario_data: formData.value,
                respuestas: formData.value, // Para compatibilidad con formularios
            }),
        });

        // Si es error 419 y no hemos reintentado más de una vez
        if (response.status === 419 && retryCount < 1) {
            const newToken = await refreshCSRFToken();
            
            if (newToken) {
                if (showNotifications) {
                    toast.info('Renovando sesión...', {
                        description: 'Se detectó una sesión expirada, renovando automáticamente',
                        duration: 2000,
                    });
                }
                
                // Reintentar con el nuevo token
                return makeRequest(saveUrl, newToken, retryCount + 1);
            }
        }

        return response;
    };

    /**
     * Realizar el autoguardado
     */
    const performAutoSave = async () => {
        // No guardar si ya está guardando
        if (state.value.status === 'saving') return;

        // Verificar si hay datos para guardar
        if (!formData.value || Object.keys(formData.value).length === 0) {
            return;
        }

        state.value.status = 'saving';
        state.value.lastError = null;

        try {
            // Determinar la URL correcta basada en si existe un recurso
            const saveUrl = state.value.resourceId 
                ? url.replace('autosave', `${state.value.resourceId}/autosave`)
                : url;

            // Obtener token CSRF actual
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            if (!csrfToken) {
                throw new Error('Token CSRF no encontrado');
            }

            // Realizar la petición con manejo automático de CSRF
            const response = await makeRequest(saveUrl, csrfToken);

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                state.value.status = 'saved';
                state.value.lastSaved = new Date();
                
                // Actualizar el ID del recurso si es el primer guardado
                if (!state.value.resourceId) {
                    // Buscar el ID en diferentes campos posibles
                    const possibleId = result[resourceIdField] || result.respuesta_id || result.candidatura_id || result.id;
                    if (possibleId) {
                        state.value.resourceId = possibleId;
                    }
                }

                // Guardar en localStorage como respaldo
                saveToLocalStorage();

                // Mostrar notificación discreta
                if (showNotifications) {
                    toast.success('Cambios guardados', {
                        description: `Autoguardado a las ${result.timestamp || new Date().toLocaleTimeString()}`,
                        duration: 2000,
                    });
                }
            } else {
                throw new Error(result.message || 'Error al guardar');
            }
        } catch (error) {
            state.value.status = 'error';
            state.value.lastError = error instanceof Error ? error.message : 'Error desconocido';
            
            // Guardar en localStorage aunque falle el servidor
            saveToLocalStorage();

            // Manejar errores específicos
            if (error instanceof Error && error.message.includes('419')) {
                if (showNotifications) {
                    toast.warning('Sesión expirada', {
                        description: 'Recarga la página para continuar. Los cambios se guardaron localmente.',
                        duration: 5000,
                    });
                }
            } else {
                if (showNotifications) {
                    toast.error('Error al guardar', {
                        description: 'Los cambios se guardaron localmente',
                        duration: 3000,
                    });
                }
            }

            console.error('Error en autoguardado:', error);
        }
    };

    /**
     * Función debounced para el autoguardado
     */
    const debouncedAutoSave = debounce(performAutoSave, debounceTime);

    /**
     * Guardar manualmente (sin debounce)
     */
    const saveNow = () => {
        debouncedAutoSave.cancel();
        return performAutoSave();
    };

    /**
     * Watcher para cambios en el formulario
     */
    let stopWatchingFn: (() => void) | null = null;
    
    /**
     * Configurar watcher para cambios en el formulario
     */
    const startWatching = () => {
        // Detener watcher anterior si existe
        if (stopWatchingFn) {
            stopWatchingFn();
        }
        
        stopWatchingFn = watch(
            formData,
            () => {
                // Solo autoguardar si no hay errores previos o si el estado es idle/saved
                if (state.value.status !== 'error') {
                    debouncedAutoSave();
                }
            },
            { deep: true }
        );
        
        return stopWatchingFn;
    };

    /**
     * Detener el autoguardado y el watcher
     */
    const stopWatching = () => {
        debouncedAutoSave.cancel();
        if (stopWatchingFn) {
            stopWatchingFn();
            stopWatchingFn = null;
        }
    };
    
    /**
     * Alias para compatibilidad con código existente
     */
    const stopAutoSave = stopWatching;

    /**
     * Reiniciar el estado
     */
    const reset = () => {
        stopAutoSave();
        state.value = {
            status: 'idle',
            lastSaved: null,
            lastError: null,
            resourceId: initialResourceId,
        };
        clearLocalStorage();
    };

    /**
     * Recuperar borrador guardado
     */
    const restoreDraft = () => {
        const stored = loadFromLocalStorage();
        if (stored && (stored.data || stored.formulario_data)) {
            if (showNotifications) {
                toast.info('Borrador recuperado', {
                    description: 'Se recuperaron los cambios no guardados',
                    duration: 3000,
                });
            }
            // Compatibilidad con formato antiguo y nuevo
            return {
                ...stored,
                data: stored.data || stored.formulario_data,
            };
        }
        return null;
    };

    return {
        // Estado
        state: computed(() => state.value),
        isSaving,
        hasSaved,
        hasError,
        
        // Métodos
        saveNow,
        startWatching,
        stopWatching,
        stopAutoSave, // Alias para compatibilidad
        reset,
        restoreDraft,
        clearLocalStorage,
    };
}