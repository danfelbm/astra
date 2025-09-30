<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
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
import { AlertCircle, ArrowLeft, Save, Upload, Trash2, UserPlus, X, Users, Clock, CheckCircle } from 'lucide-vue-next';
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
interface Contrato {
    id: number;
    proyecto_id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: 'borrador' | 'activo' | 'finalizado' | 'cancelado';
    tipo: string;
    monto_total?: number;
    moneda: string;
    responsable_id?: number;
    contraparte_user_id?: number;
    contraparte_nombre?: string;
    contraparte_identificacion?: string;
    contraparte_email?: string;
    contraparte_telefono?: string;
    archivo_pdf?: string;
    observaciones?: string;
    proyecto?: {
        id: number;
        nombre: string;
    };
    responsable?: {
        id: number;
        name: string;
    };
    contraparteUser?: {
        id: number;
        name: string;
        email: string;
    };
    participantes?: Participante[];
}

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
    pivot?: {
        rol: string;
        notas?: string;
    };
}

// Props
const props = withDefaults(defineProps<{
    contrato: Contrato;
    proyectos: Proyecto[];
    camposPersonalizados: CampoPersonalizado[];
    valoresCampos: Record<number, any>;
    usuarios?: User[];
}>(), {
    usuarios: () => []
});

const toast = useToast();
const { uploadFiles } = useFileUpload();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contratos', href: '/admin/contratos' },
    { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
    { title: 'Editar', href: `/admin/contratos/${props.contrato.id}/edit` },
];

// Preparar participantes desde el formato de Laravel
const prepararParticipantes = (): Participante[] => {
    if (!props.contrato.participantes || props.contrato.participantes.length === 0) {
        return [];
    }

    return props.contrato.participantes.map(p => ({
        user_id: p.pivot?.user_id || p.user_id || p.id,
        rol: p.pivot?.rol || p.rol || 'participante',
        notas: p.pivot?.notas || p.notas || ''
    }));
};

// Form data - inicializar con los valores del contrato
const form = useForm({
    proyecto_id: props.contrato.proyecto_id.toString(),
    nombre: props.contrato.nombre,
    descripcion: props.contrato.descripcion || '',
    fecha_inicio: props.contrato.fecha_inicio,
    fecha_fin: props.contrato.fecha_fin || '',
    estado: props.contrato.estado,
    tipo: props.contrato.tipo,
    monto_total: props.contrato.monto_total?.toString() || '',
    moneda: props.contrato.moneda,
    responsable_id: props.contrato.responsable_id?.toString() || 'none',
    contraparte_user_id: props.contrato.contraparte_user_id || null,
    contraparte_nombre: props.contrato.contraparte_nombre || '',
    contraparte_identificacion: props.contrato.contraparte_identificacion || '',
    contraparte_email: props.contrato.contraparte_email || '',
    contraparte_telefono: props.contrato.contraparte_telefono || '',
    participantes: prepararParticipantes(),
    archivo_pdf: null as File | null,
    archivos_paths: props.contrato.archivos_paths || [],
    archivos_nombres: props.contrato.archivos_nombres || [],
    tipos_archivos: props.contrato.tipos_archivos || {},
    observaciones: props.contrato.observaciones || '',
    campos_personalizados: props.valoresCampos || {},
    _method: 'PUT' // Para el update
});

// Estado local
const archivoNombre = ref(props.contrato.archivo_pdf ? 'Archivo actual: ' + props.contrato.archivo_pdf.split('/').pop() : '');
const archivosSubidos = ref<any[]>(
    (props.contrato.archivos_paths || []).map((path: string, index: number) => ({
        id: `existing_${index}`,
        name: props.contrato.archivos_nombres?.[index] || path.split('/').pop() || 'archivo',
        size: 0,
        path: path,
        url: `/storage/${path}`,
        mime_type: props.contrato.tipos_archivos?.[path] || 'application/octet-stream',
        uploaded_at: props.contrato.updated_at
    }))
);
const activeTab = ref('informacion');
const mostrarConfirmacionEliminar = ref(false);

// Estados para los modales de selección de usuarios
const showContraparteModal = ref(false);
const showParticipantesModal = ref(false);

// Helper para obtener route
const { route } = window as any;

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

