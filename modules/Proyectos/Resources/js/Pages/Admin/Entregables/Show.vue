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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@modules/Core/Resources/js/components/ui/accordion';
import EvidenciaFilters from '@modules/Proyectos/Resources/js/components/EvidenciaFilters.vue';
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
  Activity,
  Image,
  ExternalLink,
  Download,
  Tag
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

interface Evidencia {
  id: number;
  tipo_evidencia: string;
  descripcion?: string;
  archivo_url?: string;
  estado: string;
  created_at: string;
  usuario?: Usuario;
  obligacion?: {
    id: number;
    titulo: string;
    contrato_id: number;
    contrato?: {
      id: number;
      nombre: string;
    };
  };
}

interface CampoPersonalizado {
  id: number;
  nombre: string;
  tipo: string;
  es_requerido: boolean;
  descripcion?: string;
  opciones?: any[];
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
    evidencias?: Evidencia[];
  };
  usuariosAsignados?: UsuarioAsignado[];
  actividades?: Actividad[];
  camposPersonalizados?: CampoPersonalizado[];
  valoresCamposPersonalizados?: Record<number, any>;
  canEdit: boolean;
  canDelete: boolean;
}

const props = defineProps<Props>();

// Valores por defecto para arrays
const usuariosAsignados = computed(() => props.usuariosAsignados || []);
const actividades = computed(() => props.actividades || []);

// Estado para el tab activo
const activeTab = ref('detalles');

// Estado para filtros de evidencias
const filtrosEvidencias = ref({
  contrato_id: null as number | null,
  fecha_inicio: null as string | null,
  fecha_fin: null as string | null,
  tipo: null as string | null,
  estado: null as string | null,
  usuario_id: null as number | null
});

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

// Formatear valor de campo personalizado según su tipo
const formatCampoPersonalizado = (campo: CampoPersonalizado, valor: any) => {
  if (valor === null || valor === undefined || valor === '') {
    return 'No especificado';
  }

  switch (campo.tipo) {
    case 'date':
      return formatDate(valor);
    case 'checkbox':
      return valor ? 'Sí' : 'No';
    case 'select':
    case 'radio':
      // Buscar la etiqueta en las opciones
      const opcion = campo.opciones?.find((opt: any) => opt.value === valor);
      return opcion?.label || valor;
    default:
      return valor;
  }
};

// Computed para evidencias
const evidenciasDelEntregable = computed(() => {
  return props.entregable.evidencias || [];
});

// Extraer contratos únicos de las evidencias
const contratosRelacionados = computed(() => {
  const contratosMap = new Map();
  evidenciasDelEntregable.value.forEach(evidencia => {
    if (evidencia.obligacion?.contrato) {
      const contrato = evidencia.obligacion.contrato;
      if (!contratosMap.has(contrato.id)) {
        contratosMap.set(contrato.id, {
          id: contrato.id,
          nombre: contrato.nombre
        });
      }
    }
  });
  return Array.from(contratosMap.values());
});

// Evidencias filtradas
const evidenciasFiltradas = computed(() => {
  let result = evidenciasDelEntregable.value;

  // Filtrar por contrato
  if (filtrosEvidencias.value.contrato_id) {
    result = result.filter(e => e.obligacion?.contrato_id === filtrosEvidencias.value.contrato_id);
  }

  // Filtrar por tipo
  if (filtrosEvidencias.value.tipo) {
    result = result.filter(e => e.tipo_evidencia === filtrosEvidencias.value.tipo);
  }

  // Filtrar por estado
  if (filtrosEvidencias.value.estado) {
    result = result.filter(e => e.estado === filtrosEvidencias.value.estado);
  }

  // Filtrar por usuario
  if (filtrosEvidencias.value.usuario_id) {
    result = result.filter(e => e.usuario?.id === filtrosEvidencias.value.usuario_id);
  }

  // Filtrar por rango de fechas
  if (filtrosEvidencias.value.fecha_inicio || filtrosEvidencias.value.fecha_fin) {
    result = result.filter(e => {
      const fecha = new Date(e.created_at);
      if (filtrosEvidencias.value.fecha_inicio) {
        const fechaInicio = new Date(filtrosEvidencias.value.fecha_inicio);
        if (fecha < fechaInicio) return false;
      }
      if (filtrosEvidencias.value.fecha_fin) {
        const fechaFin = new Date(filtrosEvidencias.value.fecha_fin);
        fechaFin.setHours(23, 59, 59, 999);
        if (fecha > fechaFin) return false;
      }
      return true;
    });
  }

  return result;
});

