<script setup lang="ts">
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Checkbox } from "@modules/Core/Resources/js/components/ui/checkbox";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { RadioGroup, RadioGroupItem } from "@modules/Core/Resources/js/components/ui/radio-group";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@modules/Core/Resources/js/components/ui/tabs";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { DateTimePicker } from "@modules/Core/Resources/js/components/ui/datetime-picker";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, Trash2, Eye, Upload, X, History, Clock, CheckCircle, XCircle, AlertCircle, Search, ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';

// Import new reusable components
import DynamicFormBuilder from "@modules/Core/Resources/js/components/forms/DynamicFormBuilder.vue";
import GeographicRestrictions from "@modules/Core/Resources/js/components/forms/GeographicRestrictions.vue";
import TimezoneSelector from "@modules/Core/Resources/js/components/forms/TimezoneSelector.vue";
import CsvImportWizard from "@modules/Core/Resources/js/components/imports/CsvImportWizard.vue";
import AddUsersModal from "@modules/Core/Resources/js/components/modals/AddUsersModal.vue";
import type { FormField, GeographicRestrictions as GeographicRestrictionsType } from "@modules/Core/Resources/js/types/forms";

interface Categoria {
    id: number;
    nombre: string;
    descripcion?: string;
    activa: boolean;
}

// FormField interface is now imported from @/types/forms

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    categoria_id: number;
    formulario_config: FormField[];
    fecha_inicio: string;
    fecha_fin: string;
    estado: 'borrador' | 'activa' | 'finalizada';
    resultados_publicos: boolean;
    allow_tokens_download: boolean;
    fecha_publicacion_resultados?: string;
    limite_censo?: string;
    mensaje_limite_censo?: string;
    timezone: string;
    territorios_ids?: number[];
    departamentos_ids?: number[];
    municipios_ids?: number[];
    localidades_ids?: number[];
    votantes?: Array<{
        id: number;
        name: string;
        email: string;
        documento_identidad?: string;
        telefono?: string;
    }>;
    votantes_total?: number;
}

interface CsvImport {
    id: number;
    original_filename: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    progress_percentage: number;
    successful_rows: number;
    failed_rows: number;
    created_at: string;
    created_by: {
        name: string;
    };
}

// Geographic interfaces are now imported from @/types/forms

// Keep local interfaces for compatibility
interface Territorio { id: number; nombre: string; }
interface Departamento { id: number; nombre: string; territorio_id: number; }
interface Municipio { id: number; nombre: string; departamento_id: number; }
interface Localidad { id: number; nombre: string; municipio_id: number; }

interface Cargo {
    id: number;
    nombre: string;
    ruta_jerarquica?: string;
    es_cargo: boolean;
}

interface PeriodoElectoral {
    id: number;
    nombre: string;
    fecha_inicio: string;
    fecha_fin: string;
}

interface Convocatoria {
    id: number;
    nombre: string;
    cargo?: {
        id: number | null;
        nombre: string | null;
        ruta_jerarquica: string | null;
    };
    periodo_electoral?: {
        id: number | null;
        nombre: string | null;
    };
    estado_temporal?: string;
    postulaciones_aprobadas?: number;
}

interface Props {
    categorias: Categoria[];
    votacion?: Votacion | null;
    cargos?: Cargo[];
    periodosElectorales?: PeriodoElectoral[];
    convocatorias?: Convocatoria[];
    canManageVoters?: boolean;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.votacion);
const canEditVotantes = computed(() => isEditing.value && props.votacion?.estado === 'borrador' && props.canManageVoters);

// Inicialización de props

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Votaciones', href: '/admin/votaciones' },
    { title: isEditing.value ? 'Editar' : 'Crear', href: '#' },
];

