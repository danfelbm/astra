<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import {
  Calendar,
  Clock,
  AlertCircle,
  CheckCircle2,
  Plus,
  Search,
  Filter,
  MoreHorizontal,
  ArrowLeft,
  Edit,
  Trash2,
  Users,
  Flag,
  Target,
  CircleDot,
  ChevronUp,
  ChevronDown
} from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useToast } from '@modules/Core/Resources/js/composables/useToast';
import { debounce } from 'lodash';
import type { Hito, Entregable, EstadoEntregable, PrioridadEntregable } from '@modules/Proyectos/Resources/js/types/hitos';

// Props con tipos
interface Props {
  proyecto: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  hito: Hito;
  entregables: Entregable[] | {
    data: Entregable[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  filters: {
    search?: string;
    estado?: EstadoEntregable;
    prioridad?: PrioridadEntregable;
    responsable_id?: number;
  };
  resumenUsuarios?: Array<{
    user_id: number;
    user_name: string;
    total_asignados: number;
    completados: number;
    en_progreso: number;
    pendientes: number;
  }>;
  estados: Array<{ value: string; label: string }>;
  prioridades: Array<{ value: string; label: string; color: string }>;
  canCreate: boolean;
  canEdit: boolean;
  canDelete: boolean;
  canComplete: boolean;
  canAssign: boolean;
}

const props = defineProps<Props>();

const { toast } = useToast();

// Normalizar entregables a un formato consistente
const entregablesData = computed(() => {
  if (Array.isArray(props.entregables)) {
    // Si es un array directo, crear estructura paginada falsa
    return {
      data: props.entregables,
      current_page: 1,
      last_page: 1,
      per_page: props.entregables.length,
      total: props.entregables.length,
      from: 1,
      to: props.entregables.length,
    };
  }
  return props.entregables;
});

// Estado local
const searchTerm = ref(props.filters?.search || '');
const selectedEstado = ref(props.filters?.estado || '');
const selectedPrioridad = ref(props.filters?.prioridad || '');
const selectedResponsable = ref(props.filters?.responsable_id?.toString() || '');
const selectedEntregables = ref<number[]>([]);
const showFilters = ref(false);

// Breadcrumbs
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Proyectos', href: '/admin/proyectos' },
  { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
  { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
  { title: props.hito.nombre, href: `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}` },
  { title: 'Entregables' },
]);

// Búsqueda con debounce
const performSearch = debounce(() => {
  router.get(
    `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables`,
    {
      search: searchTerm.value,
      estado: selectedEstado.value,
      prioridad: selectedPrioridad.value,
      responsable_id: selectedResponsable.value,
      page: 1,
    },
    { preserveState: true, preserveScroll: true }
  );
}, 300);

// Watchers
watch([searchTerm, selectedEstado, selectedPrioridad, selectedResponsable], () => {
  performSearch();
});

// Métodos
const clearFilters = () => {
  searchTerm.value = '';
  selectedEstado.value = '';
  selectedPrioridad.value = '';
  selectedResponsable.value = '';
};

const toggleSelectAll = () => {
  if (selectedEntregables.value.length === entregablesData.value.data.length) {
    selectedEntregables.value = [];
  } else {
    selectedEntregables.value = entregablesData.value.data.map(e => e.id);
  }
};

const isSelected = (id: number) => {
  return selectedEntregables.value.includes(id);
};

const toggleSelection = (id: number) => {
  const index = selectedEntregables.value.indexOf(id);
  if (index > -1) {
    selectedEntregables.value.splice(index, 1);
  } else {
    selectedEntregables.value.push(id);
  }
};

const marcarComoCompletado = (entregable: Entregable) => {
  if (!props.canComplete) return;

  router.post(
    `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}/completar`,
    {
      notas_completado: `Completado por ${usePage().props.auth.user.name}`,
    },
    {
      onSuccess: () => {
        toast.success('Entregable marcado como completado');
      },
      onError: () => {
        toast.error('Error al marcar el entregable como completado');
      },
    }
  );
};

const eliminarEntregable = (entregable: Entregable) => {
  if (!props.canDelete) return;

  if (confirm(`¿Estás seguro de eliminar el entregable "${entregable.nombre}"?`)) {
    router.delete(
      `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables/${entregable.id}`,
      {
        onSuccess: () => {
          toast.success('Entregable eliminado correctamente');
        },
        onError: () => {
          toast.error('Error al eliminar el entregable');
        },
      }
    );
  }
};

const getEstadoBadgeVariant = (estado: EstadoEntregable) => {
  const variants = {
    pendiente: 'secondary',
    en_progreso: 'default',
    completado: 'success',
    cancelado: 'destructive',
  };
  return variants[estado] || 'secondary';
};

const getPrioridadBadgeVariant = (prioridad: PrioridadEntregable) => {
  const variants = {
    baja: 'outline',
    media: 'default',
    alta: 'destructive',
  };
  return variants[prioridad] || 'outline';
};

const getPrioridadIcon = (prioridad: PrioridadEntregable) => {
  if (prioridad === 'alta') return ChevronUp;
  if (prioridad === 'baja') return ChevronDown;
  return CircleDot;
};

const formatDate = (date: string | null) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

const getDiasRestantes = (fecha: string | null) => {
  if (!fecha) return null;
  const hoy = new Date();
  const fechaFin = new Date(fecha);
  const diff = fechaFin.getTime() - hoy.getTime();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

// Computed
const entregablesConInfo = computed(() => {
  return entregablesData.value.data.map(entregable => ({
    ...entregable,
    diasRestantes: getDiasRestantes(entregable.fecha_fin),
    estaVencido: entregable.fecha_fin && getDiasRestantes(entregable.fecha_fin) < 0,
    estaProximoVencer: entregable.fecha_fin && getDiasRestantes(entregable.fecha_fin) >= 0 && getDiasRestantes(entregable.fecha_fin) <= 7,
  }));
});

const estadisticasGenerales = computed(() => {
  const data = entregablesData.value.data;
  const total = entregablesData.value.total;
  const completados = data.filter(e => e.estado === 'completado').length;
  const enProgreso = data.filter(e => e.estado === 'en_progreso').length;
  const pendientes = data.filter(e => e.estado === 'pendiente').length;

  return {
    total,
    completados,
    enProgreso,
    pendientes,
    progreso: total > 0 ? Math.round((completados / total) * 100) : 0,
  };
});
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header con acciones -->
      <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Entregables del Hito</h2>
        <p class="text-muted-foreground mt-2">{{ hito.nombre }} - {{ proyecto.nombre }}</p>
      </div>
      <div class="flex gap-2">
        <Link
          :href="`/admin/proyectos/${proyecto.id}/hitos`"
          as="button"
        >
          <Button variant="outline">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Volver a Hitos
          </Button>
        </Link>
        <Link
          v-if="canCreate"
          :href="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables/create`"
          as="button"
        >
          <Button>
            <Plus class="mr-2 h-4 w-4" />
            Nuevo Entregable
          </Button>
        </Link>
      </div>
    </div>

    <!-- Estadísticas generales -->
    <div class="grid gap-4 md:grid-cols-4">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Entregables</CardTitle>
          <Target class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ estadisticasGenerales.total }}</div>
          <Progress :value="estadisticasGenerales.progreso" class="mt-2" />
          <p class="text-xs text-muted-foreground mt-2">
            {{ estadisticasGenerales.progreso }}% completado
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Completados</CardTitle>
          <CheckCircle2 class="h-4 w-4 text-green-600" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ estadisticasGenerales.completados }}</div>
          <p class="text-xs text-muted-foreground">
            de {{ estadisticasGenerales.total }} entregables
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">En Progreso</CardTitle>
          <Clock class="h-4 w-4 text-blue-600" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ estadisticasGenerales.enProgreso }}</div>
          <p class="text-xs text-muted-foreground">
            actualmente activos
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Pendientes</CardTitle>
          <AlertCircle class="h-4 w-4 text-orange-600" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ estadisticasGenerales.pendientes }}</div>
          <p class="text-xs text-muted-foreground">
            por iniciar
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Filtros -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Entregables</CardTitle>
          <Button variant="outline" size="sm" @click="showFilters = !showFilters">
            <Filter class="mr-2 h-4 w-4" />
            {{ showFilters ? 'Ocultar' : 'Mostrar' }} Filtros
          </Button>
        </div>
      </CardHeader>
      <CardContent v-if="showFilters" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-4">
          <div>
            <label class="text-sm font-medium">Buscar</label>
            <div class="relative">
              <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                v-model="searchTerm"
                placeholder="Buscar entregable..."
                class="pl-8"
              />
            </div>
          </div>

          <div>
            <label class="text-sm font-medium">Estado</label>
            <Select v-model="selectedEstado">
              <SelectTrigger>
                <SelectValue placeholder="Todos los estados" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">Todos los estados</SelectItem>
                <SelectItem v-for="estado in estados" :key="estado.value" :value="estado.value">
                  {{ estado.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <label class="text-sm font-medium">Prioridad</label>
            <Select v-model="selectedPrioridad">
              <SelectTrigger>
                <SelectValue placeholder="Todas las prioridades" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">Todas las prioridades</SelectItem>
                <SelectItem v-for="prioridad in prioridades" :key="prioridad.value" :value="prioridad.value">
                  {{ prioridad.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <label class="text-sm font-medium">&nbsp;</label>
            <Button @click="clearFilters" variant="outline" class="w-full">
              Limpiar Filtros
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Tabla de entregables -->
    <Card>
      <CardContent class="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[40px]">
                <Checkbox
                  :checked="selectedEntregables.length === entregablesData.data.length && entregablesData.data.length > 0"
                  @update:checked="toggleSelectAll"
                />
              </TableHead>
              <TableHead>Entregable</TableHead>
              <TableHead>Responsable</TableHead>
              <TableHead>Estado</TableHead>
              <TableHead>Prioridad</TableHead>
              <TableHead>Fecha Fin</TableHead>
              <TableHead>Colaboradores</TableHead>
              <TableHead class="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="entregable in entregablesConInfo" :key="entregable.id">
              <TableCell>
                <Checkbox
                  :checked="isSelected(entregable.id)"
                  @update:checked="() => toggleSelection(entregable.id)"
                />
              </TableCell>
              <TableCell>
                <div>
                  <p class="font-medium">{{ entregable.nombre }}</p>
                  <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
                    {{ entregable.descripcion }}
                  </p>
                  <div v-if="entregable.estaVencido" class="flex items-center gap-1 mt-1">
                    <AlertCircle class="h-3 w-3 text-red-600" />
                    <span class="text-xs text-red-600">Vencido hace {{ Math.abs(entregable.diasRestantes) }} días</span>
                  </div>
                  <div v-else-if="entregable.estaProximoVencer" class="flex items-center gap-1 mt-1">
                    <AlertCircle class="h-3 w-3 text-orange-600" />
                    <span class="text-xs text-orange-600">Vence en {{ entregable.diasRestantes }} días</span>
                  </div>
                </div>
              </TableCell>
              <TableCell>
                <div v-if="entregable.responsable" class="flex items-center gap-2">
                  <Avatar class="h-8 w-8">
                    <AvatarImage v-if="entregable.responsable.avatar" :src="entregable.responsable.avatar" />
                    <AvatarFallback>{{ entregable.responsable.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                  </Avatar>
                  <span class="text-sm">{{ entregable.responsable.name }}</span>
                </div>
                <span v-else class="text-sm text-muted-foreground">Sin asignar</span>
              </TableCell>
              <TableCell>
                <Badge :variant="getEstadoBadgeVariant(entregable.estado)">
                  {{ entregable.estado_label }}
                </Badge>
              </TableCell>
              <TableCell>
                <Badge :variant="getPrioridadBadgeVariant(entregable.prioridad)">
                  <component :is="getPrioridadIcon(entregable.prioridad)" class="mr-1 h-3 w-3" />
                  {{ entregable.prioridad_label }}
                </Badge>
              </TableCell>
              <TableCell>
                <div class="flex items-center gap-1">
                  <Calendar class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm">{{ formatDate(entregable.fecha_fin) }}</span>
                </div>
              </TableCell>
              <TableCell>
                <div v-if="entregable.usuarios && entregable.usuarios.length > 0" class="flex items-center gap-1">
                  <Users class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm">{{ entregable.usuarios.length }}</span>
                </div>
                <span v-else class="text-sm text-muted-foreground">-</span>
              </TableCell>
              <TableCell class="text-right">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" class="h-8 w-8 p-0">
                      <span class="sr-only">Abrir menú</span>
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                    <DropdownMenuSeparator />

                    <Link
                      v-if="canEdit"
                      :href="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}/edit`"
                      as="button"
                      class="w-full"
                    >
                      <DropdownMenuItem>
                        <Edit class="mr-2 h-4 w-4" />
                        Editar
                      </DropdownMenuItem>
                    </Link>

                    <DropdownMenuItem
                      v-if="canComplete && entregable.estado !== 'completado'"
                      @click="marcarComoCompletado(entregable)"
                    >
                      <CheckCircle2 class="mr-2 h-4 w-4" />
                      Marcar como completado
                    </DropdownMenuItem>

                    <DropdownMenuSeparator v-if="canDelete" />

                    <DropdownMenuItem
                      v-if="canDelete"
                      @click="eliminarEntregable(entregable)"
                      class="text-red-600"
                    >
                      <Trash2 class="mr-2 h-4 w-4" />
                      Eliminar
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </TableCell>
            </TableRow>

            <TableRow v-if="entregablesData.data.length === 0">
              <TableCell colspan="8" class="text-center py-8">
                <p class="text-muted-foreground">No se encontraron entregables</p>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

    <!-- Paginación -->
    <div v-if="entregablesData.last_page > 1" class="flex items-center justify-between">
      <div class="text-sm text-muted-foreground">
        Mostrando {{ entregablesData.from }} a {{ entregablesData.to }} de {{ entregablesData.total }} entregables
      </div>
      <div class="flex gap-2">
        <Button
          v-for="page in entregablesData.last_page"
          :key="page"
          variant="outline"
          size="sm"
          :disabled="page === entregablesData.current_page"
          @click="router.get(`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables?page=${page}`)"
        >
          {{ page }}
        </Button>
      </div>
    </div>
    </div>
  </AdminLayout>
</template>