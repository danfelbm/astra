<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
// Removido Calendar y Popover - usaremos Input type="date" HTML5
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@modules/Core/Resources/js/components/ui/radio-group';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import AddUsersModal from '@modules/Core/Resources/js/components/modals/AddUsersModal.vue';
import CamposPersonalizadosForm from "@modules/Proyectos/Resources/js/components/CamposPersonalizadosForm.vue";
import EtiquetaSelector from "@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue";
import { cn } from '@modules/Core/Resources/js/lib/utils';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import {
  Save,
  ArrowLeft,
  Users,
  User,
  UserPlus,
  Flag,
  AlertCircle,
  CheckCircle,
  Target,
  FileText,
  X,
  Tag
} from 'lucide-vue-next';
import { Link, router } from '@inertiajs/vue3';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import type { Hito, EstadoEntregable, PrioridadEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";

// Props
interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface CampoPersonalizado {
  id: number;
  nombre: string;
  tipo: string;
  es_requerido: boolean;
  opciones?: any[];
}

interface Props {
  proyecto: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  hito: Hito;
  usuarios: Usuario[];
  camposPersonalizados?: CampoPersonalizado[];
  categorias?: CategoriaEtiqueta[];
  estados: Array<{ value: string; label: string }>;
  prioridades: Array<{ value: string; label: string; color: string }>;
  siguienteOrden?: number;
}

const props = defineProps<Props>();

const { toast } = useToast();

// Form data
const form = useForm({
  nombre: '',
  descripcion: '',
  fecha_inicio: '',
  fecha_fin: '',
  estado: 'pendiente' as EstadoEntregable,
  prioridad: 'media' as PrioridadEntregable,
  responsable_id: null as number | null,
  usuarios_asignados: [] as Array<{ user_id: number; rol: 'colaborador' | 'revisor' }>,
  campos_personalizados: {} as Record<number, any>,
  etiquetas: [] as number[],
  orden: props.siguienteOrden || 1,
  notas: '',
});

// Estado local
const showUsuariosAsignados = ref(false);
const usuarioSeleccionado = ref<number | null>(null);
const rolSeleccionado = ref<'colaborador' | 'revisor'>('colaborador');

// Estados para los modales de selección de usuarios
const showResponsableModal = ref(false);
const showColaboradoresModal = ref(false);

// Helper para obtener route
const { route } = window as any;

// Breadcrumbs
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Proyectos', href: '/admin/proyectos' },
  { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
  { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
  { title: props.hito.nombre, href: `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}` },
  { title: 'Entregables', href: `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables` },
  { title: 'Nuevo Entregable' },
]);

// Estado para almacenar el responsable seleccionado con cache
const responsableSeleccionado = ref<Usuario | null>(null);
const usuariosCache = ref<Map<number, Usuario>>(new Map());

// Inicializar cache con usuarios disponibles
props.usuarios.forEach(u => usuariosCache.value.set(u.id, u));

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

        // Buscar primero en el cache
        if (usuariosCache.value.has(userId)) {
            responsableSeleccionado.value = usuariosCache.value.get(userId) || null;
        } else {
            // Si no está en cache, crear un placeholder temporal
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
        // Verificar que no esté ya agregado
        const yaExiste = form.usuarios_asignados.some(u => u.user_id === userId);
        if (!yaExiste) {
            form.usuarios_asignados.push({
                user_id: userId,
                rol: data.extraData.rol || 'colaborador'
            });

            // Agregar al cache si no existe
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

// Métodos
const submit = () => {
  // Transformar los datos para el backend
  const dataToSend = {
    ...form.data(),
    // El backend espera 'usuarios' en lugar de 'usuarios_asignados'
    usuarios: form.usuarios_asignados,
  };
  // Eliminar el campo con el nombre incorrecto
  delete dataToSend.usuarios_asignados;

  form.transform(() => dataToSend).post(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables`, {
    preserveScroll: true
  });
};

const cancel = () => {
  router.get(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables`);
};

const agregarUsuarioAsignado = () => {
  if (!usuarioSeleccionado.value) return;

  const yaAsignado = form.usuarios_asignados.some(u => u.user_id === usuarioSeleccionado.value);
  if (yaAsignado) {
    toast.warning('Este usuario ya está asignado');
    return;
  }

  form.usuarios_asignados.push({
    user_id: usuarioSeleccionado.value,
    rol: rolSeleccionado.value,
  });

  usuarioSeleccionado.value = null;
  rolSeleccionado.value = 'colaborador';
};

const removerUsuarioAsignado = (userId: number) => {
  const index = form.usuarios_asignados.findIndex(u => u.user_id === userId);
  if (index > -1) {
    form.usuarios_asignados.splice(index, 1);
  }
};

const getUsuarioInfo = (userId: number) => {
  // Buscar primero en el cache
  if (usuariosCache.value.has(userId)) {
    return usuariosCache.value.get(userId);
  }
  // Si no está en cache, buscar en la lista original
  return props.usuarios.find(u => u.id === userId);
};

// Removida función formatFechaSeleccionada - ya no es necesaria con input date

// Computed
const usuariosDisponibles = computed(() => {
  const asignados = form.usuarios_asignados.map(u => u.user_id);
  if (form.responsable_id) {
    asignados.push(form.responsable_id);
  }
  return props.usuarios.filter(u => !asignados.includes(u.id));
});

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
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div>
      <h2 class="text-3xl font-bold tracking-tight">Nuevo Entregable</h2>
      <p class="text-muted-foreground mt-2">
        Agregar un nuevo entregable al hito "{{ hito.nombre }}"
      </p>
    </div>

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
    <form @submit.prevent="submit" class="space-y-6">
      <!-- Información básica -->
      <Card>
        <CardHeader>
          <CardTitle>Información Básica</CardTitle>
          <CardDescription>
            Datos principales del entregable
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <Label for="nombre" class="required">
              Nombre del entregable
            </Label>
            <Input
              id="nombre"
              v-model="form.nombre"
              placeholder="Ej: Documento de diseño técnico"
              :class="{ 'border-red-500': form.errors.nombre }"
            />
            <p v-if="form.errors.nombre" class="text-sm text-red-600 mt-1">
              {{ form.errors.nombre }}
            </p>
          </div>

          <div>
            <Label for="descripcion">
              Descripción
            </Label>
            <Textarea
              id="descripcion"
              v-model="form.descripcion"
              placeholder="Describe el entregable y sus objetivos..."
              rows="4"
              :class="{ 'border-red-500': form.errors.descripcion }"
            />
            <p v-if="form.errors.descripcion" class="text-sm text-red-600 mt-1">
              {{ form.errors.descripcion }}
            </p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <Label for="estado">
                Estado inicial
              </Label>
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
              <Label for="prioridad">
                Prioridad
              </Label>
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
        </CardContent>
      </Card>

      <!-- Fechas -->
      <Card>
        <CardHeader>
          <CardTitle>Fechas</CardTitle>
          <CardDescription>
            Define el período de ejecución del entregable
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <Label for="fecha_inicio">
                Fecha de inicio
              </Label>
              <Input
                id="fecha_inicio"
                v-model="form.fecha_inicio"
                type="date"
                :disabled="form.processing"
              />
              <p v-if="form.errors.fecha_inicio" class="text-sm text-red-600 mt-1">
                {{ form.errors.fecha_inicio }}
              </p>
            </div>

            <div>
              <Label for="fecha_fin">
                Fecha de fin
              </Label>
              <Input
                id="fecha_fin"
                v-model="form.fecha_fin"
                type="date"
                :disabled="form.processing"
              />
              <p v-if="form.errors.fecha_fin" class="text-sm text-red-600 mt-1">
                {{ form.errors.fecha_fin }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Asignación -->
      <Card>
        <CardHeader>
          <CardTitle>Asignación</CardTitle>
          <CardDescription>
            Asigna un responsable y colaboradores al entregable
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Responsable principal -->
          <div>
            <Label for="responsable" class="required">
              Responsable principal
            </Label>
            <div class="space-y-2">
              <!-- Mostrar responsable seleccionado -->
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
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="form.responsable_id = null"
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
            <p v-if="form.errors.responsable_id" class="text-sm text-red-600 mt-1">
              {{ form.errors.responsable_id }}
            </p>
          </div>

          <!-- Colaboradores adicionales -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <Label>
                Colaboradores adicionales
              </Label>
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="showColaboradoresModal = true"
              >
                <UserPlus class="mr-2 h-4 w-4" />
                Agregar colaboradores
              </Button>
            </div>

            <!-- Mostrar colaboradores asignados -->
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
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="removerUsuarioAsignado(asignado.user_id)"
                >
                  <X class="h-4 w-4" />
                </Button>
              </div>
            </div>
            <div v-else class="text-sm text-muted-foreground mt-3">
              No hay colaboradores asignados
            </div>

            <!-- Oculto la sección antigua pero la mantengo para compatibilidad -->
            <div v-if="false" class="space-y-4 p-4 border rounded-lg">
              <div class="grid gap-4 md:grid-cols-3">
                <Select v-model="usuarioSeleccionado">
                  <SelectTrigger>
                    <SelectValue placeholder="Seleccionar usuario" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="usuario in usuariosDisponibles" :key="usuario.id" :value="usuario.id">
                      {{ usuario.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>

                <RadioGroup v-model="rolSeleccionado" class="flex gap-4">
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem value="colaborador" id="colaborador" />
                    <Label for="colaborador">Colaborador</Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem value="revisor" id="revisor" />
                    <Label for="revisor">Revisor</Label>
                  </div>
                </RadioGroup>

                <Button
                  type="button"
                  @click="agregarUsuarioAsignado"
                  :disabled="!usuarioSeleccionado"
                >
                  Agregar
                </Button>
              </div>

              <!-- Lista de usuarios asignados -->
              <div v-if="form.usuarios_asignados.length > 0" class="space-y-2">
                <p class="text-sm font-medium text-muted-foreground">Usuarios asignados:</p>
                <div
                  v-for="asignado in form.usuarios_asignados"
                  :key="asignado.user_id"
                  class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded"
                >
                  <div class="flex items-center gap-2">
                    <Avatar class="h-6 w-6">
                      <AvatarImage v-if="getUsuarioInfo(asignado.user_id)?.avatar" :src="getUsuarioInfo(asignado.user_id)?.avatar" />
                      <AvatarFallback>{{ getUsuarioInfo(asignado.user_id)?.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <span class="text-sm font-medium">{{ getUsuarioInfo(asignado.user_id)?.name }}</span>
                    <Badge variant="outline">{{ asignado.rol }}</Badge>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removerUsuarioAsignado(asignado.user_id)"
                  >
                    Remover
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Notas adicionales -->
      <Card>
        <CardHeader>
          <CardTitle>Notas adicionales</CardTitle>
          <CardDescription>
            Información adicional o consideraciones especiales
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Textarea
            v-model="form.notas"
            placeholder="Notas adicionales sobre el entregable..."
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
          <CardDescription>
            Asigna etiquetas para categorizar y organizar este entregable
          </CardDescription>
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
          {{ form.processing ? 'Creando...' : 'Crear Entregable' }}
        </Button>
      </div>
    </form>
    </div>

    <!-- Modal de selección de responsable principal -->
    <AddUsersModal
      v-model="showResponsableModal"
      title="Seleccionar Responsable Principal"
      description="Selecciona el usuario que será el responsable principal del entregable"
      :search-endpoint="route('admin.proyectos.search-users')"
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
      :search-endpoint="route('admin.proyectos.search-users')"
      :excluded-ids="excludedIdsColaboradores"
      :extra-fields="extraFieldsColaboradores"
      submit-button-text="Agregar Colaboradores"
      search-placeholder="Buscar por nombre, email, documento o teléfono..."
      @submit="handleColaboradoresSelect"
    />
  </AdminLayout>
</template>

<style scoped>
.required::after {
  content: ' *';
  color: rgb(239 68 68);
}
</style>