// Función para inicializar datos del formulario
const getInitialFormData = () => {
    if (!props.votacion) {
        return {
            titulo: '',
            descripcion: '',
            categoria_id: '',
            fecha_inicio: null,
            fecha_fin: null,
            estado: 'borrador',
            resultados_publicos: false,
            allow_tokens_download: false,
            fecha_publicacion_resultados: null,
            limite_censo: null,
            mensaje_limite_censo: '',
            formulario_config: [],
            timezone: 'America/Bogota',
            territorios_ids: [],
            departamentos_ids: [],
            municipios_ids: [],
            localidades_ids: [],
        };
    }

    const votacion = props.votacion;
    
    return {
        titulo: votacion.titulo || '',
        descripcion: votacion.descripcion || '',
        categoria_id: votacion.categoria_id ? String(votacion.categoria_id) : '',
        fecha_inicio: votacion.fecha_inicio || null,
        fecha_fin: votacion.fecha_fin || null,
        estado: votacion.estado || 'borrador',
        resultados_publicos: !!votacion.resultados_publicos,
        allow_tokens_download: !!votacion.allow_tokens_download,
        fecha_publicacion_resultados: votacion.fecha_publicacion_resultados || null,
        limite_censo: votacion.limite_censo || null,
        mensaje_limite_censo: votacion.mensaje_limite_censo || '',
        formulario_config: votacion.formulario_config || [],
        timezone: votacion.timezone || 'America/Bogota',
        territorios_ids: votacion.territorios_ids || [],
        departamentos_ids: votacion.departamentos_ids || [],
        municipios_ids: votacion.municipios_ids || [],
        localidades_ids: votacion.localidades_ids || [],
    };
};

// Formulario principal
const form = useForm(getInitialFormData());

// Tab actual
const activeTab = ref('basicos');

// Form builder is now handled by DynamicFormBuilder component

// Estados disponibles
const estadosDisponibles = [
    { value: 'borrador', label: 'Borrador' },
    { value: 'activa', label: 'Activa' },
    { value: 'finalizada', label: 'Finalizada' },
];

// Timezone data is now handled by TimezoneSelector component

// Geographic data is now handled by GeographicRestrictions component
// Create computed for geographic restrictions
const geographicRestrictions = computed({
    get: () => ({
        territorios_ids: form.territorios_ids || [],
        departamentos_ids: form.departamentos_ids || [],
        municipios_ids: form.municipios_ids || [],
        localidades_ids: form.localidades_ids || [],
    }),
    set: (value: GeographicRestrictionsType) => {
        form.territorios_ids = value.territorios_ids;
        form.departamentos_ids = value.departamentos_ids;
        form.municipios_ids = value.municipios_ids;
        form.localidades_ids = value.localidades_ids;
    }
});

// Geographic functions are now handled by GeographicRestrictions component

// Form field functions are now handled by DynamicFormBuilder component

// Validación de fechas
const fechaFinError = computed(() => {
    if (!form.fecha_fin || !form.fecha_inicio) return '';
    if (form.fecha_fin <= form.fecha_inicio) {
        return 'La fecha de fin debe ser posterior a la fecha de inicio';
    }
    return '';
});

// Enviar formulario
const submit = () => {
    // Validar que los datos básicos estén completos
    if (!canProceedToFormulario.value) {
        activeTab.value = 'basicos';
        alert('Por favor completa todos los campos requeridos en la pestaña "Datos Básicos" antes de crear la votación.');
        return;
    }
    
    if (form.formulario_config.length === 0) {
        alert('Por favor agrega al menos un campo al formulario antes de crear la votación.');
        return;
    }
    
    if (isEditing.value) {
        form.put(`/admin/votaciones/${props.votacion!.id}`, {
            onError: (errors) => {
                // Mostrar el primer error encontrado
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    alert(`Error: ${Array.isArray(firstError) ? firstError[0] : firstError}`);
                }
            }
        });
    } else {
        form.post('/admin/votaciones', {
            onError: (errors) => {
                // Mostrar el primer error encontrado
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    alert(`Error: ${Array.isArray(firstError) ? firstError[0] : firstError}`);
                }
            }
        });
    }
};

// Navegación entre tabs
const canProceedToFormulario = computed(() => {
    return form.titulo && form.categoria_id && form.fecha_inicio && form.fecha_fin && form.timezone;
});

const canProceedToVotantes = computed(() => {
    return canProceedToFormulario.value && form.formulario_config.length > 0;
});

