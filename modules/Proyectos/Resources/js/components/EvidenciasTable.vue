<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@modules/Core/Resources/js/components/ui/table';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@modules/Core/Resources/js/components/ui/accordion';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@modules/Core/Resources/js/components/ui/select';
import { FileText, ExternalLink, Download, Image, Eye } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

// Interfaces
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
  obligacion_titulo?: string; // Para modo agrupado
  entregables?: Array<{
    id: number;
    nombre: string;
  }>; // Para modo simple
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

interface GrupoContrato {
  contrato: {
    id: number;
    nombre: string;
  };
  evidencias: Evidencia[];
}

interface Contrato {
  id: number;
  nombre: string;
}

// Props
interface Props {
  /** Modo de visualización: 'grouped' para agrupar por contrato, 'simple' para tabla plana */
  mode: 'grouped' | 'simple';
  /** Evidencias filtradas (requerido para modo simple) */
  evidencias?: Evidencia[];
  /** Evidencias agrupadas por contrato (requerido para modo grouped) */
  evidenciasAgrupadas?: GrupoContrato[];
  /** Contrato asociado (requerido para modo simple) */
  contrato?: Contrato;
  /** Función para formatear fechas */
  formatDate: (date: string) => string;
  /** Título del card (opcional, solo para modo simple) */
  cardTitle?: string;
  /** Descripción del card (opcional, solo para modo simple) */
  cardDescription?: string;
  /** Modo de operación: 'admin' o 'user' */
  modo?: 'admin' | 'user';
  /** Si el usuario puede gestionar el estado de evidencias */
  puedeGestionarEstado?: boolean;
  /** ID del proyecto (requerido para cambio de estado) */
  proyectoId?: number;
}

const props = withDefaults(defineProps<Props>(), {
  cardTitle: 'Evidencias Asociadas',
  cardDescription: 'Evidencias cargadas para esta obligación',
  modo: 'admin',
  puedeGestionarEstado: false
});

// Computed para obtener clases de estado
const getEstadoBadgeClasses = (estado: string) => {
  return {
    'bg-yellow-100 text-yellow-800': estado === 'pendiente',
    'bg-green-100 text-green-800': estado === 'aprobada',
    'bg-red-100 text-red-800': estado === 'rechazada'
  };
};

// Computed para determinar si hay evidencias
const hayEvidencias = computed(() => {
  if (props.mode === 'grouped') {
    return props.evidenciasAgrupadas && props.evidenciasAgrupadas.length > 0;
  }
  return props.evidencias && props.evidencias.length > 0;
});

// Función para cambiar estado directamente desde dropdown
const cambiarEstadoDirecto = (evidencia: Evidencia, nuevoEstado: string) => {
  if (nuevoEstado === evidencia.estado || !props.proyectoId) return;

  // Determinar el endpoint según el modo
  const endpoint = props.modo === 'admin'
    ? `/admin/proyectos/${props.proyectoId}/evidencias/${evidencia.id}/cambiar-estado`
    : `/miembro/mis-proyectos/${props.proyectoId}/evidencias/${evidencia.id}/cambiar-estado`;

  router.post(endpoint, {
    estado: nuevoEstado,
    observaciones: null
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // El mensaje flash viene desde el backend
      router.reload();
    },
    onError: (errors) => {
      toast.error('Error al cambiar el estado');
      console.error(errors);
    }
  });
};
</script>

