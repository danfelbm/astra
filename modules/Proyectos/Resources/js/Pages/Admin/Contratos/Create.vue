<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Switch } from '@modules/Core/Resources/js/components/ui/switch';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { AlertCircle, ArrowLeft, Save, Upload, UserPlus, X, Users, Clock, CheckCircle, Trash2 } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import { useFileUpload } from '@modules/Core/Resources/js/composables/useFileUpload';
import { useAutoSave } from '@modules/Core/Resources/js/composables/useAutoSave';
import CampoPersonalizadoInput from '@modules/Proyectos/Resources/js/components/CampoPersonalizadoInput.vue';
import ContratoUserSelect from '@modules/Proyectos/Resources/js/components/ContratoUserSelect.vue';
import ParticipantesManager from '@modules/Proyectos/Resources/js/components/ParticipantesManager.vue';
import AddUsersModal from '@modules/Core/Resources/js/components/modals/AddUsersModal.vue';
import FileUploadField from '@modules/Core/Resources/js/components/forms/FileUploadField.vue';
import { Separator } from '@modules/Core/Resources/js/components/ui/separator';

// Tipos
interface Proyecto {
    id: number;
    nombre: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    es_requerido: boolean;
    opciones?: string[];
    descripcion?: string;
    placeholder?: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Participante {
    user_id: number;
    rol: string;
    notas?: string;
}

// Props
const props = withDefaults(defineProps<{
    proyecto?: Proyecto;
    proyectos: Proyecto[];
    camposPersonalizados: CampoPersonalizado[];
    responsable?: User | null; // Solo el responsable específico (si existe)
    contraparte?: User | null; // Contraparte del sistema (si existe)
    borrador?: any; // Borrador existente del servidor
}>(), {
    responsable: null,
    contraparte: null,
    borrador: undefined
});

const toast = useToast();
const { uploadFiles } = useFileUpload();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
    { title: 'Crear Contrato', href: '/admin/contratos/create' },
];

// Form data
const form = useForm({
    proyecto_id: props.proyecto?.id || '',
    nombre: '',
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'borrador',
    tipo: 'servicio',
    monto_total: '',
    moneda: 'USD',
    responsable_id: 'none',
    contraparte_user_id: null as number | null,
    contraparte_nombre: '',
    contraparte_identificacion: '',
    contraparte_email: '',
    contraparte_telefono: '',
    participantes: [] as Participante[],
    archivo_pdf: null as File | null,
    archivos_paths: [] as string[],
    archivos_nombres: [] as string[],
    tipos_archivos: {} as Record<string, string>,
    observaciones: '',
    campos_personalizados: {} as Record<number, any>,
});

// Estado local
const archivoNombre = ref('');
const archivosSubidos = ref<any[]>([]);
const activeTab = ref('informacion');
const archivosPendientesCarga = ref<any[]>([]); // Archivos del borrador pendientes de cargar

// Estados para los modales de selección de usuarios
const showContraparteModal = ref(false);
const showParticipantesModal = ref(false);
const showResponsableModal = ref(false);

// Helper para obtener route
const { route } = window as any;

// Ref para la contraparte seleccionada
const contraparteSeleccionada = ref<User | null>(props.contraparte || null);

// Ref para el responsable seleccionado
const responsableSeleccionado = ref<User | null>(props.responsable || null);

// Campos extra para el modal de participantes
const extraFieldsParticipantes = computed(() => [
    {
        name: 'rol',
        label: 'Rol',
        type: 'select' as const,
        options: [
            { value: 'testigo', label: 'Testigo' },
            { value: 'revisor', label: 'Revisor' },
            { value: 'aprobador', label: 'Aprobador' },
            { value: 'observador', label: 'Observador' }
        ],
        value: 'testigo',
        required: true
    },
    {
        name: 'notas',
        label: 'Notas (opcional)',
        type: 'text' as const,
        value: '',
        required: false
    }
]);

// IDs excluidos para el modal de participantes
const excludedIdsParticipantes = computed(() => {
    const ids = form.participantes.map(p => p.user_id);
    if (form.contraparte_user_id) {
        ids.push(form.contraparte_user_id);
    }
    return ids;
});

