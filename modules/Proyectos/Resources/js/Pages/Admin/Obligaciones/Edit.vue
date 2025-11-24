<script setup lang="ts">
import { useForm, router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AdminLayout from '@modules/Core/Resources/js/layouts/AdminLayout.vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import ObligacionForm from '@modules/Proyectos/Resources/js/components/ObligacionForm.vue';
import { ArrowLeft } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

interface Props {
  obligacion: any;
  contrato: any;
  posiblesPadres?: any[];
  usuarios?: any[];
}

const props = defineProps<Props>();

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '/admin/dashboard' },
  { title: 'Contratos', href: '/admin/contratos' },
  { title: props.contrato.nombre, href: `/admin/contratos/${props.contrato.id}` },
  { title: props.obligacion.titulo, href: `/admin/obligaciones/${props.obligacion.id}` },
  { title: 'Editar', href: '#' },
];

const form = useForm({
  ...props.obligacion,
  archivos_eliminar: []
});

const handleSubmit = (data: any) => {
  const formData = new FormData();

  // Lista blanca de campos permitidos (según UpdateObligacionContratoRequest)
  const camposPermitidos = ['parent_id', 'titulo', 'descripcion', 'orden'];

  // Agregar solo campos permitidos
  camposPermitidos.forEach(key => {
    if (data[key] !== undefined && data[key] !== null) {
      formData.append(key, String(data[key]));
    }
  });

  // Agregar nuevos archivos si hay
  if (data.archivos && data.archivos.length > 0) {
    data.archivos.forEach((archivo: any, index: number) => {
      formData.append(`archivos[${index}]`, archivo);
    });
  }

  // Agregar archivos a eliminar si hay
  if (data.archivos_eliminar && data.archivos_eliminar.length > 0) {
    data.archivos_eliminar.forEach((ruta: string, index: number) => {
      formData.append(`archivos_eliminar[${index}]`, ruta);
    });
  }

  // Agregar método PUT
  formData.append('_method', 'PUT');

  router.post(route('admin.obligaciones.update', props.obligacion.id), formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Obligación actualizada exitosamente');
      router.visit(route('admin.obligaciones.show', props.obligacion.id));
    },
    onError: (errors) => {
      console.error('Errores al actualizar:', errors);
      toast.error('Error al actualizar la obligación. Revisa los campos.');
    }
  });
};

const handleCancel = () => {
  router.visit(route('admin.obligaciones.show', props.obligacion.id));
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
          <Link :href="route('admin.obligaciones.show', obligacion.id)">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Volver
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold">Editar Obligación</h1>
            <p class="text-gray-600 mt-1">{{ obligacion.titulo }}</p>
          </div>
        </div>
      </div>

      <div class="max-w-4xl">
        <!-- Información del contrato -->
        <Card class="mb-6">
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
        <Card v-if="obligacion.padre" class="mb-6">
          <CardContent class="pt-6">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm text-gray-600">Obligación padre:</span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <h4 class="font-medium">{{ obligacion.padre.titulo }}</h4>
              <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                <Badge :variant="getEstadoVariant(obligacion.padre.estado)" size="sm">
                  {{ obligacion.padre.estado_label || obligacion.padre.estado }}
                </Badge>
                <span>Nivel: {{ obligacion.nivel || 0 }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Formulario -->
        <ObligacionForm
          :obligacion="obligacion"
          :contratoId="obligacion.contrato_id"
          :parentId="obligacion.parent_id"
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