// Evidencias agrupadas por contrato (para el Accordion)
const evidenciasAgrupadasPorContrato = computed(() => {
  const grupos: Record<number, any> = {};

  evidenciasFiltradas.value.forEach(evidencia => {
    if (evidencia.obligacion?.contrato) {
      const contratoId = evidencia.obligacion.contrato.id;
      if (!grupos[contratoId]) {
        grupos[contratoId] = {
          contrato: evidencia.obligacion.contrato,
          evidencias: []
        };
      }
      grupos[contratoId].evidencias.push({
        ...evidencia,
        obligacion_titulo: evidencia.obligacion.titulo
      });
    }
  });

  return Object.values(grupos);
});

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
          <TabsTrigger value="evidencias">
            <Image class="h-4 w-4 mr-2" />
            Evidencias
            <Badge v-if="evidenciasDelEntregable.length > 0" class="ml-2 h-5 px-1.5" variant="secondary">
              {{ evidenciasDelEntregable.length }}
            </Badge>
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
                <h4 class="text-sm font-medium text-muted-foreground mb-4">Metadatos</h4>
                <div class="space-y-3 text-sm">
                  <div>
                    <span class="text-muted-foreground text-xs font-medium block mb-1">Creado</span>
                    <span class="block">{{ formatDateTime(entregable.created_at) }}</span>
                  </div>
                  <div>
                    <span class="text-muted-foreground text-xs font-medium block mb-1">Última actualización</span>
                    <span class="block">{{ formatDateTime(entregable.updated_at) }}</span>
                  </div>
                  <div>
                    <span class="text-muted-foreground text-xs font-medium block mb-1">Orden de visualización</span>
                    <span class="block">{{ entregable.orden }}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Campos Personalizados -->
          <Card v-if="camposPersonalizados && camposPersonalizados.length > 0">
            <CardHeader>
              <CardTitle>Campos Personalizados</CardTitle>
              <CardDescription>
                Información adicional específica del entregable
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid gap-4 md:grid-cols-2">
                <div v-for="campo in camposPersonalizados" :key="campo.id">
                  <h4 class="text-sm font-medium text-muted-foreground mb-1">
                    {{ campo.nombre }}
                    <span v-if="campo.es_requerido" class="text-red-500">*</span>
                  </h4>
                  <p class="text-sm">
                    {{ formatCampoPersonalizado(campo, valoresCamposPersonalizados?.[campo.id]) }}
                  </p>
                  <p v-if="campo.descripcion" class="text-xs text-muted-foreground mt-1">
                    {{ campo.descripcion }}
                  </p>
                </div>
              </div>
              <div v-if="!camposPersonalizados || camposPersonalizados.length === 0" class="text-center py-4">
                <p class="text-sm text-muted-foreground">No hay campos personalizados configurados</p>
              </div>
            </CardContent>
          </Card>

          <!-- Etiquetas -->
          <Card v-if="entregable.etiquetas && entregable.etiquetas.length > 0">
            <CardHeader>
              <div class="flex items-center gap-2">
                <Tag class="h-5 w-5" />
                <CardTitle>Etiquetas</CardTitle>
              </div>
              <CardDescription>
                Etiquetas asignadas a este entregable para su organización y categorización
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="etiqueta in entregable.etiquetas"
                  :key="etiqueta.id"
                  variant="outline"
                  class="px-3 py-1.5"
                  :style="{
                    borderColor: etiqueta.color || '#94a3b8',
                    color: etiqueta.color || '#64748b',
                    backgroundColor: `${etiqueta.color}15` || '#f1f5f9'
                  }"
                >
                  {{ etiqueta.nombre }}
                </Badge>
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

        <!-- Tab Evidencias -->
        <TabsContent value="evidencias" class="space-y-4 mt-6">
          <!-- Filtros de evidencias -->
          <EvidenciaFilters
            v-if="evidenciasDelEntregable.length > 0"
            v-model="filtrosEvidencias"
            :contratos="contratosRelacionados"
            :evidencias="evidenciasDelEntregable"
          />

          <!-- Evidencias agrupadas por contrato -->
          <div v-if="evidenciasAgrupadasPorContrato.length > 0">
            <Accordion type="multiple" class="space-y-4" collapsible>
              <AccordionItem
                v-for="grupo in evidenciasAgrupadasPorContrato"
                :key="grupo.contrato.id"
                :value="`contrato-${grupo.contrato.id}`"
                class="border rounded-lg bg-card"
              >
                <AccordionTrigger class="px-4 py-3 hover:no-underline hover:bg-gray-50 dark:hover:bg-gray-800 rounded-t-lg">
                  <div class="flex items-center justify-between w-full pr-4">
                    <div class="flex items-center gap-3">
                      <FileText class="h-5 w-5 text-gray-500" />
                      <div class="text-left">
                        <div class="font-semibold">
                          {{ grupo.contrato.nombre }}
                        </div>
                        <div class="text-sm text-gray-500">
                          Contrato #{{ grupo.contrato.id }}
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center gap-3">
                      <Badge variant="secondary">
                        {{ grupo.evidencias.length }} evidencia(s)
                      </Badge>
                      <Link
                        :href="`/admin/contratos/${grupo.contrato.id}`"
                        @click.stop
                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                      >
                        Ver contrato
                        <ExternalLink class="h-3 w-3" />
                      </Link>
                    </div>
                  </div>
                </AccordionTrigger>
                <AccordionContent class="px-4 pb-4">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Obligación</TableHead>
                        <TableHead>Descripción</TableHead>
                        <TableHead>Usuario</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Fecha</TableHead>
                        <TableHead class="text-right">Acciones</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow
                        v-for="evidencia in grupo.evidencias"
                        :key="evidencia.id"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                      >
                        <Link
                          :href="`/admin/contratos/${grupo.contrato.id}/evidencias/${evidencia.id}`"
                          class="contents"
                        >
                          <TableCell>
                            <Badge variant="outline">{{ evidencia.tipo_evidencia }}</Badge>
                          </TableCell>
                          <TableCell>{{ evidencia.obligacion_titulo }}</TableCell>
                          <TableCell>
                            <span class="text-sm text-gray-600">{{ evidencia.descripcion || '-' }}</span>
                          </TableCell>
                          <TableCell>
                            <span class="text-sm">{{ evidencia.usuario?.name || '-' }}</span>
                          </TableCell>
                          <TableCell>
                            <Badge
                              :class="{
                                'bg-yellow-100 text-yellow-800': evidencia.estado === 'pendiente',
                                'bg-green-100 text-green-800': evidencia.estado === 'aprobada',
                                'bg-red-100 text-red-800': evidencia.estado === 'rechazada'
                              }"
                            >
                              {{ evidencia.estado }}
                            </Badge>
                          </TableCell>
                          <TableCell>{{ formatDate(evidencia.created_at) }}</TableCell>
                          <TableCell class="text-right">
                            <a
                              v-if="evidencia.archivo_url"
                              :href="evidencia.archivo_url"
                              target="_blank"
                              class="inline-flex items-center text-blue-600 hover:text-blue-800"
                              @click.stop
                            >
                              <Download class="h-4 w-4" />
                            </a>
                          </TableCell>
                        </Link>
                      </TableRow>
                    </TableBody>
                  </Table>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </div>

          <!-- Estado vacío -->
          <Card v-else-if="evidenciasDelEntregable.length === 0">
            <CardContent class="py-8">
              <div class="text-center">
                <Image class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600">No hay evidencias asociadas</p>
                <p class="text-xs text-gray-500 mt-1">Las evidencias se asocian desde los contratos</p>
              </div>
            </CardContent>
          </Card>

          <!-- Sin resultados después de filtrar -->
          <Card v-else>
            <CardContent class="py-8">
              <div class="text-center">
                <Image class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600">No hay evidencias que coincidan con los filtros</p>
                <p class="text-xs text-gray-500 mt-1">Intenta ajustar los criterios de búsqueda</p>
              </div>
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