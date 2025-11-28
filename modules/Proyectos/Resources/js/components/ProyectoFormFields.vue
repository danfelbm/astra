<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@modules/Core/Resources/js/components/ui/select";
import CamposPersonalizadosForm from "./CamposPersonalizadosForm.vue";
import EtiquetaSelector from "./EtiquetaSelector.vue";
import GestoresManager from "./GestoresManager.vue";
import AddUsersModal from "@modules/Core/Resources/js/components/modals/AddUsersModal.vue";
import { Save, X, AlertCircle, Tag, UserPlus, FileText, Info } from 'lucide-vue-next';
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@modules/Core/Resources/js/components/ui/tooltip";
import type { CategoriaEtiqueta, Etiqueta } from "../types/etiquetas";
import { toast } from 'vue-sonner';

// Interfaces
interface User {
    id: number;
    name: string;
    email?: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: any[];
    es_requerido: boolean;
    placeholder?: string;
    descripcion?: string;
}

interface Proyecto {
    id?: number;
    nombre?: string;
    descripcion?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    estado?: string;
    prioridad?: string;
    responsable_id?: number;
    responsable?: User;
    etiquetas?: Etiqueta[];
    nomenclatura_archivos?: string;
    created_at?: string;
    updated_at?: string;
}

interface TokenNomenclatura {
    token: string;
    descripcion: string;
}

interface Props {
    proyecto?: Proyecto;
    camposPersonalizados: CampoPersonalizado[];
    valoresCampos?: Record<number | string, any>;
    categorias?: CategoriaEtiqueta[];
    gestores?: User[];
    tokensNomenclatura?: TokenNomenclatura[];
    submitUrl: string;
    cancelUrl: string;
    mode?: 'create' | 'edit';
    showResponsable?: boolean;
    showGestores?: boolean;
    showInfoAlert?: boolean;
    searchUsersEndpoint?: string;
    estados?: Record<string, string>;
    prioridades?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'edit',
    showResponsable: false,
    showGestores: false,
    showInfoAlert: true,
    estados: () => ({}),
    prioridades: () => ({}),
    valoresCampos: () => ({}),
    tokensNomenclatura: () => ([])
});

// Emits
const emit = defineEmits<{
    cancel: [];
    success: [];
}>();

// Formatear fechas para inputs HTML de tipo date
const formatDateForInput = (dateString: string | undefined): string => {
    if (!dateString) return '';
    return dateString.split(' ')[0];
};

// Preparar valores iniciales de campos personalizados
const valoresIniciales: Record<number, any> = {};
if (props.valoresCampos) {
    props.camposPersonalizados.forEach(campo => {
        // Soporte para ambos formatos: campo.id o campo.slug
        valoresIniciales[campo.id] = props.valoresCampos[campo.id] || props.valoresCampos[campo.slug] || '';
    });
}

// Determinar si estamos en modo creación
const isCreateMode = props.mode === 'create';

// Formulario con valores por defecto para creación
const form = useForm({
    nombre: props.proyecto?.nombre || '',
    descripcion: props.proyecto?.descripcion || '',
    fecha_inicio: formatDateForInput(props.proyecto?.fecha_inicio),
    fecha_fin: formatDateForInput(props.proyecto?.fecha_fin),
    estado: props.proyecto?.estado || 'planificacion',
    prioridad: props.proyecto?.prioridad || 'media',
    responsable_id: props.proyecto?.responsable_id || null,
    etiquetas: props.proyecto?.etiquetas?.map(e => e.id) || [],
    gestores: props.gestores?.map(g => g.id) || [],
    campos_personalizados: valoresIniciales,
    nomenclatura_archivos: props.proyecto?.nomenclatura_archivos || ''
});

// Ref para el input de nomenclatura (para insertar tokens)
const nomenclaturaInput = ref<HTMLInputElement | null>(null);

