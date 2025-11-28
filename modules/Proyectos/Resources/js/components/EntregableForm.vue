<script setup lang="ts">
// Componente reutilizable para crear/editar entregables
// Usado tanto en Admin como en User
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import AddUsersModal from '@modules/Core/Resources/js/components/modals/AddUsersModal.vue';
import CamposPersonalizadosForm from "@modules/Proyectos/Resources/js/components/CamposPersonalizadosForm.vue";
import EtiquetaSelector from "@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue";
import {
  Save,
  ArrowLeft,
  UserPlus,
  Flag,
  AlertCircle,
  CheckCircle,
  X,
  Tag
} from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import type { Hito, Entregable, EstadoEntregable, PrioridadEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

// Interfaces
interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface UsuarioAsignado {
  user_id: number;
  rol: 'colaborador' | 'revisor';
}

interface CampoPersonalizado {
  id: number;
  nombre: string;
  tipo: string;
  es_requerido: boolean;
  opciones?: any[];
}

interface Props {
  mode: 'create' | 'edit';
  proyecto: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  hito: Hito;
  entregable?: Entregable;
  usuarios: Usuario[];
  usuariosAsignados?: UsuarioAsignado[];
  camposPersonalizados?: CampoPersonalizado[];
  valoresCamposPersonalizados?: Record<number, any>;
  categorias?: CategoriaEtiqueta[];
  estados: Array<{ value: string; label: string }>;
  prioridades: Array<{ value: string; label: string; color: string }>;
  siguienteOrden?: number;
  // Rutas para el submit y cancelar
  submitUrl: string;
  cancelUrl: string;
  // Endpoint de búsqueda de usuarios
  searchUsersEndpoint: string;
}

const props = withDefaults(defineProps<Props>(), {
  usuariosAsignados: () => [],
  valoresCamposPersonalizados: () => ({}),
  siguienteOrden: 1,
});

const emit = defineEmits<{
  success: [message: string];
  error: [message: string];
}>();

const { toast } = useToast();

// Estado original para detectar cambios (modo edit)
const estadoOriginal = props.entregable?.estado || 'pendiente';

// Inicializar form según el modo
const form = useForm({
  nombre: props.entregable?.nombre || '',
  descripcion: props.entregable?.descripcion || '',
  fecha_inicio: props.entregable?.fecha_inicio || '',
  fecha_fin: props.entregable?.fecha_fin || '',
  estado: (props.entregable?.estado || 'pendiente') as EstadoEntregable,
  prioridad: (props.entregable?.prioridad || 'media') as PrioridadEntregable,
  responsable_id: props.entregable?.responsable_id || null as number | null,
  usuarios_asignados: props.usuariosAsignados || [] as Array<{ user_id: number; rol: 'colaborador' | 'revisor' }>,
  campos_personalizados: props.valoresCamposPersonalizados || {} as Record<number, any>,
  etiquetas: props.entregable?.etiquetas?.map((e: any) => e.id) ?? [] as number[],
  orden: props.entregable?.orden || props.siguienteOrden || 1,
  notas: '',
  observaciones_estado: '',
});

// Detectar si el estado cambió (solo en modo edit)
const estadoCambio = computed(() => {
  if (props.mode === 'create') return false;
  return form.estado !== estadoOriginal;
});

// Label para el cambio de estado
const cambioEstadoLabel = computed(() => {
  if (!estadoCambio.value) return '';
  const estadoAnteriorLabel = props.estados.find(e => e.value === estadoOriginal)?.label || estadoOriginal;
  const estadoNuevoLabel = props.estados.find(e => e.value === form.estado)?.label || form.estado;
  return `${estadoAnteriorLabel} → ${estadoNuevoLabel}`;
});

// Estado local
const showResponsableModal = ref(false);
const showColaboradoresModal = ref(false);

// Estado para almacenar el responsable seleccionado con cache
const responsableSeleccionado = ref<Usuario | null>(null);
const usuariosCache = ref<Map<number, Usuario>>(new Map());

// Inicializar cache con usuarios disponibles
props.usuarios.forEach(u => usuariosCache.value.set(u.id, u));

// Inicializar el responsable si ya existe (modo edit)
if (props.entregable?.responsable_id) {
  const found = props.usuarios.find(u => u.id === props.entregable?.responsable_id);
  if (found) {
    responsableSeleccionado.value = found;
  } else {
    const entregableAny = props.entregable as any;
    responsableSeleccionado.value = {
      id: props.entregable.responsable_id,
      name: entregableAny.responsable?.name || `Usuario #${props.entregable.responsable_id}`,
      email: entregableAny.responsable?.email || ''
    } as Usuario;
    usuariosCache.value.set(props.entregable.responsable_id, responsableSeleccionado.value);
  }
}

// Inicializar cache de colaboradores asignados
props.usuariosAsignados?.forEach(asignado => {
  if (!usuariosCache.value.has(asignado.user_id)) {
    usuariosCache.value.set(asignado.user_id, {
      id: (asignado as any).user?.id || asignado.user_id,
      name: (asignado as any).user?.name || `Usuario #${asignado.user_id}`,
      email: (asignado as any).user?.email || ''
    } as Usuario);
  }
});

// Campo extra para el modal de colaboradores
const extraFieldsColaboradores = computed(() => [
  {
    name: 'rol',
    label: 'Rol',
    type: 'select' as const,
    options: [
      { value: 'colaborador', label: 'Colaborador' },
      { value: 'revisor', label: 'Revisor' }
    ],
    value: 'colaborador',
    required: true
  }
]);

// IDs excluidos para el modal de colaboradores
const excludedIdsColaboradores = computed(() => {
  const ids = form.usuarios_asignados.map(u => u.user_id);
  if (form.responsable_id) {
    ids.push(form.responsable_id);
  }
  return ids;
});

// Manejar selección del responsable principal
const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
  if (data.userIds.length > 0) {
    const userId = data.userIds[0];
    form.responsable_id = userId;
    if (usuariosCache.value.has(userId)) {
      responsableSeleccionado.value = usuariosCache.value.get(userId) || null;
    } else {
      responsableSeleccionado.value = {
        id: userId,
        name: `Usuario seleccionado (ID: ${userId})`,
        email: 'Se actualizará al guardar'
      } as Usuario;
      usuariosCache.value.set(userId, responsableSeleccionado.value);
    }
  }
};

