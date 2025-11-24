<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import ObligacionForm from '@modules/Proyectos/Resources/js/components/ObligacionForm.vue';
import { Layers, ArrowLeft } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import type { ObligacionFormData } from '@modules/Proyectos/Resources/js/types/obligaciones';

interface Props {
  contrato?: any;
  contratos?: any[];
  parent?: any;
  posiblesPadres?: any[];
  usuarios?: any[];
}

const props = defineProps<Props>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = props.contrato
  ? [
      { title: 'Admin', href: '/admin/dashboard' },
      { title: 'Contratos', href: '/admin/contratos' },
      { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
      { title: 'Nueva Obligación', href: '#' },
    ]
  : [
      { title: 'Admin', href: '/admin/dashboard' },
      { title: 'Obligaciones', href: '/admin/obligaciones' },
      { title: 'Nueva Obligación', href: '#' },
    ];

// Form
const form = useForm<ObligacionFormData>({
  contrato_id: props.contrato?.id || '',
  parent_id: props.parent?.id || null,
  titulo: '',
  descripcion: '',
  fecha_vencimiento: '',
  estado: 'pendiente',
  prioridad: 'media',
  responsable_id: undefined,
  orden: 1,
  archivos: [],
  archivos_eliminar: [],
  notas_cumplimiento: '',
  porcentaje_cumplimiento: 0
});

// Métodos
const handleSubmit = (data: ObligacionFormData) => {
  // Crear FormData para enviar archivos
  const formData = new FormData();

  // Agregar campos normales (excluyendo archivos y arrays de archivos)
  Object.keys(data).forEach(key => {
    if (key !== 'archivos' && key !== 'archivos_eliminar' && key !== 'archivos_adjuntos' && data[key as keyof ObligacionFormData] !== undefined && data[key as keyof ObligacionFormData] !== null) {
      const value = data[key as keyof ObligacionFormData];
      // Si es un objeto o array, convertir a JSON
      if (typeof value === 'object') {
        formData.append(key, JSON.stringify(value));
      } else {
        formData.append(key, String(value));
      }
    }
  });

  // Agregar archivos
  if (data.archivos && data.archivos.length > 0) {
    data.archivos.forEach((archivo, index) => {
      formData.append(`archivos[${index}]`, archivo);
    });
  }

  // Enviar formulario
  router.post(route('admin.obligaciones.store'), formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Obligación creada exitosamente');

      // Redirigir según el contexto
      if (props.contrato) {
        router.visit(route('admin.contratos.show', props.contrato.id));
      } else {
        router.visit(route('admin.obligaciones.index'));
      }
    },
    onError: (errors) => {
      console.error('Errores al crear:', errors);
      toast.error('Error al crear la obligación. Revisa los campos marcados.');
    }
  });
};

const handleCancel = () => {
  if (props.contrato) {
    router.visit(route('admin.contratos.show', props.contrato.id));
  } else {
    router.visit(route('admin.obligaciones.index'));
  }
};

// Utilidades
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'Sin fecha';
  try {
    return format(parseISO(dateString), 'dd MMM yyyy', { locale: es });
  } catch {
    return dateString;
  }
};

const getEstadoVariant = (estado: string) => {
  const variants: Record<string, any> = {
    'borrador': 'secondary',
    'activo': 'success',
    'finalizado': 'default',
    'cancelado': 'destructive',
    'pendiente': 'secondary',
    'en_progreso': 'default',
    'cumplida': 'success',
    'vencida': 'destructive',
    'cancelada': 'outline'
  };
  return variants[estado] || 'secondary';
};
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link
            :href="contrato ? route('admin.contratos.show', contrato.id) : route('admin.obligaciones.index')"
          >
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Volver
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold">Nueva Obligación</h1>
            <p class="text-gray-600 mt-1">
              {{ contrato ? `Para el contrato: ${contrato.nombre}` : 'Complete la información para crear una obligación' }}
            </p>
          </div>
        </div>
      </div>

      <div class="max-w-4xl">
        <!-- Información del contrato -->
        <Card v-if="contrato" class="mb-6">
          <CardContent class="pt-6">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-medium text-lg">{{ contrato.nombre }}</h3>
                <p class="text-sm text-gray-600">
                  Proyecto: {{ contrato.proyecto?.nombre }}
                </p>
              </div>
              <Badge :variant="getEstadoVariant(contrato.estado)">
                {{ contrato.estado_label || contrato.estado }}
              </Badge>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Fecha inicio:</span>
                <span class="ml-2 font-medium">{{ formatDate(contrato.fecha_inicio) }}</span>
              </div>
              <div>
                <span class="text-gray-600">Fecha fin:</span>
                <span class="ml-2 font-medium">{{ formatDate(contrato.fecha_fin) }}</span>
              </div>
              <div>
                <span class="text-gray-600">Obligaciones:</span>
                <span class="ml-2 font-medium">{{ contrato.total_obligaciones || 0 }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Información de la obligación padre (si aplica) -->
        <Card v-if="parent" class="mb-6">
          <CardContent class="pt-6">
            <div class="flex items-center gap-2 mb-2">
              <Layers class="h-4 w-4 text-gray-500" />
              <span class="text-sm text-gray-600">Creando obligación hija de:</span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <h4 class="font-medium">{{ parent.titulo }}</h4>
              <p v-if="parent.descripcion" class="text-sm text-gray-600 mt-1">
                {{ parent.descripcion }}
              </p>
              <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                <Badge :variant="getEstadoVariant(parent.estado)" size="sm">
                  {{ parent.estado_label || parent.estado }}
                </Badge>
                <span>Nivel: {{ (parent.nivel || 0) + 1 }}</span>
                <span v-if="parent.responsable">
                  Responsable: {{ parent.responsable.name }}
                </span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Formulario -->
        <ObligacionForm
          :contratoId="contrato?.id"
          :contratos="contratos"
          :parentId="parent?.id"
          :posiblesPadres="posiblesPadres"
          :usuarios="usuarios"
          :loading="form.processing"
          :errors="form.errors"
          @submit="handleSubmit"
          @cancel="handleCancel"
        />
      </div>
    </div>
  </AdminLayout>
</template>