// Computed para autosave
const formDataRef = computed(() => form.data());

// Configurar autosave para Create (como borrador)
const {
    state: autoSaveState,
    isSaving,
    hasSaved,
    hasError,
    startWatching,
    stopAutoSave,
    restoreDraft,
    clearLocalStorage
} = useAutoSave(formDataRef, {
    url: '/admin/contratos/autosave',
    resourceIdField: 'contrato_id',
    debounceTime: 3000,
    showNotifications: true,
    useLocalStorage: true,
    localStorageKey: 'contrato_draft_create'
});

// Computed
const monedas = [
    { value: 'USD', label: 'USD - Dólar Estadounidense' },
    { value: 'EUR', label: 'EUR - Euro' },
    { value: 'MXN', label: 'MXN - Peso Mexicano' },
    { value: 'COP', label: 'COP - Peso Colombiano' },
    { value: 'ARS', label: 'ARS - Peso Argentino' },
    { value: 'CLP', label: 'CLP - Peso Chileno' },
    { value: 'PEN', label: 'PEN - Sol Peruano' },
];

const camposAgrupados = computed(() => {
    const grupos = {
        informacion: [] as CampoPersonalizado[],
        financiero: [] as CampoPersonalizado[],
        otros: [] as CampoPersonalizado[],
    };

    props.camposPersonalizados.forEach(campo => {
        // Agrupar por tipo o lógica personalizada
        if (campo.slug.includes('financ') || campo.slug.includes('monto') || campo.slug.includes('precio')) {
            grupos.financiero.push(campo);
        } else if (campo.slug.includes('info') || campo.slug.includes('descrip')) {
            grupos.informacion.push(campo);
        } else {
            grupos.otros.push(campo);
        }
    });

    return grupos;
});

// Métodos
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.archivo_pdf = target.files[0];
        archivoNombre.value = target.files[0].name;
    }
};

// Handler para múltiples archivos (recibe File[] directamente del componente)
const handleFilesSelected = async (files: File[]) => {
    try {
        // Subir todos los archivos
        const uploadedFiles = await uploadFiles(files, {
            module: 'contratos',
            fieldId: 'archivos',
            folder: 'contratos',
            maxSize: 10 * 1024 * 1024, // 10MB
        });

        // Extraer paths y nombres de los archivos subidos
        const paths = uploadedFiles.map(f => f.path);
        const nombres = uploadedFiles.map(f => f.name);
        const tiposArchivos: Record<string, string> = {};

        uploadedFiles.forEach(f => {
            tiposArchivos[f.path] = f.mime_type;
        });

        // CRÍTICO: AGREGAR a los existentes, NO reemplazar
        form.archivos_paths = [...form.archivos_paths, ...paths];
        form.archivos_nombres = [...form.archivos_nombres, ...nombres];
        form.tipos_archivos = { ...form.tipos_archivos, ...tiposArchivos };
        archivosSubidos.value = [...archivosSubidos.value, ...uploadedFiles];

        toast.success(`${uploadedFiles.length} archivo(s) subido(s) exitosamente`);
    } catch (error: any) {
        toast.error(error.message || 'Error al subir archivos');
    }
};

const validateForm = () => {
    const errors = [];

    if (!form.proyecto_id) {
        errors.push('Debe seleccionar un proyecto');
    }

    if (!form.nombre.trim()) {
        errors.push('El nombre del contrato es obligatorio');
    }

    if (!form.fecha_inicio) {
        errors.push('La fecha de inicio es obligatoria');
    }

    if (form.fecha_fin && form.fecha_fin < form.fecha_inicio) {
        errors.push('La fecha de fin debe ser posterior a la fecha de inicio');
    }

    // Validar campos personalizados requeridos
    props.camposPersonalizados.forEach(campo => {
        if (campo.es_requerido && !form.campos_personalizados[campo.id]) {
            errors.push(`El campo "${campo.nombre}" es obligatorio`);
        }
    });

    return errors;
};

