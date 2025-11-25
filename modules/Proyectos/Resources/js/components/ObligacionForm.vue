<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <!-- Información básica -->
    <Card>
      <CardHeader>
        <CardTitle>Información Básica</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- Título -->
        <div>
          <Label for="titulo" required>Título</Label>
          <Input
            id="titulo"
            v-model="form.titulo"
            type="text"
            placeholder="Título de la obligación"
            :class="{ 'border-red-500': errors?.titulo }"
          />
          <InputError v-if="errors?.titulo" :message="errors.titulo[0]" />
        </div>

        <!-- Descripción -->
        <div>
          <Label for="descripcion">Descripción</Label>
          <Textarea
            id="descripcion"
            v-model="form.descripcion"
            placeholder="Descripción detallada de la obligación"
            :rows="3"
            :class="{ 'border-red-500': errors?.descripcion }"
          />
          <InputError v-if="errors?.descripcion" :message="errors.descripcion[0]" />
        </div>

        <!-- Obligación padre (si aplica) -->
        <div v-if="obligacionesPadre.length > 0">
          <Label for="parent_id">Obligación Padre</Label>
          <Select v-model="form.parent_id">
            <SelectTrigger id="parent_id">
              <SelectValue placeholder="Seleccionar obligación padre (opcional)" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">Sin padre (raíz)</SelectItem>
              <SelectItem
                v-for="padre in obligacionesPadre"
                :key="padre.id"
                :value="padre.id"
                :disabled="padre.id === obligacion?.id"
              >
                {{ '  '.repeat(padre.nivel) }}{{ padre.titulo }}
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError v-if="errors?.parent_id" :message="errors.parent_id[0]" />
        </div>
      </CardContent>
    </Card>

    <!-- Archivos adjuntos -->
    <Card>
      <CardHeader>
        <CardTitle>Archivos Adjuntos</CardTitle>
      </CardHeader>
      <CardContent>
        <FileAttachmentManager
          :existing-files="archivosExistentesFiltrados"
          description="Formatos: PDF, Word, Excel, Imágenes"
          :multiple="true"
          :max-files="20"
          :max-size-m-b="5"
          accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
          @files-added="handleFilesAdded"
          @file-removed="handleFileRemoved"
        />
      </CardContent>
    </Card>

    <!-- Botones de acción -->
    <div class="flex justify-end gap-2">
      <Button
        type="button"
        variant="outline"
        @click="$emit('cancel')"
        :disabled="loading"
      >
        Cancelar
      </Button>
      <Button
        type="submit"
        :disabled="loading || !form.titulo"
      >
        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
        {{ obligacion ? 'Actualizar' : 'Crear' }} Obligación
      </Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@modules/Core/Resources/js/components/ui/select';
import InputError from '@modules/Core/Resources/js/components/InputError.vue';
import FileAttachmentManager from '@modules/Core/Resources/js/components/forms/FileAttachmentManager.vue';
import { Loader2 } from 'lucide-vue-next';
import type { ObligacionContrato, ObligacionFormData } from '@modules/Proyectos/Resources/js/types/obligaciones';

// Interfaz de Props extendida
interface Props {
  /** Obligación a editar (null para crear) */
  obligacion?: ObligacionContrato;
  /** ID del contrato */
  contratoId?: number;
  /** Lista de contratos disponibles */
  contratos?: Array<{ id: number; nombre: string }>;
  /** ID del padre (para crear sub-obligación) */
  parentId?: number | null;
  /** Lista de posibles padres para el selector */
  posiblesPadres?: Array<{ id: number; titulo: string; nivel: number; parent_id?: number | null }>;
  /** Lista de usuarios para asignar responsable */
  usuarios?: Array<{ id: number; name: string; email: string }>;
  /** Si está cargando */
  loading?: boolean;
  /** Errores de validación */
  errors?: Record<string, string[]>;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  errors: () => ({}),
  posiblesPadres: () => [],
  contratos: () => [],
  usuarios: () => []
});

const emit = defineEmits<{
  submit: [data: ObligacionFormData];
  cancel: [];
}>();

// Estado del formulario
const form = ref<ObligacionFormData>({
  contrato_id: props.contratoId || props.obligacion?.contrato_id || 0,
  parent_id: props.parentId || props.obligacion?.parent_id || null,
  titulo: props.obligacion?.titulo || '',
  descripcion: props.obligacion?.descripcion || '',
  archivos: [],
  archivos_eliminar: [],
});

// Usar posiblesPadres del prop
const obligacionesPadre = computed(() => props.posiblesPadres || []);

// Computed para archivos existentes filtrados (excluyendo los marcados para eliminar)
const archivosExistentesFiltrados = computed(() => {
  const archivosOriginales = props.obligacion?.archivos_adjuntos || [];
  return archivosOriginales.filter((archivo: any) =>
    !form.value.archivos_eliminar?.includes(archivo.ruta)
  );
});

// Métodos
const handleSubmit = () => {
  const formData = {
    ...form.value
  };
  emit('submit', formData);
};

// Manejar archivos agregados desde FileAttachmentManager
const handleFilesAdded = (files: File[]) => {
  form.value.archivos = files;
};

// Manejar archivos eliminados desde FileAttachmentManager
const handleFileRemoved = (ruta: string) => {
  form.value.archivos_eliminar = form.value.archivos_eliminar || [];
  if (!form.value.archivos_eliminar.includes(ruta)) {
    form.value.archivos_eliminar.push(ruta);
  }
};
</script>