// Gestión de votantes con paginación
const votantesAsignados = ref(props.votacion?.votantes || []);
const votantesTotal = ref(props.votacion?.votantes_total || 0);
const votantesCurrentPage = ref(1);
const votantesPerPage = ref(50);
const votantesLoading = ref(false);
const votantesSearchQuery = ref('');
const votantesLastPage = ref(1);
const showImportWizard = ref(false);
const showAddUsersModal = ref(false); // Modal para añadir votantes

// Estado de importaciones recientes
const recentImports = ref<CsvImport[]>([]);

// Cargar importaciones recientes
const loadRecentImports = async () => {
    if (!isEditing.value || !props.votacion?.id) return;
    
    try {
        const response = await fetch(`/admin/votaciones/${props.votacion.id}/imports/recent`);
        if (response.ok) {
            const data = await response.json();
            recentImports.value = data;
        }
    } catch (error) {
        console.error('Error loading recent imports:', error);
    }
};

// Navegación a páginas de importación
const viewImportProgress = (importId: number) => {
    router.get(`/admin/imports/${importId}`);
};

const viewAllImports = () => {
    if (props.votacion?.id) {
        router.get(`/admin/votaciones/${props.votacion.id}/imports`);
    }
};

// Obtener configuración de estado para las importaciones
const getImportStatusConfig = (status: CsvImport['status']) => {
    switch (status) {
        case 'pending':
            return { icon: Clock, label: 'Pendiente', class: 'text-yellow-600' };
        case 'processing':
            return { icon: Clock, label: 'Procesando', class: 'text-blue-600' };
        case 'completed':
            return { icon: CheckCircle, label: 'Completada', class: 'text-green-600' };
        case 'failed':
            return { icon: XCircle, label: 'Fallida', class: 'text-red-600' };
    }
};

// Formatear fecha
const formatImportDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('es-ES', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Ya no necesitamos cargar todos los votantes disponibles
// El modal AddUsersModal maneja la búsqueda con lazy loading

// Importar debounce para búsqueda
import { debounce } from 'lodash-es';

// Cargar votantes asignados con paginación y búsqueda
const loadAssignedVoters = async (page: number = 1, search: string = '') => {
    if (!props.votacion?.id) return;
    
    votantesLoading.value = true;
    
    try {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: votantesPerPage.value.toString(),
            ...(search && { query: search })
        });
        
        const response = await fetch(`/admin/votaciones/${props.votacion.id}/assigned-voters?${params}`);
        const data = await response.json();
        
        votantesAsignados.value = data.data || [];
        votantesTotal.value = data.total || 0;
        votantesCurrentPage.value = data.current_page || 1;
        votantesLastPage.value = data.last_page || 1;
    } catch (error) {
        console.error('Error cargando votantes:', error);
    } finally {
        votantesLoading.value = false;
    }
};

// Debounce para búsqueda de votantes asignados
const searchAssignedVoters = debounce(() => {
    votantesCurrentPage.value = 1;
    loadAssignedVoters(1, votantesSearchQuery.value);
}, 300);

// Cambiar página de votantes
const changeVotantesPage = (page: number) => {
    votantesCurrentPage.value = page;
    loadAssignedVoters(page, votantesSearchQuery.value);
};

// Watch para búsqueda de votantes
watch(votantesSearchQuery, () => {
    searchAssignedVoters();
});

// Cargar votantes cuando se active el tab
watch(() => activeTab.value, (newTab) => {
    if (newTab === 'votantes' && props.votacion?.id && votantesTotal.value > 0) {
        // Solo cargar si no hemos cargado antes
        if (votantesAsignados.value.length === 0 || 
            (votantesAsignados.value.length < votantesTotal.value && votantesAsignados.value.length <= 50)) {
            loadAssignedVoters();
        }
    }
});