// Manejar selección de contraparte
const handleContraparteSelect = (data: { userIds: number[]; extraData: Record<string, any>; users?: User[] }) => {
    if (data.userIds.length > 0) {
        form.contraparte_user_id = data.userIds[0];
        // Actualizar automáticamente los datos de la contraparte
        // Intentar obtener info del usuario desde los datos del modal
        if (data.users && data.users.length > 0) {
            const usuario = data.users[0];
            form.contraparte_nombre = usuario.name;
            form.contraparte_email = usuario.email || '';
            // Actualizar la referencia de la contraparte para mostrarla en la UI
            contraparteSeleccionada.value = usuario;
        }
    }
};

// Manejar selección de responsable
const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any>; users?: User[] }) => {
    if (data.userIds.length > 0) {
        form.responsable_id = data.userIds[0];
        // Actualizar la referencia del responsable
        if (data.users && data.users.length > 0) {
            responsableSeleccionado.value = data.users[0];
        }
    }
};

// Manejar selección de participantes
const handleParticipantesSelect = (data: { userIds: number[]; extraData: Record<string, any>; users?: User[] }) => {
    data.userIds.forEach((userId, index) => {
        // Verificar que no esté ya agregado
        const yaExiste = form.participantes.some(p => p.user_id === userId);
        if (!yaExiste) {
            form.participantes.push({
                user_id: userId,
                rol: data.extraData.rol || 'testigo',
                notas: data.extraData.notas || ''
            });

            // Guardar info del usuario para mostrarla
            if (data.users && data.users[index]) {
                participantesInfo.value.set(userId, data.users[index]);
            }
        }
    });
};

// Remover participante
const removeParticipante = (userId: number) => {
    const index = form.participantes.findIndex(p => p.user_id === userId);
    if (index > -1) {
        form.participantes.splice(index, 1);
    }
};

// Obtener info de usuario (de los participantes cargados)
const participantesInfo = ref<Map<number, User>>(new Map());

const getUsuarioInfo = (userId: number) => {
    return participantesInfo.value.get(userId);
};

const submitForm = () => {
    const errors = validateForm();

    if (errors.length > 0) {
        errors.forEach(error => toast.error(error));
        return;
    }

    const formData = new FormData();

    // Agregar campos básicos
    Object.keys(form.data()).forEach(key => {
        if (key === 'campos_personalizados') {
            // Manejar campos personalizados
            Object.keys(form.campos_personalizados).forEach(campoId => {
                const valor = form.campos_personalizados[campoId];
                if (valor !== null && valor !== undefined && valor !== '') {
                    formData.append(`campos_personalizados[${campoId}]`, valor);
                }
            });
        } else if (key === 'participantes') {
            // Manejar array de participantes
            form.participantes.forEach((participante: any, index: number) => {
                formData.append(`participantes[${index}][user_id]`, participante.user_id.toString());
                formData.append(`participantes[${index}][rol]`, participante.rol);
                if (participante.notas) {
                    formData.append(`participantes[${index}][notas]`, participante.notas);
                }
            });
        } else if (key === 'archivos_paths' || key === 'archivos_nombres' || key === 'tipos_archivos') {
            // Manejar arrays de archivos múltiples como JSON
            if (key === 'archivos_paths' && Array.isArray(form.archivos_paths) && form.archivos_paths.length > 0) {
                formData.append('archivos_paths', JSON.stringify(form.archivos_paths));
            } else if (key === 'archivos_nombres' && Array.isArray(form.archivos_nombres) && form.archivos_nombres.length > 0) {
                formData.append('archivos_nombres', JSON.stringify(form.archivos_nombres));
            } else if (key === 'tipos_archivos' && form.tipos_archivos && Object.keys(form.tipos_archivos).length > 0) {
                formData.append('tipos_archivos', JSON.stringify(form.tipos_archivos));
            }
        } else if (key === 'archivo_pdf' && form.archivo_pdf) {
            formData.append('archivo_pdf', form.archivo_pdf);
        } else if (key === 'responsable_id' && form[key] === 'none') {
            // No enviar responsable_id si es 'none' (sin responsable)
        } else if (form[key] !== null && form[key] !== '') {
            formData.append(key, form[key]);
        }
    });

    router.post(route('admin.contratos.store'), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Detener autosave y limpiar borrador
            stopAutoSave();
            clearLocalStorage();

            toast.success('Contrato creado exitosamente');
        },
        onError: (errors) => {
            // Manejo robusto de errores
            if (typeof errors === 'object' && errors !== null) {
                Object.values(errors).forEach(error => {
                    if (Array.isArray(error)) {
                        error.forEach(e => toast.error(String(e)));
                    } else if (typeof error === 'string') {
                        toast.error(error);
                    } else {
                        toast.error('Error al crear el contrato');
                    }
                });
            } else {
                toast.error('Error al crear el contrato');
            }
        }
    });
};

