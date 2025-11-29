<script setup lang="ts">
/**
 * HitoForm.vue - Componente reutilizable para crear y editar hitos.
 * Usado tanto en Admin como en User views.
 */
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import CamposPersonalizadosForm from "@modules/Proyectos/Resources/js/components/CamposPersonalizadosForm.vue";
import EtiquetaSelector from "@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue";
import AddUsersModal from "@modules/Core/Resources/js/components/modals/AddUsersModal.vue";
import { Save, UserPlus, X, Tag } from 'lucide-vue-next';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

// Interfaces
interface User {
    id: number;
    name: string;
    email: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    tipo: string;
    es_requerido: boolean;
    opciones?: any[];
}

interface HitoDisponible {
    id: number;
    nombre: string;
    ruta_completa: string;
    nivel: number;
}

interface EstadoOption {
    value: string;
    label: string;
}

// Props
const props = withDefaults(defineProps<{
    // Modo del formulario
    mode: 'create' | 'edit';
    // Datos del hito para edición
    hito?: Hito & { responsable?: User };
    // Datos del proyecto
    proyectoId: number;
    proyectoNombre?: string;
    // Datos para el formulario
    responsables?: User[];
    hitosDisponibles?: HitoDisponible[];
    camposPersonalizados?: CampoPersonalizado[];
    valoresCamposPersonalizados?: Record<number, any>;
    categorias?: CategoriaEtiqueta[];
    estados: EstadoOption[];
    siguienteOrden?: number;
    // Endpoint para búsqueda de usuarios
    searchUsersEndpoint?: string;
    // Opciones de visualización
    showCamposPersonalizados?: boolean;
    showEtiquetas?: boolean;
    showParentSelector?: boolean;
    // Estado de procesamiento externo
    externalProcessing?: boolean;
}>(), {
    responsables: () => [],
    hitosDisponibles: () => [],
    camposPersonalizados: () => [],
    valoresCamposPersonalizados: () => ({}),
    categorias: () => [],
    siguienteOrden: 1,
    showCamposPersonalizados: true,
    showEtiquetas: true,
    showParentSelector: true,
    externalProcessing: false,
});

// Emits
const emit = defineEmits<{
    submit: [data: typeof form];
    cancel: [];
}>();

// Estado del modal de responsable
const showResponsableModal = ref(false);
const responsableSeleccionado = ref<User | null>(null);
const usuariosCache = ref<Map<number, User>>(new Map());

// Inicializar cache de usuarios
props.responsables.forEach(u => usuariosCache.value.set(u.id, u));

// Inicializar responsable seleccionado si es modo edición
if (props.mode === 'edit' && props.hito?.responsable) {
    responsableSeleccionado.value = props.hito.responsable;
    usuariosCache.value.set(props.hito.responsable.id, props.hito.responsable);
}

// Formulario con Inertia
const form = useForm({
    nombre: props.hito?.nombre || '',
    descripcion: props.hito?.descripcion || '',
    fecha_inicio: props.hito?.fecha_inicio || '',
    fecha_fin: props.hito?.fecha_fin || '',
    estado: props.hito?.estado || 'pendiente',
    responsable_id: props.hito?.responsable_id || null as number | null,
    parent_id: props.hito?.parent_id || null as number | null,
    campos_personalizados: props.valoresCamposPersonalizados || {} as Record<number, any>,
    etiquetas: props.hito?.etiquetas?.map((e: any) => e.id) ?? [] as number[],
    orden: props.hito?.orden || props.siguienteOrden,
    crear_entregables_predefinidos: false,
});

// Handler para selección de responsable desde modal
const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
    if (data.userIds.length > 0) {
        const userId = data.userIds[0];
        form.responsable_id = userId;

        if (usuariosCache.value.has(userId)) {
            responsableSeleccionado.value = usuariosCache.value.get(userId) || null;
        } else {
            // Usuario nuevo, crear entrada temporal
            responsableSeleccionado.value = {
                id: userId,
                name: `Usuario seleccionado (ID: ${userId})`,
                email: 'Se actualizará al guardar'
            } as User;
            usuariosCache.value.set(userId, responsableSeleccionado.value);
        }
    }
};

// Limpiar responsable seleccionado
const clearResponsable = () => {
    form.responsable_id = null;
    responsableSeleccionado.value = null;
};

// Submit del formulario
const submit = () => {
    emit('submit', form);
};

// Computed para verificar si está procesando
const isProcessing = computed(() => form.processing || props.externalProcessing);