// Manejar selección de colaboradores
const handleColaboradoresSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
  data.userIds.forEach(userId => {
    const yaExiste = form.usuarios_asignados.some(u => u.user_id === userId);
    if (!yaExiste) {
      form.usuarios_asignados.push({
        user_id: userId,
        rol: data.extraData.rol || 'colaborador'
      });
      if (!usuariosCache.value.has(userId)) {
        usuariosCache.value.set(userId, {
          id: userId,
          name: `Usuario #${userId}`,
          email: 'Pendiente de actualizar'
        } as Usuario);
      }
    }
  });
};

// Remover responsable
const clearResponsable = () => {
  form.responsable_id = null;
  responsableSeleccionado.value = null;
};

// Remover usuario asignado
const removerUsuarioAsignado = (userId: number) => {
  const index = form.usuarios_asignados.findIndex(u => u.user_id === userId);
  if (index > -1) {
    form.usuarios_asignados.splice(index, 1);
  }
};

// Obtener info de usuario
const getUsuarioInfo = (userId: number) => {
  if (usuariosCache.value.has(userId)) {
    return usuariosCache.value.get(userId);
  }
  return props.usuarios.find(u => u.id === userId);
};

// Submit del formulario
const submit = () => {
  // Transformar los datos para el backend
  const dataToSend = {
    ...form.data(),
    usuarios: form.usuarios_asignados,
  };
  delete dataToSend.usuarios_asignados;

  if (props.mode === 'create') {
    form.transform(() => dataToSend).post(props.submitUrl, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Entregable creado exitosamente');
        emit('success', 'Entregable creado exitosamente');
      },
      onError: () => {
        toast.error('Error al crear el entregable');
        emit('error', 'Error al crear el entregable');
      },
    });
  } else {
    form.transform(() => dataToSend).put(props.submitUrl, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Entregable actualizado exitosamente');
        emit('success', 'Entregable actualizado exitosamente');
      },
      onError: () => {
        toast.error('Error al actualizar el entregable');
        emit('error', 'Error al actualizar el entregable');
      },
    });
  }
};

