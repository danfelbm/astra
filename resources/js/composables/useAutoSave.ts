import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, ref, watch, type Ref } from 'vue';
import { toast } from 'vue-sonner';

interface AutoSaveOptions {
    /** URL para el autoguardado */
    url: string;
    /** ID de la candidatura existente (opcional) */
    candidaturaId?: number | null;
    /** Tiempo de debounce en milisegundos (default: 3000) */
    debounceTime?: number;
    /** Si mostrar notificaciones de autoguardado (default: true) */
    showNotifications?: boolean;
    /** Si guardar en localStorage como respaldo (default: true) */
    useLocalStorage?: boolean;
    /** Clave para localStorage */
    localStorageKey?: string;
}

interface AutoSaveState {
    /** Estado actual del autoguardado */
    status: 'idle' | 'saving' | 'saved' | 'error';
    /** Último timestamp de guardado exitoso */
    lastSaved: Date | null;
    /** Mensaje del último error (si hubo) */
    lastError: string | null;
    /** ID de la candidatura (se actualiza después del primer guardado) */
    candidaturaId: number | null;
}

export function useAutoSave(
    formData: Ref<Record<string, any>>,
    options: AutoSaveOptions
) {
    const {
        url,
        candidaturaId: initialCandidaturaId = null,
        debounceTime = 3000,
        showNotifications = true,
        useLocalStorage = true,
        localStorageKey = 'candidatura_draft',
    } = options;

    // Estado del autoguardado
    const state = ref<AutoSaveState>({
        status: 'idle',
        lastSaved: null,
        lastError: null,
        candidaturaId: initialCandidaturaId,
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
                formulario_data: formData.value,
                timestamp: new Date().toISOString(),
                candidaturaId: state.value.candidaturaId,
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
                formulario_data: formData.value,
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
            // Determinar la URL correcta basada en si existe una candidatura
            const saveUrl = state.value.candidaturaId 
                ? url.replace('autosave', `${state.value.candidaturaId}/autosave`)
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
                
                // Actualizar el ID de candidatura si es el primer guardado
                if (!state.value.candidaturaId && result.candidatura_id) {
                    state.value.candidaturaId = result.candidatura_id;
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
     * Configurar watcher para cambios en el formulario
     */
    const startWatching = () => {
        return watch(
            formData,
            () => {
                // Solo autoguardar si no hay errores previos o si el estado es idle/saved
                if (state.value.status !== 'error') {
                    debouncedAutoSave();
                }
            },
            { deep: true }
        );
    };

    /**
     * Detener el autoguardado
     */
    const stopAutoSave = () => {
        debouncedAutoSave.cancel();
    };

    /**
     * Reiniciar el estado
     */
    const reset = () => {
        stopAutoSave();
        state.value = {
            status: 'idle',
            lastSaved: null,
            lastError: null,
            candidaturaId: initialCandidaturaId,
        };
        clearLocalStorage();
    };

    /**
     * Recuperar borrador guardado
     */
    const restoreDraft = () => {
        const stored = loadFromLocalStorage();
        if (stored && stored.formulario_data) {
            if (showNotifications) {
                toast.info('Borrador recuperado', {
                    description: 'Se recuperaron los cambios no guardados',
                    duration: 3000,
                });
            }
            return stored;
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
        stopAutoSave,
        reset,
        restoreDraft,
        clearLocalStorage,
    };
}