const cancelar = () => {
    if (confirm('¿Está seguro de cancelar? Los cambios no guardados se perderán.')) {
        router.get(route('admin.contratos.index'));
    }
};

const descartarBorrador = () => {
    if (!confirm('¿Está seguro de descartar el borrador? Esta acción no se puede deshacer.')) {
        return;
    }

    router.delete(route('admin.contratos.borrador'), {
        preserveScroll: false,
        onFinish: () => {
            // Limpiar estado local después de que Inertia maneje el redirect
            clearLocalStorage();
        }
    });
};

// Watch: Cargar archivos cuando el usuario cambie manualmente al tab de archivos
watch(activeTab, (newTab) => {
    if (newTab === 'archivos' && archivosPendientesCarga.value.length > 0) {
        nextTick(() => {
            archivosSubidos.value = archivosPendientesCarga.value;
            archivosPendientesCarga.value = []; // Limpiar pendientes
        });
    }
});

// Watch: Sincronizar archivosSubidos con form cuando se eliminan archivos
watch(archivosSubidos, (newFiles) => {
    // Reconstruir arrays desde archivosSubidos
    form.archivos_paths = newFiles.map(f => f.path).filter(Boolean);
    form.archivos_nombres = newFiles.map(f => f.name).filter(Boolean);

    const tiposArchivos: Record<string, string> = {};
    newFiles.forEach(f => {
        if (f.path && f.mime_type) {
            tiposArchivos[f.path] = f.mime_type;
        }
    });
    form.tipos_archivos = tiposArchivos;
}, { deep: true });

// Lifecycle hooks
onMounted(() => {
    // Manejar flash messages del servidor
    const page = usePage();
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success as string);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error as string);
    }

    // Prioridad 1: Recuperar borrador del servidor (más reciente y confiable)
    if (props.borrador) {
        // Restaurar datos del borrador del servidor
        form.proyecto_id = props.borrador.proyecto_id?.toString() || '';
        form.nombre = props.borrador.nombre || '';
        form.descripcion = props.borrador.descripcion || '';
        form.fecha_inicio = props.borrador.fecha_inicio || '';
        form.fecha_fin = props.borrador.fecha_fin || '';
        form.tipo = props.borrador.tipo || 'servicio';
        form.monto_total = props.borrador.monto_total || '';
        form.moneda = props.borrador.moneda || 'USD';
        form.responsable_id = props.borrador.responsable_id || 'none';
        form.contraparte_user_id = props.borrador.contraparte_user_id || null;
        form.contraparte_nombre = props.borrador.contraparte_nombre || '';
        form.contraparte_identificacion = props.borrador.contraparte_identificacion || '';
        form.contraparte_email = props.borrador.contraparte_email || '';
        form.contraparte_telefono = props.borrador.contraparte_telefono || '';
        form.observaciones = props.borrador.observaciones || '';

        // CRÍTICO: Restaurar archivos
        form.archivos_paths = props.borrador.archivos_paths || [];
        form.archivos_nombres = props.borrador.archivos_nombres || [];
        form.tipos_archivos = props.borrador.tipos_archivos || {};

        // Reconstruir archivosSubidos para el FileUploadField
        if (form.archivos_paths.length > 0) {
            const archivosReconstruidos = form.archivos_paths.map((path: string, index: number) => ({
                id: `borrador_${index}`,
                name: form.archivos_nombres[index] || path.split('/').pop() || 'archivo',
                size: 0,
                path: path,
                url: `/storage/${path}`,
                mime_type: form.tipos_archivos[path] || 'application/octet-stream',
            }));

            // Guardar archivos en pendientes en lugar de forzar cambio de tab
            archivosPendientesCarga.value = archivosReconstruidos;

            // NO forzar cambio de tab automáticamente
            // Los archivos se cargarán cuando el usuario cambie al tab manualmente
        }

        // CRÍTICO: Restaurar participantes
        if (props.borrador.participantes && Array.isArray(props.borrador.participantes)) {
            form.participantes = props.borrador.participantes.map((p: any) => ({
                user_id: p.pivot?.user_id || p.id,
                rol: p.pivot?.rol || 'testigo',
                notas: p.pivot?.notas || ''
            }));

            // Guardar info de usuarios para mostrarla
            props.borrador.participantes.forEach((p: any) => {
                participantesInfo.value.set(p.id, {
                    id: p.id,
                    name: p.name,
                    email: p.email
                });
            });
        }

        toast.success('Borrador recuperado', {
            description: `Borrador del servidor cargado (${form.archivos_paths.length} archivo(s), ${form.participantes.length} participante(s))`
        });
    } else {
        // Prioridad 2: Intentar recuperar del localStorage (fallback)
        const draft = restoreDraft();
        if (draft && draft.data) {
            Object.assign(form, draft.data);
            toast.info('Borrador recuperado', {
                description: 'Se recuperaron tus cambios del navegador'
            });
        }
    }

    // Iniciar autoguardado
    startWatching();
});