// Cancelar
const cancel = () => {
  router.get(props.cancelUrl);
};

// Validaciones
const validationErrors = computed(() => {
  const errors: string[] = [];
  if (!form.nombre) errors.push('El nombre es requerido');
  if (form.nombre.length > 255) errors.push('El nombre no puede exceder 255 caracteres');
  if (form.fecha_inicio && form.fecha_fin) {
    if (new Date(form.fecha_inicio) > new Date(form.fecha_fin)) {
      errors.push('La fecha de inicio no puede ser posterior a la fecha de fin');
    }
  }
  if (!form.responsable_id) errors.push('Debe asignar un responsable');
  return errors;
});

const canSubmit = computed(() => {
  return validationErrors.value.length === 0 && !form.processing;
});

// Información del estado actual (para modo edit)
const estadoInfo = computed(() => {
  if (props.mode === 'edit' && props.entregable?.estado === 'completado' && props.entregable?.completado_at) {
    const fecha = new Date(props.entregable.completado_at).toLocaleDateString('es-ES');
    const usuario = (props.entregable as any).completado_por?.name || 'Usuario desconocido';
    return `Completado el ${fecha} por ${usuario}`;
  }
  return null;
});
</script>

<template>
  <div class="space-y-6">
    <!-- Estado actual si está completado -->
    <Alert v-if="estadoInfo" class="bg-green-50 dark:bg-green-900/20 border-green-200">
      <CheckCircle class="h-4 w-4 text-green-600" />
      <AlertDescription class="text-green-800 dark:text-green-200">
        {{ estadoInfo }}
      </AlertDescription>
    </Alert>

    <!-- Información del Hito -->
    <Card>
      <CardHeader>
        <CardTitle class="text-base">Información del Hito</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <p class="text-sm text-muted-foreground">Hito</p>
            <p class="font-medium">{{ hito.nombre }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">Estado</p>
            <Badge>{{ hito.estado_label || hito.estado }}</Badge>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">Progreso</p>
            <p class="font-medium">{{ hito.porcentaje_completado || 0 }}%</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Formulario -->
    <form @submit.prevent="submit" class="space-y-4">
      <!-- Información básica -->
      <Card>
        <CardHeader>
          <CardTitle>Información Básica</CardTitle>
          <CardDescription>Datos principales del entregable</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <Label for="nombre" class="required">Nombre del entregable</Label>
            <Input
              id="nombre"
              v-model="form.nombre"
              placeholder="Ej: Documento de diseño técnico"
              :class="{ 'border-red-500': form.errors.nombre }"
            />
            <p v-if="form.errors.nombre" class="text-sm text-red-600 mt-1">{{ form.errors.nombre }}</p>
          </div>

          <div>
            <Label for="descripcion">Descripción</Label>
            <Textarea
              id="descripcion"
              v-model="form.descripcion"
              placeholder="Describe el entregable y sus objetivos..."
              rows="4"
              :class="{ 'border-red-500': form.errors.descripcion }"
            />
            <p v-if="form.errors.descripcion" class="text-sm text-red-600 mt-1">{{ form.errors.descripcion }}</p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <Label for="estado">{{ mode === 'create' ? 'Estado inicial' : 'Estado' }}</Label>
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
            </div>

            <div>
              <Label for="prioridad">Prioridad</Label>
              <Select v-model="form.prioridad">
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar prioridad" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="prioridad in prioridades" :key="prioridad.value" :value="prioridad.value">
                    <div class="flex items-center gap-2">
                      <Flag class="h-4 w-4" :class="{
                        'text-blue-500': prioridad.value === 'baja',
                        'text-yellow-500': prioridad.value === 'media',
                        'text-red-500': prioridad.value === 'alta'
                      }" />
                      {{ prioridad.label }}
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Campo de observaciones cuando cambia el estado (solo en modo edit) -->
          <div v-if="estadoCambio" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <Label for="observaciones_estado" class="text-blue-800 dark:text-blue-200">
              Observaciones del cambio de estado
              <span class="text-sm font-normal ml-2">({{ cambioEstadoLabel }})</span>
            </Label>
            <Textarea
              id="observaciones_estado"
              v-model="form.observaciones_estado"
              placeholder="Describe el motivo del cambio de estado..."
              rows="3"
              class="mt-2"
            />
            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
              Las observaciones quedarán registradas en el historial de cambios.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Fechas -->
      <Card>
        <CardHeader>
          <CardTitle>Fechas</CardTitle>
          <CardDescription>Define el período de ejecución del entregable</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <Label for="fecha_inicio">Fecha de inicio</Label>
              <Input id="fecha_inicio" v-model="form.fecha_inicio" type="date" :disabled="form.processing" />
              <p v-if="form.errors.fecha_inicio" class="text-sm text-red-600 mt-1">{{ form.errors.fecha_inicio }}</p>
            </div>
            <div>
              <Label for="fecha_fin">Fecha de fin</Label>
              <Input id="fecha_fin" v-model="form.fecha_fin" type="date" :disabled="form.processing" />
              <p v-if="form.errors.fecha_fin" class="text-sm text-red-600 mt-1">{{ form.errors.fecha_fin }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Asignación -->
      <Card>
        <CardHeader>
          <CardTitle>Asignación</CardTitle>
          <CardDescription>Asigna un responsable y colaboradores al entregable</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Responsable principal -->
          <div>
            <Label for="responsable" class="required">Responsable principal</Label>
            <div class="space-y-2">
              <div v-if="responsableSeleccionado" class="p-3 bg-muted rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Avatar class="h-8 w-8">
                    <AvatarImage v-if="responsableSeleccionado.avatar" :src="responsableSeleccionado.avatar" />
                    <AvatarFallback>{{ responsableSeleccionado.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p class="font-medium">{{ responsableSeleccionado.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ responsableSeleccionado.email }}</p>
                  </div>
                </div>
                <Button type="button" variant="ghost" size="sm" @click="clearResponsable">
                  <X class="h-4 w-4" />
                </Button>
              </div>
              <Button type="button" variant="outline" @click="showResponsableModal = true" class="w-full">
                <UserPlus class="h-4 w-4 mr-2" />
                {{ responsableSeleccionado ? 'Cambiar Responsable' : 'Seleccionar Responsable' }}
              </Button>
            </div>
            <p v-if="form.errors.responsable_id" class="text-sm text-red-600 mt-1">{{ form.errors.responsable_id }}</p>
          </div>

          <!-- Colaboradores adicionales -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <Label>Colaboradores adicionales</Label>
              <Button type="button" variant="outline" size="sm" @click="showColaboradoresModal = true">
                <UserPlus class="mr-2 h-4 w-4" />
                Agregar colaboradores
              </Button>
            </div>
            <div v-if="form.usuarios_asignados.length > 0" class="space-y-2 mt-3">
              <div
                v-for="asignado in form.usuarios_asignados"
                :key="asignado.user_id"
                class="p-3 bg-muted rounded-lg flex items-center justify-between"
              >
                <div class="flex items-center gap-2">
                  <Avatar class="h-8 w-8">
                    <AvatarImage v-if="getUsuarioInfo(asignado.user_id)?.avatar" :src="getUsuarioInfo(asignado.user_id)?.avatar" />
                    <AvatarFallback>{{ getUsuarioInfo(asignado.user_id)?.name?.substring(0, 2)?.toUpperCase() || 'US' }}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p class="font-medium">{{ getUsuarioInfo(asignado.user_id)?.name || `Usuario #${asignado.user_id}` }}</p>
                    <p class="text-sm text-muted-foreground">{{ getUsuarioInfo(asignado.user_id)?.email || '' }}</p>
                  </div>
                  <Badge variant="outline">{{ asignado.rol }}</Badge>
                </div>
                <Button type="button" variant="ghost" size="sm" @click="removerUsuarioAsignado(asignado.user_id)">
                  <X class="h-4 w-4" />
                </Button>
              </div>
            </div>
            <div v-else class="text-sm text-muted-foreground mt-3">No hay colaboradores asignados</div>
          </div>
        </CardContent>
      </Card>

      <!-- Notas adicionales -->
      <Card>
        <CardHeader>
          <CardTitle>{{ mode === 'create' ? 'Notas adicionales' : 'Notas de actualización' }}</CardTitle>
          <CardDescription>
            {{ mode === 'create' ? 'Información adicional o consideraciones especiales' : 'Describe los cambios realizados (opcional)' }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Textarea
            v-model="form.notas"
            :placeholder="mode === 'create' ? 'Notas adicionales sobre el entregable...' : 'Notas sobre los cambios realizados...'"
            rows="4"
          />
        </CardContent>
      </Card>

      <!-- Campos Personalizados -->
      <CamposPersonalizadosForm
        v-if="camposPersonalizados && camposPersonalizados.length > 0"
        :campos="camposPersonalizados"
        :valores="form.campos_personalizados"
        :errors="form.errors"
        @update="form.campos_personalizados = $event"
      />

      <!-- Etiquetas -->
      <Card v-if="categorias && categorias.length > 0">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Tag class="h-5 w-5" />
            Etiquetas
          </CardTitle>
          <CardDescription>Asigna etiquetas para categorizar y organizar este entregable</CardDescription>
        </CardHeader>
        <CardContent>
          <EtiquetaSelector
            v-model="form.etiquetas"
            :categorias="categorias"
            :max-etiquetas="10"
            placeholder="Seleccionar etiquetas para el entregable..."
            description="Puedes asignar hasta 10 etiquetas"
          />
        </CardContent>
      </Card>

      <!-- Errores de validación -->
      <Alert v-if="validationErrors.length > 0" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          <ul class="list-disc pl-4">
            <li v-for="error in validationErrors" :key="error">{{ error }}</li>
          </ul>
        </AlertDescription>
      </Alert>

      <!-- Botones de acción -->
      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="cancel">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Cancelar
        </Button>
        <Button type="submit" :disabled="!canSubmit">
          <Save class="mr-2 h-4 w-4" />
          {{ form.processing ? (mode === 'create' ? 'Creando...' : 'Actualizando...') : (mode === 'create' ? 'Crear Entregable' : 'Actualizar Entregable') }}
        </Button>
      </div>
    </form>

    <!-- Modal de selección de responsable principal -->
    <AddUsersModal
      v-model="showResponsableModal"
      title="Seleccionar Responsable Principal"
      description="Selecciona el usuario que será el responsable principal del entregable"
      :search-endpoint="searchUsersEndpoint"
      :excluded-ids="form.responsable_id ? [form.responsable_id] : []"
      :max-selection="1"
      submit-button-text="Seleccionar Responsable"
      search-placeholder="Buscar por nombre, email, documento o teléfono..."
      @submit="handleResponsableSelect"
    />

    <!-- Modal de selección de colaboradores -->
    <AddUsersModal
      v-model="showColaboradoresModal"
      title="Agregar Colaboradores"
      description="Selecciona los usuarios que colaborarán en este entregable"
      :search-endpoint="searchUsersEndpoint"
      :excluded-ids="excludedIdsColaboradores"
      :extra-fields="extraFieldsColaboradores"
      submit-button-text="Agregar Colaboradores"
      search-placeholder="Buscar por nombre, email, documento o teléfono..."
      @submit="handleColaboradoresSelect"
    />
  </div>
</template>

<style scoped>
.required::after {
  content: ' *';
  color: rgb(239 68 68);
}
</style>