<template>
  <!-- Modo Agrupado por Contrato (Accordion) -->
  <div v-if="mode === 'grouped' && hayEvidencias">
    <Accordion type="multiple" class="space-y-4" collapsible>
      <AccordionItem
        v-for="grupo in evidenciasAgrupadas"
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
                class="hover:bg-gray-50 dark:hover:bg-gray-800"
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
                  <Badge :class="getEstadoBadgeClasses(evidencia.estado)">
                    {{ evidencia.estado }}
                  </Badge>
                </TableCell>
                <TableCell>{{ formatDate(evidencia.created_at) }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Botón de ver detalles -->
                    <Button variant="ghost" size="sm" as-child class="h-8 px-2">
                      <Link :href="`/admin/contratos/${grupo.contrato.id}/evidencias/${evidencia.id}`">
                        <Eye class="h-4 w-4" />
                        <span class="ml-1">Ver</span>
                      </Link>
                    </Button>
                    <!-- Botón de descargar archivo -->
                    <Button
                      v-if="evidencia.archivo_url"
                      variant="ghost"
                      size="sm"
                      as-child
                      class="h-8 px-2"
                    >
                      <a :href="evidencia.archivo_url" target="_blank">
                        <Download class="h-4 w-4" />
                        <span class="ml-1">Descargar</span>
                      </a>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  </div>

  <!-- Modo Simple (Tabla plana) -->
  <div v-else-if="mode === 'simple' && hayEvidencias">
    <Card>
      <CardHeader>
        <CardTitle>{{ cardTitle }}</CardTitle>
        <CardDescription>
          {{ cardDescription }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tipo</TableHead>
              <TableHead>Descripción</TableHead>
              <TableHead>Entregable</TableHead>
              <TableHead>Usuario</TableHead>
              <TableHead>Estado</TableHead>
              <TableHead>Fecha</TableHead>
              <TableHead class="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="evidencia in evidencias"
              :key="evidencia.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <TableCell>
                <Badge variant="outline">{{ evidencia.tipo_evidencia }}</Badge>
              </TableCell>
              <TableCell>
                <span class="text-sm text-gray-600">{{ evidencia.descripcion || '-' }}</span>
              </TableCell>
              <TableCell>
                <div v-if="evidencia.entregables && evidencia.entregables.length > 0" class="flex flex-wrap gap-1">
                  <Badge
                    v-for="entregable in evidencia.entregables"
                    :key="entregable.id"
                    variant="secondary"
                    class="text-xs"
                  >
                    {{ entregable.nombre }}
                  </Badge>
                </div>
                <span v-else class="text-sm text-gray-500">-</span>
              </TableCell>
              <TableCell>
                <span class="text-sm">{{ evidencia.usuario?.name || '-' }}</span>
              </TableCell>
              <TableCell>
                <Badge :class="getEstadoBadgeClasses(evidencia.estado)">
                  {{ evidencia.estado }}
                </Badge>
              </TableCell>
              <TableCell>{{ formatDate(evidencia.created_at) }}</TableCell>
              <TableCell class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <!-- Botón de ver detalles -->
                  <Button variant="ghost" size="sm" as-child class="h-8 px-2">
                    <Link :href="`/admin/contratos/${contrato?.id}/evidencias/${evidencia.id}`">
                      <Eye class="h-4 w-4" />
                      <span class="ml-1">Ver</span>
                    </Link>
                  </Button>
                  <!-- Botón de descargar archivo -->
                  <Button
                    v-if="evidencia.archivo_url"
                    variant="ghost"
                    size="sm"
                    as-child
                    class="h-8 px-2"
                  >
                    <a :href="evidencia.archivo_url" target="_blank">
                      <Download class="h-4 w-4" />
                      <span class="ml-1">Descargar</span>
                    </a>
                  </Button>
                  <!-- Dropdown de estados para Admin y User gestores -->
                  <template v-if="(modo === 'admin' && proyectoId) || (modo === 'user' && puedeGestionarEstado && proyectoId)">
                    <Select
                      :model-value="evidencia.estado"
                      @update:model-value="(value) => cambiarEstadoDirecto(evidencia, value)"
                    >
                      <SelectTrigger class="h-8 w-32">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="pendiente">
                          <span class="text-yellow-600">Pendiente</span>
                        </SelectItem>
                        <SelectItem value="aprobada">
                          <span class="text-green-600">Aprobada</span>
                        </SelectItem>
                        <SelectItem value="rechazada">
                          <span class="text-red-600">Rechazada</span>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </template>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>

  <!-- Estado vacío (sin evidencias en el source original) -->
  <Card v-else-if="!hayEvidencias">
    <CardContent class="py-8">
      <div class="text-center">
        <Image class="mx-auto h-12 w-12 text-gray-400" />
        <p class="mt-2 text-sm text-gray-600">
          {{ mode === 'grouped' ? 'No hay evidencias que coincidan con los filtros' : 'No hay evidencias asociadas' }}
        </p>
        <p class="text-xs text-gray-500 mt-1">
          {{ mode === 'grouped' ? 'Intenta ajustar los criterios de búsqueda' : 'Las evidencias se cargan desde los contratos' }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