// Computed para obtener la contraparte seleccionada
const contraparteSeleccionada = computed(() => {
    if (!form.contraparte_user_id) return null;
    return props.usuarios?.find(u => u.id === form.contraparte_user_id);
});

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

// Manejar selección de contraparte
const handleContraparteSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
    if (data.userIds.length > 0) {
        form.contraparte_user_id = data.userIds[0];
        // Actualizar automáticamente los datos de la contraparte
        const usuario = props.usuarios?.find(u => u.id === data.userIds[0]);
        if (usuario) {
            form.contraparte_nombre = usuario.name;
            form.contraparte_email = usuario.email || '';
        }
    }
};

// Manejar selección de participantes
const handleParticipantesSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
    data.userIds.forEach(userId => {
        // Verificar que no esté ya agregado
        const yaExiste = form.participantes.some(p => p.user_id === userId);
        if (!yaExiste) {
            form.participantes.push({
                user_id: userId,
                rol: data.extraData.rol || 'testigo',
                notas: data.extraData.notas || ''
            });
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

// Obtener info de usuario
const getUsuarioInfo = (userId: number) => {
    return props.usuarios?.find(u => u.id === userId);
};

const camposAgrupados = computed(() => {
    const grupos = {
        informacion: [] as CampoPersonalizado[],
        financiero: [] as CampoPersonalizado[],
        otros: [] as CampoPersonalizado[],
    };

    props.camposPersonalizados.forEach(campo => {
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

const puedeEditar = computed(() => {
    // Lógica para determinar si se puede editar según el estado
    return props.contrato.estado !== 'finalizado' && props.contrato.estado !== 'cancelado';
});

// Computed para autosave
const formDataRef = computed(() => form.data());

// Configurar autosave para Edit (guardado directo)
const {
    state: autoSaveState,
    isSaving,
    hasSaved,
    hasError,
    startWatching,
    stopAutoSave
} = useAutoSave(formDataRef, {
    url: `/admin/contratos/${props.contrato.id}/autosave`,
    resourceId: props.contrato.id,
    debounceTime: 3000,
    showNotifications: true,
    useLocalStorage: false // No usar localStorage en Edit
});

// Métodos
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.archivo_pdf = target.files[0];
        archivoNombre.value = 'Nuevo archivo: ' + target.files[0].name;
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
        const newPaths = uploadedFiles.map(f => f.path);
        const newNombres = uploadedFiles.map(f => f.name);
        const newTiposArchivos: Record<string, string> = {};

        uploadedFiles.forEach(f => {
            newTiposArchivos[f.path] = f.mime_type;
        });

        // Actualizar form agregando a los existentes
        form.archivos_paths = [...form.archivos_paths, ...newPaths];
        form.archivos_nombres = [...form.archivos_nombres, ...newNombres];
        form.tipos_archivos = { ...form.tipos_archivos, ...newTiposArchivos };

        // Actualizar archivos subidos agregando los nuevos
        archivosSubidos.value = [...archivosSubidos.value, ...uploadedFiles];

        toast.success(`${uploadedFiles.length} archivo(s) agregado(s) exitosamente`);
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
        } else if (key !== '_method' && form[key] !== null && form[key] !== '') {
            formData.append(key, form[key]);
        }
    });

    // Agregar método PUT para Laravel
    formData.append('_method', 'PUT');

    router.post(route('admin.contratos.update', props.contrato.id), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Contrato actualizado exitosamente');
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
                        toast.error('Error al actualizar el contrato');
                    }
                });
            } else {
                toast.error('Error al actualizar el contrato');
            }
        }
    });
};

const eliminarContrato = () => {
    if (confirm('¿Está seguro de eliminar este contrato? Esta acción no se puede deshacer.')) {
        router.delete(route('admin.contratos.destroy', props.contrato.id), {
            onSuccess: () => {
                toast.success('Contrato eliminado exitosamente');
            },
            onError: () => {
                toast.error('Error al eliminar el contrato');
            }
        });
    }
};

const duplicarContrato = () => {
    if (confirm('¿Desea duplicar este contrato?')) {
        router.post(route('admin.contratos.duplicar', props.contrato.id), {}, {
            onSuccess: () => {
                toast.success('Contrato duplicado exitosamente');
            },
            onError: () => {
                toast.error('Error al duplicar el contrato');
            }
        });
    }
};

