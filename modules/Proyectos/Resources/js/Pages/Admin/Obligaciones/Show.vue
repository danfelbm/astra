<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import EvidenciaFilters from '@modules/Proyectos/Resources/js/components/EvidenciaFilters.vue';
import EvidenciasTable from '@modules/Proyectos/Resources/js/components/EvidenciasTable.vue';
import ObligacionTree from '@modules/Proyectos/Resources/js/components/ObligacionTree.vue';
import {
  Pencil,
  Paperclip,
  Info,
  ListTree,
  Image,
  ArrowLeft
} from 'lucide-vue-next';
import { usePermissions } from '@modules/Core/Resources/js/composables/usePermissions';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';

interface Evidencia {
  id: number;
  tipo_evidencia: string;
  descripcion?: string;
  archivo_url?: string;
  estado: string;
  created_at: string;
  usuario?: {
    id: number;
    name: string;
    email: string;
  };
}

interface Props {
  obligacion: any;
  contrato: any;
  canEdit: boolean;
  canDelete: boolean;
  canCreateChild: boolean;
}

const props = defineProps<Props>();

const { hasPermission } = usePermissions();

// Estado para el tab activo
const activeTab = ref('general');

// Estado para filtros de evidencias
const filtrosEvidencias = ref({
  contrato_id: null as number | null,
  fecha_inicio: null as string | null,
  fecha_fin: null as string | null,
  tipo: null as string | null,
  estado: null as string | null,
  usuario_id: null as number | null,
  entregable_id: null as number | null
});

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Obligaciones', href: '/admin/obligaciones' },
  { title: props.obligacion.titulo }
];

// Computed para evidencias
const evidenciasDeLaObligacion = computed(() => {
  return props.obligacion.evidencias || [];
});