// Manejar adición de votantes desde el modal
const handleAddVotantes = (data: { userIds: number[]; extraData: Record<string, any> }) => {
    if (!data.userIds || data.userIds.length === 0 || !isEditing.value || !props.votacion?.id) return;
    
    router.post(`/admin/votaciones/${props.votacion.id}/votantes`, {
        votante_ids: data.userIds.map(id => id.toString())
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Actualizar el total y recargar la lista
            votantesTotal.value += data.userIds.length;
            // Recargar la página actual para reflejar los cambios
            loadAssignedVoters(votantesCurrentPage.value, votantesSearchQuery.value);
            // Cerrar el modal
            showAddUsersModal.value = false;
        },
        onError: (errors) => {
            console.error('Error adding votantes:', errors);
            alert('Error al agregar votantes');
        }
    });
};

// Remover votante
const removeVotante = (votanteId: number) => {
    if (!isEditing.value || !props.votacion?.id) return;
    
    router.delete(`/admin/votaciones/${props.votacion.id}/votantes`, {
        data: {
            votante_id: votanteId
        },
        preserveScroll: true,
        onSuccess: () => {
            // Actualizar el total y recargar la lista
            votantesTotal.value -= 1;
            // Si la página actual queda vacía, ir a la página anterior
            if (votantesAsignados.value.length === 1 && votantesCurrentPage.value > 1) {
                changeVotantesPage(votantesCurrentPage.value - 1);
            } else {
                loadAssignedVoters(votantesCurrentPage.value, votantesSearchQuery.value);
            }
        },
        onError: (errors) => {
            console.error('Error removing votante:', errors);
            alert('Error al remover votante');
        }
    });
};

// Manejar éxito de importación
const handleImportSuccess = (importId: number) => {
    showImportWizard.value = false;
    // Recargar listas de votantes
    loadRecentImports();
    
    // Redirigir a la página de progreso de importación
    router.get(`/admin/imports/${importId}`);
};

// Manejar cancelación del wizard
const handleImportCancel = () => {
    showImportWizard.value = false;
};

// Watcher para actualizar el formulario cuando cambien los props
watch(() => props.votacion, (newVotacion) => {
    if (newVotacion) {
        const newData = getInitialFormData();
        
        // Actualizar cada campo del formulario
        form.titulo = newData.titulo;
        form.descripcion = newData.descripcion;
        form.categoria_id = newData.categoria_id;
        form.fecha_inicio = newData.fecha_inicio;
        form.fecha_fin = newData.fecha_fin;
        form.estado = newData.estado;
        form.resultados_publicos = newData.resultados_publicos;
        form.allow_tokens_download = newData.allow_tokens_download;
        form.fecha_publicacion_resultados = newData.fecha_publicacion_resultados;
        form.formulario_config = newData.formulario_config;
        form.timezone = newData.timezone;
        
        // También cargar importaciones recientes cuando lleguen los datos
        if (isEditing.value) {
            loadRecentImports();
        }
    }
}, { immediate: true, deep: true });

// Debug watcher para resultados_publicos
watch(() => form.resultados_publicos, (newValue) => {
    console.log('resultados_publicos changed to:', newValue);
}, { immediate: true });

// Geographic data loading is now handled by GeographicRestrictions component
onMounted(() => {
    // Component-specific initialization if needed
});
</script>