// Función para insertar un token en el input de nomenclatura
const insertarToken = (token: string) => {
    const input = nomenclaturaInput.value;
    if (!input) {
        // Si no hay referencia al input, solo agregar al final
        form.nomenclatura_archivos += token;
        return;
    }

    const start = input.selectionStart ?? form.nomenclatura_archivos.length;
    const end = input.selectionEnd ?? form.nomenclatura_archivos.length;
    const text = form.nomenclatura_archivos;

    // Insertar el token en la posición del cursor
    form.nomenclatura_archivos = text.slice(0, start) + token + text.slice(end);

    // Mover el cursor después del token insertado
    nextTick(() => {
        if (input) {
            const newPos = start + token.length;
            input.setSelectionRange(newPos, newPos);
            input.focus();
        }
    });
};

// Generar preview del nombre de archivo
const previewNombreArchivo = computed(() => {
    const patron = form.nomenclatura_archivos || '{original}';

    // Simular reemplazos para el preview
    let preview = patron
        .replace('{proyecto}', 'mi-proyecto')
        .replace('{proyecto_id}', '42')
        .replace('{hito}', 'fase-1')
        .replace('{hito_id}', '15')
        .replace('{entregable}', 'documento-final')
        .replace('{entregable_id}', '78')
        .replace('{original}', 'mi-archivo');

    // Procesar tokens de fecha
    const hoy = new Date();
    preview = preview
        .replace('{fecha}', hoy.toISOString().split('T')[0])
        .replace(/\{fecha:Ymd\}/g, hoy.toISOString().split('T')[0].replace(/-/g, ''))
        .replace(/\{fecha:d-m-Y\}/g, `${String(hoy.getDate()).padStart(2, '0')}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${hoy.getFullYear()}`);

    // Agregar uid y extensión (siempre se agregan automáticamente)
    return preview + '_a1b2c3.pdf';
});

// Estado de procesamiento
const processing = ref(false);

// Estado para modales
const showResponsableModal = ref(false);

// Ref para el responsable seleccionado
const responsableSeleccionado = ref<User | null>(props.proyecto?.responsable || null);

// Estados y prioridades por defecto
const estadosDefault = {
    'planificacion': 'Planificación',
    'en_progreso': 'En Progreso',
    'pausado': 'Pausado',
    'completado': 'Completado',
    'cancelado': 'Cancelado'
};

const prioridadesDefault = {
    'baja': 'Baja',
    'media': 'Media',
    'alta': 'Alta',
    'critica': 'Crítica'
};

// Usar estados/prioridades proporcionados o valores por defecto
const estadosToUse = computed(() => {
    return Object.keys(props.estados).length > 0 ? props.estados : estadosDefault;
});

const prioridadesToUse = computed(() => {
    return Object.keys(props.prioridades).length > 0 ? props.prioridades : prioridadesDefault;
});

// Función para enviar formulario (crear o actualizar)
const submit = () => {
    processing.value = true;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(isCreateMode ? 'Proyecto creado exitosamente' : 'Proyecto actualizado exitosamente');
            emit('success');
        },
        onError: (errors: Record<string, string>) => {
            toast.error(isCreateMode ? 'Error al crear el proyecto' : 'Error al actualizar el proyecto');
            console.error(errors);
        },
        onFinish: () => {
            processing.value = false;
        }
    };

    // Usar POST para crear, PUT para actualizar
    if (isCreateMode) {
        form.post(props.submitUrl, options);
    } else {
        form.put(props.submitUrl, options);
    }
};

// Función para cancelar
const cancelar = () => {
    emit('cancel');
};

// Actualizar campos personalizados
const updateCamposPersonalizados = (valores: Record<number, any>) => {
    form.campos_personalizados = valores;
};