// Evidencias filtradas
const evidenciasFiltradas = computed(() => {
  let result = evidenciasDeLaObligacion.value;

  // Filtrar por tipo
  if (filtrosEvidencias.value.tipo) {
    result = result.filter((e: Evidencia) => e.tipo_evidencia === filtrosEvidencias.value.tipo);
  }

  // Filtrar por estado
  if (filtrosEvidencias.value.estado) {
    result = result.filter((e: Evidencia) => e.estado === filtrosEvidencias.value.estado);
  }

  // Filtrar por usuario
  if (filtrosEvidencias.value.usuario_id) {
    result = result.filter((e: Evidencia) => e.usuario?.id === filtrosEvidencias.value.usuario_id);
  }

  // Filtrar por entregable
  if (filtrosEvidencias.value.entregable_id) {
    result = result.filter((e: Evidencia) => {
      return e.entregables?.some(ent => ent.id === filtrosEvidencias.value.entregable_id);
    });
  }

  // Filtrar por rango de fechas
  if (filtrosEvidencias.value.fecha_inicio || filtrosEvidencias.value.fecha_fin) {
    result = result.filter((e: Evidencia) => {
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

// Función para formatear fechas
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'Sin fecha';
  try {
    return format(parseISO(dateString), 'dd MMM yyyy HH:mm', { locale: es });
  } catch {
    return dateString;
  }
};

// Función para formatear fecha corta
const formatDateShort = (dateString: string | undefined) => {
  if (!dateString) return '-';
  try {
    return format(parseISO(dateString), "d 'de' MMMM 'de' yyyy", { locale: es });
  } catch {
    return dateString;
  }
};
</script>

<template>
  <Head :title="`Obligación - ${obligacion.titulo}`" />

  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">{{ obligacion.titulo }}</h1>
          <p class="text-muted-foreground mt-2">
            Contrato: {{ contrato.nombre }}
          </p>
        </div>

        <div class="flex gap-2">
          <Link :href="`/admin/contratos/${contrato.id}`">
            <Button variant="outline" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Ver Contrato
            </Button>
          </Link>
          <Link v-if="canEdit" :href="route('admin.obligaciones.edit', obligacion.id)">
            <Button variant="outline" size="sm">
              <Pencil class="h-4 w-4 mr-2" />
              Editar
            </Button>
          </Link>
        </div>
      </div>

      <!-- Tabs -->
      <Tabs v-model="activeTab" class="flex-1">
        <TabsList>
          <TabsTrigger value="general">
            <Info class="h-4 w-4 mr-2" />
            General
          </TabsTrigger>
          <TabsTrigger v-if="obligacion.hijos?.length" value="hijos">
            <ListTree class="h-4 w-4 mr-2" />
            Obligaciones Hijas
            <Badge v-if="obligacion.hijos?.length" class="ml-2 h-5 px-1.5" variant="secondary">
              {{ obligacion.hijos.length }}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="evidencias">
            <Image class="h-4 w-4 mr-2" />
            Evidencias
            <Badge v-if="evidenciasDeLaObligacion.length > 0" class="ml-2 h-5 px-1.5" variant="secondary">
              {{ evidenciasDeLaObligacion.length }}
            </Badge>
          </TabsTrigger>
        </TabsList>

        <!-- Tab General -->
        <TabsContent value="general" class="space-y-4 mt-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Información General</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div>
                    <Label>Descripción</Label>
                    <p class="mt-1">{{ obligacion.descripcion || 'Sin descripción' }}</p>
                  </div>
                </CardContent>
              </Card>
            </div>

            <div class="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Detalles del Contrato</CardTitle>
                </CardHeader>
                <CardContent>
                  <Link :href="route('admin.contratos.show', contrato.id)" class="hover:underline">
                    <h3 class="font-medium">{{ contrato.nombre }}</h3>
                  </Link>
                  <p class="text-sm text-gray-600 mt-1">{{ contrato.proyecto?.nombre }}</p>
                </CardContent>
              </Card>

              <Card v-if="obligacion.archivos_adjuntos?.length">
                <CardHeader>
                  <CardTitle>Archivos Adjuntos</CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-2">
                    <div v-for="archivo in obligacion.archivos_adjuntos" :key="archivo.ruta" class="flex items-center gap-2">
                      <Paperclip class="h-4 w-4" />
                      <a :href="archivo.ruta" target="_blank" class="hover:underline text-sm">
                        {{ archivo.nombre_original }}
                      </a>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Auditoría</CardTitle>
                </CardHeader>
                <CardContent class="text-sm space-y-2">
                  <div>
                    <span class="text-gray-600">Creado por:</span>
                    <p>{{ obligacion.creador?.name || 'Sistema' }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(obligacion.created_at) }}</p>
                  </div>
                  <div v-if="obligacion.updated_at">
                    <span class="text-gray-600">Última actualización:</span>
                    <p>{{ obligacion.actualizador?.name || 'Sistema' }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(obligacion.updated_at) }}</p>
                  </div>
                  <div v-if="obligacion.cumplido_at">
                    <span class="text-gray-600">Cumplido por:</span>
                    <p>{{ obligacion.cumplido_por_usuario?.name || 'Sistema' }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(obligacion.cumplido_at) }}</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        <!-- Tab Obligaciones Hijas -->
        <TabsContent v-if="obligacion.hijos?.length" value="hijos" class="space-y-4 mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Obligaciones Hijas</CardTitle>
              <CardDescription>
                Sub-obligaciones derivadas de esta obligación principal
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ObligacionTree
                :obligaciones="obligacion.hijos"
                :contratoId="obligacion.contrato_id"
                :editable="hasPermission('obligaciones.edit')"
                @edit="(o) => router.visit(route('admin.obligaciones.edit', o.id))"
              />
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Tab Evidencias -->
        <TabsContent value="evidencias" class="space-y-4 mt-6">
          <!-- Filtros de evidencias -->
          <EvidenciaFilters
            v-if="evidenciasDeLaObligacion.length > 0"
            v-model="filtrosEvidencias"
            :contratos="[contrato]"
            :evidencias="evidenciasDeLaObligacion"
          />

          <!-- Tabla de evidencias (componente reutilizable) -->
          <EvidenciasTable
            mode="simple"
            :evidencias="evidenciasFiltradas"
            :contrato="contrato"
            :format-date="formatDateShort"
            card-title="Evidencias Asociadas"
            card-description="Evidencias cargadas para esta obligación"
          />
        </TabsContent>
      </Tabs>
    </div>
  </AdminLayout>
</template>
