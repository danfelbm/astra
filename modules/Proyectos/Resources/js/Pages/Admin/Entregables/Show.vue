<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Progress } from '@modules/Core/Resources/js/components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@modules/Core/Resources/js/components/ui/alert';
import {
  ArrowLeft,
  Edit,
  Trash2,
  Calendar,
  User,
  Flag,
  CheckCircle2,
  Clock,
  AlertCircle,
  FileText,
  Users,
  Target,
  Activity
} from 'lucide-vue-next';
import { format, parseISO, differenceInDays } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';

interface Usuario {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface Actividad {
  id: number;
  description: string;
  causer: Usuario;
  created_at: string;
  properties?: {
    attributes?: any;
    old?: any;
  };
}

interface UsuarioAsignado {
  user_id: number;
  user: Usuario;
  rol: 'colaborador' | 'revisor';
  created_at: string;
}

interface Props {
  proyecto: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  hito: {
    id: number;
    nombre: string;
    estado: string;
    porcentaje_completado: number;
  };
  entregable: {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    estado: 'pendiente' | 'en_progreso' | 'completado' | 'cancelado';
    prioridad: 'baja' | 'media' | 'alta';
    responsable_id?: number;
    responsable?: Usuario;
    porcentaje_completado: number;
    orden: number;
    notas?: string;
    created_at: string;
    updated_at: string;
  };
  usuariosAsignados?: UsuarioAsignado[];
  actividades?: Actividad[];
  canEdit: boolean;
  canDelete: boolean;
}

const props = defineProps<Props>();

// Valores por defecto para arrays
const usuariosAsignados = computed(() => props.usuariosAsignados || []);
const actividades = computed(() => props.actividades || []);

// Estado para el tab activo
const activeTab = ref('detalles');

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Proyectos', href: '/admin/proyectos' },
  { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
  { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
  { title: props.hito.nombre, href: `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}` },
  { title: 'Entregables', href: `/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}/entregables` },
  { title: props.entregable.nombre }
];

// Computed
const estadoBadgeVariant = computed(() => {
  const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pendiente: 'secondary',
    en_progreso: 'default',
    completado: 'outline',
    cancelado: 'destructive'
  };
  return variants[props.entregable.estado] || 'default';
});

const estadoLabel = computed(() => {
  const labels: Record<string, string> = {
    pendiente: 'Pendiente',
    en_progreso: 'En Progreso',
    completado: 'Completado',
    cancelado: 'Cancelado'
  };
  return labels[props.entregable.estado] || props.entregable.estado;
});

const prioridadColor = computed(() => {
  const colors: Record<string, string> = {
    baja: 'text-blue-500',
    media: 'text-yellow-500',
    alta: 'text-red-500'
  };
  return colors[props.entregable.prioridad] || '';
});

const diasRestantes = computed(() => {
  if (!props.entregable.fecha_fin) return null;
  const diff = differenceInDays(new Date(props.entregable.fecha_fin), new Date());
  return diff;
});

const estadoProgreso = computed(() => {
  if (props.entregable.estado === 'completado') return 'Completado';
  if (props.entregable.estado === 'cancelado') return 'Cancelado';
  if (diasRestantes.value !== null) {
    if (diasRestantes.value < 0) return 'Vencido';
    if (diasRestantes.value === 0) return 'Vence hoy';
    if (diasRestantes.value <= 3) return 'Por vencer';
  }
  return 'En curso';
});

const formatDate = (date: string | null | undefined) => {
  if (!date) return 'No definida';
  return format(parseISO(date), "d 'de' MMMM 'de' yyyy", { locale: es });
};

const formatDateTime = (date: string) => {
  return format(parseISO(date), "d MMM yyyy 'a las' HH:mm", { locale: es });
};

// Helper para obtener route
const { route } = window as any;
</script>

<template>
  <Head :title="`Entregable - ${entregable.nombre}`" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">{{ entregable.nombre }}</h1>
          <p class="text-muted-foreground mt-2">
            Entregable del hito "{{ hito.nombre }}"
          </p>
        </div>

        <div class="flex gap-2">
          <Link :href="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables`">
            <Button variant="outline" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Volver
            </Button>
          </Link>
          <Link v-if="canEdit" :href="`/admin/proyectos/${proyecto.id}/hitos/${hito.id}/entregables/${entregable.id}/edit`">
            <Button variant="outline" size="sm">
              <Edit class="h-4 w-4 mr-2" />
              Editar
            </Button>
          </Link>
        </div>
      </div>