const cambiarEstado = (nuevoEstado: string) => {
    router.post(route('admin.contratos.cambiar-estado', props.contrato.id), {
        estado: nuevoEstado
    }, {
        onSuccess: () => {
            form.estado = nuevoEstado as any;
            toast.success('Estado actualizado exitosamente');
        },
        onError: () => {
            toast.error('Error al cambiar el estado');
        }
    });
};

const cancelar = () => {
    if (confirm('¿Está seguro de cancelar? Los cambios no guardados se perderán.')) {
        router.get(route('admin.contratos.show', props.contrato.id));
    }
};

// Lifecycle hooks
onMounted(() => {
    // Iniciar autoguardado (solo si puede editar)
    if (puedeEditar.value) {
        startWatching();
    }
});

onUnmounted(() => {
    // Detener autoguardado al salir
    stopAutoSave();
});

// El método handleContraparteUserChange fue reemplazado por handleContraparteSelect
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
                        <h1 class="text-3xl font-bold">Editar Contrato</h1>
                        <p class="text-gray-600 mt-1">Modificar información del contrato #{{ contrato.id }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        @click="duplicarContrato"
                    >
                        Duplicar
                    </Button>
                    <Button
                        variant="destructive"
                        @click="eliminarContrato"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Eliminar
                    </Button>
                </div>
            </div>

            <!-- Indicador de autosave -->
            <div v-if="puedeEditar" class="flex items-center justify-end gap-2 text-sm text-muted-foreground">
                <Clock v-if="isSaving" class="h-4 w-4 animate-spin" />
                <CheckCircle v-else-if="hasSaved && !hasError" class="h-4 w-4 text-green-600" />
                <AlertCircle v-else-if="hasError" class="h-4 w-4 text-amber-600" />
                <span v-if="isSaving">Guardando cambios...</span>
                <span v-else-if="hasSaved && !hasError">Guardado a las {{ autoSaveState.lastSaved?.toLocaleTimeString() }}</span>
                <span v-else-if="hasError">Error al guardar</span>
            </div>

            <!-- Alert de estado -->
            <Alert v-if="!puedeEditar" variant="warning">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Este contrato está {{ contrato.estado }} y no puede ser editado.
                    <Button
                        v-if="contrato.estado === 'finalizado'"
                        variant="link"
                        size="sm"
                        @click="cambiarEstado('activo')"
                        class="ml-2"
                    >
                        Reactivar
                    </Button>
                </AlertDescription>
            </Alert>

            <!-- Acciones rápidas de cambio de estado -->
            <Card v-if="puedeEditar">
                <CardHeader>
                    <CardTitle class="text-lg">Cambio Rápido de Estado</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-2">
                        <Button
                            v-if="form.estado === 'borrador'"
                            variant="outline"
                            size="sm"
                            @click="cambiarEstado('activo')"
                        >
                            Activar Contrato
                        </Button>
                        <Button
                            v-if="form.estado === 'activo'"
                            variant="outline"
                            size="sm"
                            @click="cambiarEstado('finalizado')"
                        >
                            Finalizar Contrato
                        </Button>
                        <Button
                            v-if="form.estado !== 'cancelado'"
                            variant="outline"
                            size="sm"
                            class="text-red-600"
                            @click="cambiarEstado('cancelado')"
                        >
                            Cancelar Contrato
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulario -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <Tabs v-model="activeTab">
                    <TabsList class="grid w-full grid-cols-6">
                        <TabsTrigger value="informacion">Información General</TabsTrigger>
                        <TabsTrigger value="fechas">Fechas y Estado</TabsTrigger>
                        <TabsTrigger value="financiero">Información Financiera</TabsTrigger>
                        <TabsTrigger value="contraparte">Contraparte</TabsTrigger>
                        <TabsTrigger value="archivos">Archivos</TabsTrigger>
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label for="proyecto_id">Proyecto *</Label>
                                        <Select v-model="form.proyecto_id" :disabled="!puedeEditar">
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
                                            :disabled="!puedeEditar"
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
                                        :disabled="!puedeEditar"
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
                                            :disabled="!puedeEditar"
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label for="fecha_inicio">Fecha de Inicio *</Label>
                                        <Input
                                            id="fecha_inicio"
                                            type="date"
                                            v-model="form.fecha_inicio"
                                            :disabled="!puedeEditar"
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
                                            :disabled="!puedeEditar"
                                        />
                                        <div v-if="form.errors.fecha_fin" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.fecha_fin }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="estado">Estado</Label>
                                        <Select v-model="form.estado" :disabled="!puedeEditar">
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
                                        <Select v-model="form.tipo" :disabled="!puedeEditar">
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

                                <div v-if="usuarios">
                                    <Label for="responsable_id">Responsable</Label>
                                    <Select v-model="form.responsable_id" :disabled="!puedeEditar">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione un responsable" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Sin responsable</SelectItem>
                                            <SelectItem
                                                v-for="usuario in usuarios"
                                                :key="usuario.id"
                                                :value="usuario.id.toString()"
                                            >
                                                {{ usuario.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label for="monto_total">Monto Total</Label>
                                        <Input
                                            id="monto_total"
                                            type="number"
                                            step="0.01"
                                            v-model="form.monto_total"
                                            placeholder="0.00"
                                            :disabled="!puedeEditar"
                                        />
                                        <div v-if="form.errors.monto_total" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.monto_total }}
                                        </div>
                                    </div>

                                    <div>
                                        <Label for="moneda">Moneda</Label>
                                        <Select v-model="form.moneda" :disabled="!puedeEditar">
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
                                            :disabled="!puedeEditar"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab: Contraparte -->
                    <TabsContent value="contraparte">
                        <div class="space-y-6">
                            <!-- Sección 1: Contraparte del Sistema -->
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
                                                @click="form.contraparte_user_id = null; form.contraparte_nombre = ''; form.contraparte_email = ''"
                                                :disabled="!puedeEditar"
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
                                            :disabled="!puedeEditar"
                                        >
                                            <UserPlus class="h-4 w-4 mr-2" />
                                            {{ contraparteSeleccionada ? 'Cambiar Contraparte' : 'Seleccionar Contraparte' }}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Sección 2: Contraparte Externa -->
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

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label for="contraparte_nombre">Nombre de la Contraparte</Label>
                                            <Input
                                                id="contraparte_nombre"
                                                v-model="form.contraparte_nombre"
                                                placeholder="Nombre de la empresa o persona"
                                                :disabled="!puedeEditar || !!form.contraparte_user_id"
                                            />
                                        </div>

                                        <div>
                                            <Label for="contraparte_identificacion">Identificación/RUC</Label>
                                            <Input
                                                id="contraparte_identificacion"
                                                v-model="form.contraparte_identificacion"
                                                placeholder="Número de identificación"
                                                :disabled="!puedeEditar || !!form.contraparte_user_id"
                                            />
                                        </div>

                                        <div>
                                            <Label for="contraparte_email">Email de Contacto</Label>
                                            <Input
                                                id="contraparte_email"
                                                type="email"
                                                v-model="form.contraparte_email"
                                                placeholder="email@ejemplo.com"
                                                :disabled="!puedeEditar || !!form.contraparte_user_id"
                                            />
                                        </div>

                                        <div>
                                            <Label for="contraparte_telefono">Teléfono de Contacto</Label>
                                            <Input
                                                id="contraparte_telefono"
                                                v-model="form.contraparte_telefono"
                                                placeholder="+1234567890"
                                                :disabled="!puedeEditar || !!form.contraparte_user_id"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Sección 3: Participantes del Contrato -->
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
                                            :disabled="!puedeEditar"
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
                                                        :disabled="!puedeEditar"
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
                                    description="Agrega archivos adicionales al contrato (PDF, imágenes, documentos)"
                                    @filesSelected="handleFilesSelected"
                                    :multiple="true"
                                    :maxFiles="10"
                                    :maxFileSize="10"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx"
                                    :autoUpload="false"
                                    :disabled="!puedeEditar"
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
                                        :disabled="!puedeEditar"
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
                                            :disabled="!puedeEditar"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                <!-- Acciones -->
                <div v-if="puedeEditar" class="flex justify-end gap-4">
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
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
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
    </AdminLayout>
</template>