<template>
    <Head :title="isEditing ? 'Editar Votación' : 'Nueva Votación'" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ isEditing ? 'Editar Votación' : 'Nueva Votación' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ isEditing ? 'Modifica los datos de la votación' : 'Crea una nueva votación paso a paso' }}
                    </p>
                </div>
            </div>

            <!-- Formulario con Tabs -->
            <div class="relative min-h-[70vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Card class="border-0 shadow-none h-full">
                    <CardContent class="p-6">
                    <Tabs v-model="activeTab" class="w-full">
                        <TabsList class="grid w-full grid-cols-3">
                            <TabsTrigger value="basicos">
                                <span class="flex items-center gap-2">
                                    <span class="rounded-full bg-primary text-primary-foreground w-5 h-5 text-xs flex items-center justify-center">1</span>
                                    Datos Básicos
                                </span>
                            </TabsTrigger>
                            <TabsTrigger value="formulario" :disabled="!isEditing && !canProceedToFormulario">
                                <span class="flex items-center gap-2">
                                    <span class="rounded-full bg-primary text-primary-foreground w-5 h-5 text-xs flex items-center justify-center">2</span>
                                    Formulario
                                </span>
                            </TabsTrigger>
                            <TabsTrigger value="votantes" :disabled="!isEditing">
                                <span class="flex items-center gap-2">
                                    <span class="rounded-full bg-primary text-primary-foreground w-5 h-5 text-xs flex items-center justify-center">3</span>
                                    Votantes
                                </span>
                            </TabsTrigger>
                        </TabsList>

                        <!-- Tab 1: Datos Básicos -->
                        <TabsContent value="basicos" class="space-y-6">
                            <div class="grid gap-6 md:grid-cols-2">
                                <div class="space-y-4">
                                    <div>
                                        <Label for="titulo">Título *</Label>
                                        <Input
                                            id="titulo"
                                            v-model="form.titulo"
                                            placeholder="Nombre de la votación"
                                            :error="form.errors.titulo"
                                        />
                                        <p v-if="form.errors.titulo" class="text-sm text-destructive mt-1">
                                            {{ form.errors.titulo }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="descripcion">Descripción</Label>
                                        <Textarea
                                            id="descripcion"
                                            v-model="form.descripcion"
                                            placeholder="Descripción detallada de la votación"
                                            rows="4"
                                        />
                                        <p v-if="form.errors.descripcion" class="text-sm text-destructive mt-1">
                                            {{ form.errors.descripcion }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="categoria">Categoría *</Label>
                                        <Select v-model="form.categoria_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar categoría" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="categoria in categorias"
                                                    :key="categoria.id"
                                                    :value="categoria.id.toString()"
                                                >
                                                    {{ categoria.nombre }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.categoria_id" class="text-sm text-destructive mt-1">
                                            {{ form.errors.categoria_id }}
                                        </p>
                                    </div>

                                    <!-- Restricciones Geográficas -->
                                    <GeographicRestrictions 
                                        v-model="geographicRestrictions"
                                    />
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <Label for="fecha_inicio">Fecha y Hora de Inicio *</Label>
                                        <DateTimePicker
                                            v-model="form.fecha_inicio"
                                            placeholder="Seleccionar fecha y hora de inicio"
                                        />
                                        <p v-if="form.errors.fecha_inicio" class="text-sm text-destructive mt-1">
                                            {{ form.errors.fecha_inicio }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="fecha_fin">Fecha y Hora de Fin *</Label>
                                        <DateTimePicker
                                            v-model="form.fecha_fin"
                                            placeholder="Seleccionar fecha y hora de fin"
                                        />
                                        <p v-if="fechaFinError" class="text-sm text-destructive mt-1">
                                            {{ fechaFinError }}
                                        </p>
                                        <p v-if="form.errors.fecha_fin" class="text-sm text-destructive mt-1">
                                            {{ form.errors.fecha_fin }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="estado">Estado</Label>
                                        <Select v-model="form.estado">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="estado in estadosDisponibles"
                                                    :key="estado.value"
                                                    :value="estado.value"
                                                >
                                                    {{ estado.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <TimezoneSelector 
                                        v-model="form.timezone"
                                        required
                                        :error="form.errors.timezone"
                                    />

                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="resultados_publicos"
                                            :checked="form.resultados_publicos"
                                            @update:checked="form.resultados_publicos = $event"
                                        />
                                        <Label for="resultados_publicos">
                                            Resultados públicos
                                        </Label>
                                    </div>

                                    <Transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 transform scale-95"
                                        enter-to-class="opacity-100 transform scale-100"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 transform scale-100"
                                        leave-to-class="opacity-0 transform scale-95"
                                    >
                                        <div v-if="form.resultados_publicos" class="space-y-2">
                                        <Label for="fecha_publicacion_resultados">
                                            Fecha de publicación de resultados (opcional)
                                        </Label>
                                        <DateTimePicker
                                            id="fecha_publicacion_resultados"
                                            v-model="form.fecha_publicacion_resultados"
                                            placeholder="Dejar vacío para publicar al finalizar la votación"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Si se especifica una fecha, los resultados serán visibles desde esa fecha. 
                                            Si se deja vacío, los resultados solo serán visibles después de que termine la votación.
                                        </p>
                                        
                                        <div class="flex items-center space-x-2 mt-4">
                                            <Checkbox
                                                id="allow_tokens_download"
                                                :checked="form.allow_tokens_download"
                                                @update:checked="form.allow_tokens_download = $event"
                                            />
                                            <Label for="allow_tokens_download">
                                                Permitir descarga de tokens en CSV
                                            </Label>
                                        </div>
                                        <p class="text-xs text-muted-foreground ml-6">
                                            Permite a los usuarios descargar todos los tokens de verificación en formato CSV para análisis externos.
                                        </p>
                                        </div>
                                    </Transition>

                                    <div>
                                        <Label for="limite_censo">Límite del Censo (opcional)</Label>
                                        <DateTimePicker
                                            id="limite_censo"
                                            v-model="form.limite_censo"
                                            placeholder="Seleccionar fecha límite del censo"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Fecha de referencia para determinar disponibilidad de la votación según criterios externos como fecha de inscripción.
                                        </p>
                                        <p v-if="form.errors.limite_censo" class="text-sm text-destructive mt-1">
                                            {{ form.errors.limite_censo }}
                                        </p>
                                    </div>

                                    <div v-if="form.limite_censo">
                                        <Label for="mensaje_limite_censo">
                                            Mensaje de notificación sobre límite de censo (opcional)
                                        </Label>
                                        <Textarea
                                            id="mensaje_limite_censo"
                                            v-model="form.mensaje_limite_censo"
                                            placeholder="Mensaje a mostrar cuando un usuario no cumple con el límite del censo"
                                            rows="3"
                                            class="mt-1"
                                        />
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Este mensaje se mostrará si un usuario intenta registrarse pero su fecha de inscripción es posterior al límite del censo configurado.
                                        </p>
                                        <p v-if="form.errors.mensaje_limite_censo" class="text-sm text-destructive mt-1">
                                            {{ form.errors.mensaje_limite_censo }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between">
                                <div></div>
                                <div class="flex gap-2">
                                    <Button
                                        v-if="isEditing"
                                        @click="submit"
                                        :disabled="form.processing || !canProceedToFormulario"
                                        variant="default"
                                    >
                                        {{ form.processing ? 'Actualizando...' : 'Actualizar Votación' }}
                                    </Button>
                                    <Button
                                        @click="activeTab = 'formulario'"
                                        :disabled="!canProceedToFormulario"
                                        variant="outline"
                                    >
                                        Siguiente: Configurar Formulario
                                    </Button>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Tab 2: Constructor de Formulario -->
                        <TabsContent value="formulario" class="space-y-6">
                            <DynamicFormBuilder 
                                v-model="form.formulario_config"
                                title="Constructor de Formulario"
                                description="Agrega los campos que aparecerán en la votación"
                                :show-perfil-candidatura-config="true"
                                :show-convocatoria-config="true"
                                :cargos="props.cargos?.filter(c => c.es_cargo) || []"
                                :periodos-electorales="props.periodosElectorales || []"
                                :convocatorias="props.convocatorias || []"
                                context="votacion"
                            />

                            <div class="flex justify-between">
                                <Button variant="outline" @click="activeTab = 'basicos'">
                                    Anterior
                                </Button>
                                <div class="flex gap-2">
                                    <Button
                                        v-if="canEditVotantes && props.canManageVoters"
                                        variant="outline"
                                        @click="activeTab = 'votantes'"
                                        :disabled="form.formulario_config.length === 0"
                                    >
                                        Siguiente: Gestionar Votantes
                                    </Button>
                                    <Button
                                        @click="submit"
                                        :disabled="form.processing || form.formulario_config.length === 0"
                                    >
                                        {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear') }} Votación
                                    </Button>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Tab 3: Gestión de Votantes -->
                        <TabsContent value="votantes" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold">Gestión de Votantes</h3>
                                <p class="text-sm text-muted-foreground">
                                    Asigna los usuarios que podrán participar en esta votación
                                </p>
                            </div>

                            <!-- Mensaje de sin permisos -->
                            <Card v-if="!props.canManageVoters" class="border-yellow-200 bg-yellow-50">
                                <CardContent class="pt-6">
                                    <div class="flex items-center space-x-2 text-yellow-700">
                                        <AlertCircle class="h-5 w-5" />
                                        <p class="font-semibold">Sin permisos para manejar censo electoral</p>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">
                                        No tienes los permisos necesarios para gestionar el censo electoral de esta votación. 
                                        Contacta a un administrador si necesitas acceso a esta funcionalidad.
                                    </p>
                                </CardContent>
                            </Card>

                            <!-- Contenido completo solo si tiene permisos -->
                            <template v-if="props.canManageVoters">
                            <!-- Upload CSV -->
                            <Card v-if="!showImportWizard">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Upload class="h-5 w-5" />
                                        Importar Votantes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <p class="text-sm text-muted-foreground">
                                            Importa votantes desde un archivo CSV con mapeo dinámico de campos.
                                            Los usuarios importados serán asignados automáticamente a esta votación.
                                        </p>
                                        <p class="text-sm text-blue-600">
                                            <a href="/ejemplo-usuarios.csv" download class="underline">
                                                📥 Descargar plantilla CSV de ejemplo
                                            </a>
                                        </p>
                                        <Button 
                                            variant="outline" 
                                            @click="showImportWizard = true"
                                        >
                                            <Upload class="mr-2 h-4 w-4" />
                                            Iniciar Importación
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Wizard de Importación -->
                            <CsvImportWizard
                                v-if="showImportWizard && isEditing && votacion"
                                mode="votacion"
                                :votacion-id="votacion.id"
                                :votacion-titulo="votacion.titulo"
                                :redirect-on-success="false"
                                @success="handleImportSuccess"
                                @cancel="handleImportCancel"
                            />

                            <!-- Agregar votantes (selección múltiple) -->
                            <Card v-if="isEditing">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Plus class="h-5 w-5" />
                                        Agregar Votantes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Button 
                                        @click="showAddUsersModal = true"
                                        class="w-full"
                                        variant="outline"
                                    >
                                        <Plus class="h-4 w-4 mr-2" />
                                        Buscar y Añadir Votantes
                                    </Button>
                                    <p class="text-sm text-muted-foreground mt-2">
                                        Busca usuarios por nombre, email, documento o teléfono
                                    </p>
                                </CardContent>
                            </Card>

                            <!-- Historial de Importaciones -->
                            <Card v-if="isEditing">
                                <CardHeader>
                                    <CardTitle class="flex items-center justify-between">
                                        <span class="flex items-center gap-2">
                                            <History class="h-5 w-5" />
                                            Historial de Importaciones
                                        </span>
                                        <Button 
                                            v-if="recentImports.length > 0"
                                            variant="outline" 
                                            size="sm"
                                            @click="viewAllImports"
                                        >
                                            Ver Todas
                                        </Button>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div v-if="recentImports.length === 0" class="text-center py-6 text-muted-foreground">
                                        <History class="mx-auto h-8 w-8 mb-2" />
                                        <p>No hay importaciones registradas</p>
                                        <p class="text-sm">Las importaciones aparecerán aquí una vez que subas archivos CSV</p>
                                    </div>
                                    
                                    <div v-else class="space-y-3">
                                        <div 
                                            v-for="import_ in recentImports" 
                                            :key="import_.id"
                                            class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                                            @click="viewImportProgress(import_.id)"
                                        >
                                            <div class="flex items-center gap-3">
                                                <component 
                                                    :is="getImportStatusConfig(import_.status).icon"
                                                    :class="['h-4 w-4', getImportStatusConfig(import_.status).class]"
                                                />
                                                <div>
                                                    <p class="font-medium text-sm">{{ import_.original_filename }}</p>
                                                    <p class="text-xs text-muted-foreground">
                                                        Por {{ import_.created_by.name }} • {{ formatImportDate(import_.created_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                <Badge 
                                                    variant="outline"
                                                    :class="getImportStatusConfig(import_.status).class"
                                                >
                                                    {{ getImportStatusConfig(import_.status).label }}
                                                </Badge>
                                                
                                                <div v-if="import_.status === 'completed'" class="text-xs text-muted-foreground">
                                                    {{ import_.successful_rows }} exitosos
                                                    <span v-if="import_.failed_rows > 0">, {{ import_.failed_rows }} errores</span>
                                                </div>
                                                
                                                <div v-else-if="import_.status === 'processing'" class="text-xs text-muted-foreground">
                                                    {{ import_.progress_percentage }}%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Lista de votantes asignados con búsqueda y paginación -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Votantes Asignados ({{ votantesTotal }})</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <!-- Campo de búsqueda -->
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            v-model="votantesSearchQuery"
                                            type="text"
                                            placeholder="Buscar por nombre, email, documento o teléfono..."
                                            class="pl-10"
                                        />
                                    </div>
                                    
                                    <!-- Estado de carga -->
                                    <div v-if="votantesLoading" class="flex items-center justify-center py-8">
                                        <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                                        <span class="ml-2 text-muted-foreground">Cargando votantes...</span>
                                    </div>
                                    
                                    <!-- Lista de votantes -->
                                    <div v-else-if="votantesAsignados.length === 0" class="text-center py-8 text-muted-foreground">
                                        <p v-if="votantesSearchQuery">No se encontraron votantes que coincidan con "{{ votantesSearchQuery }}"</p>
                                        <p v-else>No hay votantes asignados a esta votación</p>
                                        <p class="text-sm">Importa un archivo CSV o agrega votantes manualmente</p>
                                    </div>
                                    
                                    <div v-else>
                                        <!-- Lista de votantes -->
                                        <div class="space-y-2 max-h-96 overflow-y-auto">
                                            <div
                                                v-for="votante in votantesAsignados"
                                                :key="votante.id"
                                                class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
                                            >
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium truncate">{{ votante.name }}</p>
                                                    <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                                        <span class="truncate">{{ votante.email }}</span>
                                                        <span v-if="votante.documento_identidad">Doc: {{ votante.documento_identidad }}</span>
                                                        <span v-if="votante.telefono">Tel: {{ votante.telefono }}</span>
                                                    </div>
                                                </div>
                                                <Button variant="ghost" size="sm" @click="removeVotante(votante.id)">
                                                    <Trash2 class="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </div>
                                        
                                        <!-- Paginación -->
                                        <div v-if="votantesLastPage > 1" class="flex items-center justify-between pt-4 border-t">
                                            <div class="text-sm text-muted-foreground">
                                                Mostrando {{ (votantesCurrentPage - 1) * votantesPerPage + 1 }} - 
                                                {{ Math.min(votantesCurrentPage * votantesPerPage, votantesTotal) }} 
                                                de {{ votantesTotal }} votantes
                                            </div>
                                            <div class="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    :disabled="votantesCurrentPage === 1"
                                                    @click="changeVotantesPage(votantesCurrentPage - 1)"
                                                >
                                                    <ChevronLeft class="h-4 w-4" />
                                                    Anterior
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    :disabled="votantesCurrentPage === votantesLastPage"
                                                    @click="changeVotantesPage(votantesCurrentPage + 1)"
                                                >
                                                    Siguiente
                                                    <ChevronRight class="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <div class="flex justify-between">
                                <Button variant="outline" @click="activeTab = 'formulario'">
                                    Anterior
                                </Button>
                                <Button @click="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Guardando...' : 'Actualizar' }} Votación
                                </Button>
                            </div>
                            </template>
                        </TabsContent>
                    </Tabs>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Modal para añadir votantes -->
        <AddUsersModal
            v-model="showAddUsersModal"
            title="Añadir Votantes"
            description="Selecciona los usuarios que deseas añadir como votantes de esta votación"
            :search-endpoint="`/admin/votaciones/${props.votacion?.id}/search-users`"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            submit-button-text="Añadir Votantes"
            empty-message="Escribe para buscar usuarios disponibles"
            no-results-message="No se encontraron usuarios con esa búsqueda"
            @submit="handleAddVotantes"
        />
    </AdminLayout>
</template>