      <!-- Estado y Progreso -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Estado</CardDescription>
          </CardHeader>
          <CardContent>
            <Badge :variant="estadoBadgeVariant" class="text-sm">
              {{ estadoLabel }}
            </Badge>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Prioridad</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-2">
              <Flag class="h-4 w-4" :class="prioridadColor" />
              <span class="font-medium capitalize">{{ entregable.prioridad }}</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Progreso</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-2xl font-bold">{{ entregable.porcentaje_completado }}%</span>
              </div>
              <Progress :model-value="entregable.porcentaje_completado" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Tiempo Restante</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="diasRestantes !== null" class="flex items-center gap-2">
              <Clock
                class="h-4 w-4"
                :class="{
                  'text-red-500': diasRestantes < 0,
                  'text-yellow-500': diasRestantes >= 0 && diasRestantes <= 3,
                  'text-green-500': diasRestantes > 3
                }"
              />
              <span class="font-medium">
                {{ diasRestantes < 0 ? `Vencido hace ${Math.abs(diasRestantes)} días` :
                   diasRestantes === 0 ? 'Vence hoy' :
                   `${diasRestantes} días` }}
              </span>
            </div>
            <span v-else class="text-muted-foreground">Sin fecha límite</span>
          </CardContent>
        </Card>
      </div>

      <!-- Tabs -->
      <Tabs v-model="activeTab" class="flex-1">
        <TabsList>
          <TabsTrigger value="detalles">
            <FileText class="h-4 w-4 mr-2" />
            Detalles
          </TabsTrigger>
          <TabsTrigger value="equipo">
            <Users class="h-4 w-4 mr-2" />
            Equipo
          </TabsTrigger>
          <TabsTrigger value="actividad">
            <Activity class="h-4 w-4 mr-2" />
            Actividad
          </TabsTrigger>
        </TabsList>

        <!-- Tab Detalles -->
        <TabsContent value="detalles" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Información General</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="entregable.descripcion">
                <h4 class="text-sm font-medium text-muted-foreground mb-2">Descripción</h4>
                <p class="text-sm">{{ entregable.descripcion }}</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <h4 class="text-sm font-medium text-muted-foreground mb-2">Fecha de Inicio</h4>
                  <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4" />
                    <span>{{ formatDate(entregable.fecha_inicio) }}</span>
                  </div>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-muted-foreground mb-2">Fecha de Fin</h4>
                  <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4" />
                    <span>{{ formatDate(entregable.fecha_fin) }}</span>
                  </div>
                </div>
              </div>

              <div v-if="entregable.notas">
                <h4 class="text-sm font-medium text-muted-foreground mb-2">Notas</h4>
                <Alert>
                  <AlertCircle class="h-4 w-4" />
                  <AlertDescription>
                    {{ entregable.notas }}
                  </AlertDescription>
                </Alert>
              </div>

              <div class="border-t pt-4">
                <h4 class="text-sm font-medium text-muted-foreground mb-2">Metadatos</h4>
                <div class="grid gap-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Creado:</span>
                    <span>{{ formatDateTime(entregable.created_at) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Última actualización:</span>
                    <span>{{ formatDateTime(entregable.updated_at) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Orden de visualización:</span>
                    <span>{{ entregable.orden }}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Tab Equipo -->
        <TabsContent value="equipo" class="space-y-4">
          <!-- Responsable Principal -->
          <Card>
            <CardHeader>
              <CardTitle>Responsable Principal</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="entregable.responsable" class="flex items-center gap-3">
                <Avatar class="h-10 w-10">
                  <AvatarImage v-if="entregable.responsable.avatar" :src="entregable.responsable.avatar" />
                  <AvatarFallback>{{ entregable.responsable.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-medium">{{ entregable.responsable.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ entregable.responsable.email }}</p>
                </div>
              </div>
              <p v-else class="text-muted-foreground">No hay responsable asignado</p>
            </CardContent>
          </Card>

          <!-- Colaboradores -->
          <Card>
            <CardHeader>
              <CardTitle>Colaboradores</CardTitle>
              <CardDescription>
                {{ usuariosAsignados.length }} usuarios asignados
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="usuariosAsignados.length > 0" class="space-y-3">
                <div
                  v-for="asignado in usuariosAsignados"
                  :key="asignado.user_id"
                  class="flex items-center justify-between p-3 bg-muted rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <Avatar class="h-8 w-8">
                      <AvatarImage v-if="asignado.user.avatar" :src="asignado.user.avatar" />
                      <AvatarFallback>{{ asignado.user.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <div>
                      <p class="font-medium">{{ asignado.user.name }}</p>
                      <p class="text-sm text-muted-foreground">{{ asignado.user.email }}</p>
                    </div>
                  </div>
                  <Badge variant="outline">
                    {{ asignado.rol }}
                  </Badge>
                </div>
              </div>
              <p v-else class="text-muted-foreground">No hay colaboradores asignados</p>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Tab Actividad -->
        <TabsContent value="actividad" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Historial de Actividad</CardTitle>
              <CardDescription>
                Registro de cambios y eventos del entregable
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="actividades.length > 0" class="space-y-4">
                <div
                  v-for="actividad in actividades"
                  :key="actividad.id"
                  class="flex gap-3 pb-4 border-b last:border-0"
                >
                  <Avatar class="h-8 w-8">
                    <AvatarImage v-if="actividad.causer?.avatar" :src="actividad.causer.avatar" />
                    <AvatarFallback>
                      {{ actividad.causer?.name?.substring(0, 2).toUpperCase() || 'SI' }}
                    </AvatarFallback>
                  </Avatar>
                  <div class="flex-1">
                    <p class="text-sm">
                      <span class="font-medium">{{ actividad.causer?.name || 'Sistema' }}</span>
                      {{ actividad.description }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">
                      {{ formatDateTime(actividad.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
              <p v-else class="text-muted-foreground">No hay actividad registrada</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AdminLayout>
</template>