// Manejar selección de responsable
const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any>; users?: User[] }) => {
    if (data.userIds.length > 0) {
        form.responsable_id = data.userIds[0];
        if (data.users && data.users.length > 0) {
            responsableSeleccionado.value = data.users[0];
        }
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Información básica -->
        <Card>
            <CardHeader>
                <CardTitle>Información Básica</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Nombre -->
                <div>
                    <Label for="nombre">Nombre del Proyecto *</Label>
                    <Input
                        id="nombre"
                        v-model="form.nombre"
                        type="text"
                        placeholder="Ingrese el nombre del proyecto"
                        :class="{ 'border-red-500': form.errors.nombre }"
                        required
                    />
                    <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">
                        {{ form.errors.nombre }}
                    </p>
                </div>

                <!-- Descripción -->
                <div>
                    <Label for="descripcion">Descripción</Label>
                    <Textarea
                        id="descripcion"
                        v-model="form.descripcion"
                        placeholder="Ingrese una descripción del proyecto"
                        rows="4"
                        :class="{ 'border-red-500': form.errors.descripcion }"
                    />
                    <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
                        {{ form.errors.descripcion }}
                    </p>
                </div>

                <!-- Fechas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label for="fecha_inicio">Fecha de Inicio *</Label>
                        <Input
                            id="fecha_inicio"
                            v-model="form.fecha_inicio"
                            type="date"
                            :class="{ 'border-red-500': form.errors.fecha_inicio }"
                            required
                        />
                        <p v-if="form.errors.fecha_inicio" class="mt-1 text-sm text-red-600">
                            {{ form.errors.fecha_inicio }}
                        </p>
                    </div>

                    <div>
                        <Label for="fecha_fin">Fecha de Fin</Label>
                        <Input
                            id="fecha_fin"
                            v-model="form.fecha_fin"
                            type="date"
                            :min="form.fecha_inicio"
                            :class="{ 'border-red-500': form.errors.fecha_fin }"
                        />
                        <p v-if="form.errors.fecha_fin" class="mt-1 text-sm text-red-600">
                            {{ form.errors.fecha_fin }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Estado y Prioridad -->
        <Card>
            <CardHeader>
                <CardTitle>Configuración del Proyecto</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Estado -->
                    <div>
                        <Label for="estado">Estado *</Label>
                        <Select v-model="form.estado">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione el estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(label, value) in estadosToUse" :key="value" :value="value">
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.estado" class="mt-1 text-sm text-red-600">
                            {{ form.errors.estado }}
                        </p>
                    </div>

                    <!-- Prioridad -->
                    <div>
                        <Label for="prioridad">Prioridad *</Label>
                        <Select v-model="form.prioridad">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccione la prioridad" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(label, value) in prioridadesToUse" :key="value" :value="value">
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.prioridad" class="mt-1 text-sm text-red-600">
                            {{ form.errors.prioridad }}
                        </p>
                    </div>
                </div>

                <!-- Responsable (solo si showResponsable = true) -->
                <div v-if="showResponsable">
                    <Label for="responsable_id">Responsable</Label>
                    <div class="space-y-2">
                        <!-- Mostrar responsable seleccionado -->
                        <div v-if="responsableSeleccionado" class="p-3 bg-muted rounded-lg flex items-center justify-between">
                            <div>
                                <p class="font-medium">{{ responsableSeleccionado.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ responsableSeleccionado.email }}</p>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="form.responsable_id = null; responsableSeleccionado = null"
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
                    <p v-if="form.errors.responsable_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.responsable_id }}
                    </p>
                </div>

                <!-- Etiquetas -->
                <div v-if="categorias && categorias.length > 0">
                    <Label class="flex items-center gap-2 mb-2">
                        <Tag class="h-4 w-4" />
                        Etiquetas
                    </Label>
                    <EtiquetaSelector
                        v-model="form.etiquetas"
                        :categorias="categorias"
                        :max-etiquetas="10"
                        placeholder="Seleccionar etiquetas para el proyecto..."
                        description="Puedes asignar hasta 10 etiquetas para categorizar este proyecto"
                    />
                    <p v-if="form.errors.etiquetas" class="mt-1 text-sm text-red-600">
                        {{ form.errors.etiquetas }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Gestores del Proyecto (solo si showGestores = true) -->
        <GestoresManager
            v-if="showGestores"
            v-model="form.gestores"
            :gestores="gestores"
        />

        <!-- Configuración de Nomenclatura de Archivos -->
        <Card v-if="tokensNomenclatura && tokensNomenclatura.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="h-5 w-5" />
                    Nomenclatura de Archivos
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Define cómo se nombrarán los archivos de evidencias subidos a este proyecto.
                    Haz clic en los tokens para insertarlos en el patrón.
                </p>

                <!-- Tokens disponibles -->
                <div class="flex flex-wrap gap-2">
                    <TooltipProvider>
                        <Tooltip v-for="tokenInfo in tokensNomenclatura" :key="tokenInfo.token">
                            <TooltipTrigger as-child>
                                <Badge
                                    variant="outline"
                                    class="cursor-pointer hover:bg-primary hover:text-primary-foreground transition-colors"
                                    @click="insertarToken(tokenInfo.token)"
                                >
                                    {{ tokenInfo.token }}
                                </Badge>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{{ tokenInfo.descripcion }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>

                <!-- Input para el patrón -->
                <div>
                    <Label for="nomenclatura_archivos">Patrón de nomenclatura</Label>
                    <Input
                        id="nomenclatura_archivos"
                        ref="nomenclaturaInput"
                        v-model="form.nomenclatura_archivos"
                        type="text"
                        placeholder="{proyecto_id}-{hito_id}-{entregable_id}_{fecha:Ymd}_{original}"
                        :class="{ 'border-red-500': form.errors.nomenclatura_archivos }"
                    />
                    <p v-if="form.errors.nomenclatura_archivos" class="mt-1 text-sm text-red-600">
                        {{ form.errors.nomenclatura_archivos }}
                    </p>
                </div>

                <!-- Preview del nombre -->
                <div class="p-3 bg-muted rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <Info class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm font-medium">Vista previa:</span>
                    </div>
                    <code class="text-sm text-primary break-all">{{ previewNombreArchivo }}</code>
                    <p class="text-xs text-muted-foreground mt-2">
                        El identificador único (_a1b2c3) y la extensión (.pdf) se agregan automáticamente.
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Campos Personalizados -->
        <CamposPersonalizadosForm
            v-if="camposPersonalizados.length > 0"
            :campos="camposPersonalizados"
            :valores="valoresIniciales"
            :errors="form.errors"
            @update="updateCamposPersonalizados"
        />

        <!-- Nota informativa para usuarios sin permisos de responsable (solo en modo edición) -->
        <Alert v-if="!showResponsable && !isCreateMode && showInfoAlert">
            <AlertCircle class="h-4 w-4" />
            <AlertDescription>
                Los cambios se guardarán inmediatamente. Si necesitas cambiar el responsable del proyecto,
                contacta con un administrador.
            </AlertDescription>
        </Alert>

        <!-- Botones de acción -->
        <Card>
            <CardContent class="flex justify-end gap-4 pt-6">
                <Button
                    type="button"
                    variant="outline"
                    @click="cancelar"
                    :disabled="processing"
                >
                    <X class="mr-2 h-4 w-4" />
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    :disabled="processing || form.processing"
                >
                    <Save class="mr-2 h-4 w-4" />
                    <template v-if="isCreateMode">
                        {{ processing ? 'Guardando...' : 'Guardar Proyecto' }}
                    </template>
                    <template v-else>
                        {{ processing ? 'Actualizando...' : 'Actualizar Proyecto' }}
                    </template>
                </Button>
            </CardContent>
        </Card>

        <!-- Modal de selección de responsable -->
        <AddUsersModal
            v-if="showResponsable && searchUsersEndpoint"
            v-model="showResponsableModal"
            title="Seleccionar Responsable"
            description="Selecciona el usuario que será responsable de este proyecto"
            :search-endpoint="searchUsersEndpoint"
            :excluded-ids="form.responsable_id ? [form.responsable_id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Responsable"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleResponsableSelect"
        />
    </form>
</template>