onUnmounted(() => {
    // Detener autoguardado al salir
    stopAutoSave();
});

// El método handleContraparteUserChange ya no es necesario
// porque lo manejamos en handleContraparteSelect
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.contratos.index')">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold">Crear Contrato</h1>
                        <p class="text-gray-600 mt-1">Complete la información para crear un nuevo contrato</p>
                    </div>
                </div>
                <!-- Indicador de autosave -->
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Clock v-if="isSaving" class="h-4 w-4 animate-spin" />
                    <CheckCircle v-else-if="hasSaved && !hasError" class="h-4 w-4 text-green-600" />
                    <AlertCircle v-else-if="hasError" class="h-4 w-4 text-amber-600" />
                    <span v-if="isSaving">Guardando borrador...</span>
                    <span v-else-if="hasSaved && !hasError">Borrador guardado a las {{ autoSaveState.lastSaved?.toLocaleTimeString() }}</span>
                    <span v-else-if="hasError">Guardado localmente</span>
                </div>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <Tabs v-model="activeTab">
                    <TabsList class="grid w-full grid-cols-6">
                        <TabsTrigger value="informacion">Información General</TabsTrigger>
                        <TabsTrigger value="fechas">Fechas y Estado</TabsTrigger>
                        <TabsTrigger value="financiero">Información Financiera</TabsTrigger>
                        <TabsTrigger value="contraparte">Participantes</TabsTrigger>
                        <TabsTrigger value="archivos">
                            Archivos
                            <span v-if="archivosPendientesCarga.length > 0" class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">
                                {{ archivosPendientesCarga.length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger value="adicional">Información Adicional</TabsTrigger>
                    </TabsList>

                    <!-- Tab: Información General -->
                    <TabsContent value="informacion">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información General</CardTitle>
                                <CardDescription>Datos principales del contrato</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                    <div>
                                        <Label for="proyecto_id">Proyecto *</Label>
                                        <Select v-model="form.proyecto_id" :disabled="!!props.proyecto">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccione un proyecto" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="p in proyectos"
                                                    :key="p.id"
                                                    :value="p.id.toString()"
                                                >
                                                    {{ p.nombre }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.proyecto_id" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.proyecto_id }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="nombre">Nombre del Contrato *</Label>
                                        <Input
                                            id="nombre"
                                            v-model="form.nombre"
                                            placeholder="Ej: Contrato de servicios de consultoría"
                                        />
                                        <div v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.nombre }}
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <Label for="descripcion">Descripción</Label>
                                    <Textarea
                                        id="descripcion"
                                        v-model="form.descripcion"
                                        placeholder="Descripción detallada del contrato..."
                                        rows="4"
                                    />
                                    <div v-if="form.errors.descripcion" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.descripcion }}
                                    </div>
                                </div>

                                <!-- Campos personalizados de información -->
                                <div v-if="camposAgrupados.informacion.length > 0" class="space-y-4 pt-4 border-t">
                                    <h3 class="font-medium">Información Complementaria</h3>
                                    <div v-for="campo in camposAgrupados.informacion" :key="campo.id">
                                        <CampoPersonalizadoInput
                                            :campo="campo"
                                            v-model="form.campos_personalizados[campo.id]"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab: Fechas y Estado -->
                    <TabsContent value="fechas">
                        <Card>
                            <CardHeader>
                                <CardTitle>Fechas y Estado</CardTitle>
                                <CardDescription>Configure las fechas y el estado del contrato</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                    <div>
                                        <Label for="fecha_inicio">Fecha de Inicio *</Label>
                                        <Input
                                            id="fecha_inicio"
                                            type="date"
                                            v-model="form.fecha_inicio"
                                        />
                                        <div v-if="form.errors.fecha_inicio" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.fecha_inicio }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="fecha_fin">Fecha de Fin</Label>
                                        <Input
                                            id="fecha_fin"
                                            type="date"
                                            v-model="form.fecha_fin"
                                        />
                                        <div v-if="form.errors.fecha_fin" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.fecha_fin }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="estado">Estado</Label>
                                        <Select v-model="form.estado">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="borrador">Borrador</SelectItem>
                                                <SelectItem value="activo">Activo</SelectItem>
                                                <SelectItem value="finalizado">Finalizado</SelectItem>
                                                <SelectItem value="cancelado">Cancelado</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.estado" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.estado }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="tipo">Tipo de Contrato *</Label>
                                        <Select v-model="form.tipo">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="servicio">Servicio</SelectItem>
                                                <SelectItem value="obra">Obra</SelectItem>
                                                <SelectItem value="suministro">Suministro</SelectItem>
                                                <SelectItem value="consultoria">Consultoría</SelectItem>
                                                <SelectItem value="otro">Otro</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.tipo" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.tipo }}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab: Información Financiera -->
                    <TabsContent value="financiero">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Financiera</CardTitle>
                                <CardDescription>Montos y detalles financieros del contrato</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                    <div>
                                        <Label for="monto_total">Monto Total</Label>
                                        <Input
                                            id="monto_total"
                                            type="number"
                                            step="0.01"
                                            v-model="form.monto_total"
                                            placeholder="0.00"
                                        />
                                        <div v-if="form.errors.monto_total" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.monto_total }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="moneda">Moneda</Label>
                                        <Select v-model="form.moneda">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="moneda in monedas"
                                                    :key="moneda.value"
                                                    :value="moneda.value"
                                                >
                                                    {{ moneda.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.moneda" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.moneda }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Campos personalizados financieros -->
                                <div v-if="camposAgrupados.financiero.length > 0" class="space-y-4 pt-4 border-t">
                                    <h3 class="font-medium">Campos Financieros Personalizados</h3>
                                    <div v-for="campo in camposAgrupados.financiero" :key="campo.id">
                                        <CampoPersonalizadoInput
                                            :campo="campo"
                                            v-model="form.campos_personalizados[campo.id]"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab: Participantes -->
                    <TabsContent value="contraparte">
                        <div class="space-y-6">
                            <!-- Sección 1: Responsable del Contrato -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Responsable del Contrato</CardTitle>
                                    <CardDescription>
                                        Asigna un usuario responsable de este contrato
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <!-- Mostrar responsable seleccionado -->
                                        <div v-if="responsableSeleccionado" class="p-3 bg-muted rounded-lg flex items-center justify-between mb-2">
                                            <div>
                                                <p class="font-medium">{{ responsableSeleccionado.name }}</p>
                                                <p class="text-sm text-muted-foreground">{{ responsableSeleccionado.email }}</p>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="responsableSeleccionado = null; form.responsable_id = 'none'"
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <!-- Botón para seleccionar responsable -->
                                        <Button
                                            type="button"
                                            variant="outline"
                                            @click="showResponsableModal = true"
                                            class="w-full"
                                        >
                                            <UserPlus class="h-4 w-4 mr-2" />
                                            {{ responsableSeleccionado ? 'Cambiar Responsable' : 'Seleccionar Responsable' }}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Sección 2: Contraparte del Sistema -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Contraparte - Usuario del Sistema</CardTitle>
                                    <CardDescription>
                                        Seleccione un usuario del sistema como contraparte del contrato
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div>
                                            <Label>Usuario Contraparte</Label>
                                            <p class="text-sm text-muted-foreground mb-2">
                                                Si la contraparte es un usuario registrado en el sistema, selecciónelo aquí
                                            </p>
                                        </div>

                                        <!-- Mostrar contraparte seleccionada -->
                                        <div v-if="contraparteSeleccionada" class="p-3 bg-muted rounded-lg flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">{{ contraparteSeleccionada.name }}</p>
                                                <p class="text-sm text-muted-foreground">{{ contraparteSeleccionada.email }}</p>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="form.contraparte_user_id = null; form.contraparte_nombre = ''; form.contraparte_email = ''; contraparteSeleccionada = null"
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <!-- Botón para seleccionar contraparte -->
                                        <Button
                                            type="button"
                                            variant="outline"
                                            @click="showContraparteModal = true"
                                            class="w-full"
                                        >
                                            <UserPlus class="h-4 w-4 mr-2" />
                                            {{ contraparteSeleccionada ? 'Cambiar Contraparte' : 'Seleccionar Contraparte' }}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Sección 3: Contraparte Externa -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Contraparte - Información Externa</CardTitle>
                                    <CardDescription>
                                        Complete estos campos si la contraparte no es un usuario del sistema
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <Alert v-if="form.contraparte_user_id" class="mb-4">
                                        <AlertCircle class="h-4 w-4" />
                                        <AlertDescription>
                                            Los campos de contraparte externa están deshabilitados porque ya se seleccionó un usuario del sistema.
                                        </AlertDescription>
                                    </Alert>

                                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                        <div>
                                            <Label for="contraparte_nombre">Nombre de la Contraparte</Label>
                                            <Input
                                                id="contraparte_nombre"
                                                v-model="form.contraparte_nombre"
                                                placeholder="Nombre de la empresa o persona"
                                                :disabled="!!form.contraparte_user_id"
                                            />
                                        </div>

                                        <div>
                                            <Label for="contraparte_identificacion">Identificación/RUC</Label>
                                            <Input
                                                id="contraparte_identificacion"
                                                v-model="form.contraparte_identificacion"
                                                placeholder="Número de identificación"
                                                :disabled="!!form.contraparte_user_id"
                                            />
                                        </div>

                                        <div>
                                            <Label for="contraparte_email">Email de Contacto</Label>
                                            <Input
                                                id="contraparte_email"
                                                type="email"
                                                v-model="form.contraparte_email"
                                                placeholder="email@ejemplo.com"
                                                :disabled="!!form.contraparte_user_id"
                                            />
                                        </div>

                                        <div>
                                            <Label for="contraparte_telefono">Teléfono de Contacto</Label>
                                            <Input
                                                id="contraparte_telefono"
                                                v-model="form.contraparte_telefono"
                                                placeholder="+1234567890"
                                                :disabled="!!form.contraparte_user_id"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Sección 4: Participantes del Contrato -->
                            <Card>
                                <CardHeader>
                                    <CardTitle>Participantes del Contrato</CardTitle>
                                    <CardDescription>
                                        Agregue usuarios del sistema que participarán en este contrato con diferentes roles
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <!-- Botón para agregar participantes -->
                                        <Button
                                            type="button"
                                            variant="outline"
                                            @click="showParticipantesModal = true"
                                            class="w-full"
                                        >
                                            <Users class="h-4 w-4 mr-2" />
                                            Agregar Participantes
                                        </Button>

                                        <!-- Lista de participantes seleccionados -->
                                        <div v-if="form.participantes.length > 0" class="space-y-2">
                                            <p class="text-sm font-medium text-muted-foreground">Participantes agregados:</p>
                                            <div
                                                v-for="participante in form.participantes"
                                                :key="participante.user_id"
                                                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="font-medium">{{ getUsuarioInfo(participante.user_id)?.name }}</p>
                                                        <p class="text-sm text-muted-foreground">{{ getUsuarioInfo(participante.user_id)?.email }}</p>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded">
                                                                {{ participante.rol }}
                                                            </span>
                                                            <span v-if="participante.notas" class="text-xs text-muted-foreground">
                                                                {{ participante.notas }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="removeParticipante(participante.user_id)"
                                                    >
                                                        <X class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estado vacío -->
                                        <div v-else class="text-center py-8 border-2 border-dashed rounded-lg">
                                            <Users class="h-12 w-12 mx-auto text-muted-foreground mb-2" />
                                            <p class="text-sm text-muted-foreground">No hay participantes agregados</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <!-- Tab: Archivos -->
                    <TabsContent value="archivos">
                        <Card>
                            <CardHeader>
                                <CardTitle>Archivos del Contrato</CardTitle>
                                <CardDescription>Sube múltiples archivos relacionados con el contrato (máximo 10)</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <FileUploadField
                                    v-model="archivosSubidos"
                                    label="Archivos"
                                    description="Sube archivos del contrato (PDF, imágenes, documentos)"
                                    @filesSelected="handleFilesSelected"
                                    :multiple="true"
                                    :maxFiles="10"
                                    :maxFileSize="10"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx"
                                    :autoUpload="false"
                                    module="contratos"
                                    fieldId="archivos"
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab: Información Adicional -->
                    <TabsContent value="adicional">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información Adicional</CardTitle>
                                <CardDescription>Observaciones y campos personalizados</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div>
                                    <Label for="observaciones">Observaciones</Label>
                                    <Textarea
                                        id="observaciones"
                                        v-model="form.observaciones"
                                        placeholder="Notas u observaciones adicionales..."
                                        rows="4"
                                    />
                                    <div v-if="form.errors.observaciones" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.observaciones }}
                                    </div>
                                </div>

                                <!-- Otros campos personalizados -->
                                <div v-if="camposAgrupados.otros.length > 0" class="space-y-4 pt-4 border-t">
                                    <h3 class="font-medium">Campos Personalizados</h3>
                                    <div v-for="campo in camposAgrupados.otros" :key="campo.id">
                                        <CampoPersonalizadoInput
                                            :campo="campo"
                                            v-model="form.campos_personalizados[campo.id]"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                <!-- Acciones -->
                <div class="flex justify-between items-center gap-4">
                    <!-- Botón Descartar Borrador (izquierda, solo si hay borrador) -->
                    <Button
                        v-if="props.borrador"
                        variant="destructive"
                        type="button"
                        @click="descartarBorrador"
                        :disabled="form.processing"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Descartar Borrador
                    </Button>
                    <div v-else></div>

                    <!-- Botones principales (derecha) -->
                    <div class="flex gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="cancelar"
                            :disabled="form.processing"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Guardando...' : 'Guardar Contrato' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Input file oculto -->
        <input
            ref="fileInput"
            type="file"
            accept=".pdf"
            @change="handleFileChange"
            class="hidden"
        />

        <!-- Modal de selección de contraparte -->
        <AddUsersModal
            v-model="showContraparteModal"
            title="Seleccionar Contraparte"
            description="Selecciona el usuario que será la contraparte del contrato"
            :search-endpoint="route('admin.proyectos.search-users')"
            :excluded-ids="form.contraparte_user_id ? [form.contraparte_user_id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Contraparte"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleContraparteSelect"
        />

        <!-- Modal de selección de participantes -->
        <AddUsersModal
            v-model="showParticipantesModal"
            title="Agregar Participantes"
            description="Selecciona los usuarios que participarán en este contrato y define su rol"
            :search-endpoint="route('admin.proyectos.search-users')"
            :excluded-ids="excludedIdsParticipantes"
            :extra-fields="extraFieldsParticipantes"
            submit-button-text="Agregar Participantes"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleParticipantesSelect"
        />

        <!-- Modal de selección de responsable -->
        <AddUsersModal
            v-model="showResponsableModal"
            title="Seleccionar Responsable"
            description="Selecciona el usuario que será responsable de este contrato"
            :search-endpoint="route('admin.proyectos.search-users')"
            :excluded-ids="responsableSeleccionado ? [responsableSeleccionado.id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Responsable"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleResponsableSelect"
        />
    </AdminLayout>
</template>