// Computed para texto del botón
const submitButtonText = computed(() => {
    if (isProcessing.value) {
        return props.mode === 'create' ? 'Creando...' : 'Guardando...';
    }
    return props.mode === 'create' ? 'Crear Hito' : 'Guardar Cambios';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Categorías (al inicio del formulario) -->
        <Card v-if="showEtiquetas && categorias && categorias.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Tag class="h-5 w-5" />
                    Categorías
                </CardTitle>
                <CardDescription>
                    Asigna etiquetas para categorizar y organizar este hito
                </CardDescription>
            </CardHeader>
            <CardContent>
                <EtiquetaSelector
                    v-model="form.etiquetas"
                    :categorias="categorias"
                    :max-etiquetas="10"
                    placeholder="Seleccionar etiquetas para el hito..."
                    description="Puedes asignar hasta 10 etiquetas"
                />
            </CardContent>
        </Card>

        <!-- Formulario principal -->
        <Card>
            <CardHeader>
                <CardTitle>{{ mode === 'create' ? 'Información del Hito' : 'Editar Hito' }}</CardTitle>
                <CardDescription v-if="proyectoNombre">
                    {{ mode === 'create' ? 'Define los detalles del nuevo hito para el proyecto' : `Editando hito del proyecto: ${proyectoNombre}` }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Nombre -->
                    <div class="space-y-2">
                        <Label for="nombre" required>Nombre del Hito</Label>
                        <Input
                            id="nombre"
                            v-model="form.nombre"
                            type="text"
                            placeholder="Ej: Pre-producción"
                            :disabled="isProcessing"
                        />
                        <span v-if="form.errors.nombre" class="text-sm text-red-500">{{ form.errors.nombre }}</span>
                    </div>

                    <!-- Descripción -->
                    <div class="space-y-2">
                        <Label for="descripcion">Descripción</Label>
                        <Textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            placeholder="Describe el objetivo y alcance del hito..."
                            :disabled="isProcessing"
                            rows="3"
                        />
                        <span v-if="form.errors.descripcion" class="text-sm text-red-500">{{ form.errors.descripcion }}</span>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="fecha_inicio">Fecha de Inicio</Label>
                            <Input
                                id="fecha_inicio"
                                v-model="form.fecha_inicio"
                                type="date"
                                :disabled="isProcessing"
                            />
                            <span v-if="form.errors.fecha_inicio" class="text-sm text-red-500">{{ form.errors.fecha_inicio }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="fecha_fin">Fecha de Fin</Label>
                            <Input
                                id="fecha_fin"
                                v-model="form.fecha_fin"
                                type="date"
                                :disabled="isProcessing"
                            />
                            <span v-if="form.errors.fecha_fin" class="text-sm text-red-500">{{ form.errors.fecha_fin }}</span>
                        </div>
                    </div>

                    <!-- Estado y Responsable -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="estado">{{ mode === 'create' ? 'Estado Inicial' : 'Estado' }}</Label>
                            <Select v-model="form.estado">
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="estado in estados" :key="estado.value" :value="estado.value">
                                        {{ estado.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.estado" class="text-sm text-red-500">{{ form.errors.estado }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="responsable_id">Responsable</Label>
                            <div class="space-y-2">
                                <!-- Responsable seleccionado -->
                                <div v-if="responsableSeleccionado" class="p-3 bg-muted rounded-lg flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ responsableSeleccionado.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ responsableSeleccionado.email }}</p>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" @click="clearResponsable">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <!-- Botón para seleccionar responsable -->
                                <Button
                                    v-if="searchUsersEndpoint"
                                    type="button"
                                    variant="outline"
                                    @click="showResponsableModal = true"
                                    class="w-full"
                                >
                                    <UserPlus class="h-4 w-4 mr-2" />
                                    {{ responsableSeleccionado ? 'Cambiar Responsable' : 'Seleccionar Responsable' }}
                                </Button>
                                <!-- Select simple si no hay endpoint de búsqueda -->
                                <Select v-else v-model="form.responsable_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar responsable" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null">Sin responsable</SelectItem>
                                        <SelectItem v-for="user in responsables" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <span v-if="form.errors.responsable_id" class="text-sm text-red-500">{{ form.errors.responsable_id }}</span>
                        </div>
                    </div>

                    <!-- Selector de hito padre -->
                    <div v-if="showParentSelector && hitosDisponibles && hitosDisponibles.length > 0" class="space-y-2">
                        <Label for="parent_id">Hito Padre (opcional)</Label>
                        <Select v-model="form.parent_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Sin padre (raíz)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Sin padre (raíz)</SelectItem>
                                <SelectItem
                                    v-for="hitoOpt in hitosDisponibles"
                                    :key="hitoOpt.id"
                                    :value="hitoOpt.id"
                                    :disabled="hito && hitoOpt.id === hito.id"
                                >
                                    {{ '—'.repeat(hitoOpt.nivel) }} {{ hitoOpt.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-sm text-muted-foreground">Selecciona un hito padre para crear una jerarquía</p>
                        <span v-if="form.errors.parent_id" class="text-sm text-red-500">{{ form.errors.parent_id }}</span>
                    </div>

                    <!-- Orden (solo en edit) -->
                    <div v-if="mode === 'edit'" class="space-y-2">
                        <Label for="orden">Orden de visualización</Label>
                        <Input
                            id="orden"
                            v-model.number="form.orden"
                            type="number"
                            min="0"
                            :disabled="isProcessing"
                        />
                        <span v-if="form.errors.orden" class="text-sm text-red-500">{{ form.errors.orden }}</span>
                        <p class="text-sm text-muted-foreground">
                            Define el orden en que aparece este hito en la lista
                        </p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" @click="emit('cancel')">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="isProcessing">
                            <Save class="h-4 w-4 mr-2" />
                            {{ submitButtonText }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Campos Personalizados -->
        <CamposPersonalizadosForm
            v-if="showCamposPersonalizados && camposPersonalizados && camposPersonalizados.length > 0"
            :campos="camposPersonalizados"
            :valores="form.campos_personalizados"
            :errors="form.errors"
            @update="form.campos_personalizados = $event"
        />

        <!-- Modal de selección de responsable -->
        <AddUsersModal
            v-if="searchUsersEndpoint"
            v-model="showResponsableModal"
            title="Seleccionar Responsable del Hito"
            description="Selecciona el usuario que será responsable de este hito"
            :search-endpoint="searchUsersEndpoint"
            :excluded-ids="form.responsable_id ? [form.responsable_id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Responsable"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleResponsableSelect"
        />
    </div>
</template>
