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
        <!-- Archivos existentes (solo en edición) -->
        <div v-if="obligacion?.archivos_adjuntos?.length" class="mb-4">
          <Label>Archivos actuales</Label>
          <div class="space-y-2 mt-2">
            <div
              v-for="(archivo, index) in obligacion.archivos_adjuntos"
              :key="index"
              class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-2">
                <Paperclip class="h-4 w-4 text-gray-500" />
                <span class="text-sm">{{ archivo.nombre_original }}</span>
                <Badge variant="outline" class="text-xs">
                  {{ formatFileSize(archivo.tamaño) }}
                </Badge>
              </div>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-red-600"
                @click="eliminarArchivo(archivo.ruta)"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Nuevos archivos -->
        <div>
          <Label for="archivos">Agregar nuevos archivos</Label>
          <Input
            id="archivos"
            type="file"
            multiple
            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
            @change="handleFileChange"
            class="mt-1"
          />
          <p class="text-xs text-gray-500 mt-1">
            Formatos permitidos: PDF, Word, Excel, Imágenes. Máximo 5MB por archivo.
          </p>
        </div>

        <!-- Vista previa de archivos nuevos -->
        <div v-if="nuevosArchivos.length > 0" class="mt-4">
          <Label>Archivos a subir</Label>
          <div class="space-y-2 mt-2">
            <div
              v-for="(archivo, index) in nuevosArchivos"
              :key="index"
              class="flex items-center justify-between p-2 bg-blue-50 rounded-lg"
            >
              <div class="flex items-center gap-2">
                <Upload class="h-4 w-4 text-blue-500" />
                <span class="text-sm">{{ archivo.name }}</span>
                <Badge variant="outline" class="text-xs">
                  {{ formatFileSize(archivo.size) }}
                </Badge>
              </div>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="removerNuevoArchivo(index)"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
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
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
// Slider no disponible, usando input range nativo
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@modules/Core/Resources/js/components/ui/select';
import InputError from '@modules/Core/Resources/js/components/InputError.vue';
import {
  Loader2,
  Paperclip,
  Upload,
  X
} from 'lucide-vue-next';
import type { ObligacionContrato, ObligacionFormProps, ObligacionFormData } from '@modules/Proyectos/Resources/js/types/obligaciones';

const props = withDefaults(defineProps<ObligacionFormProps>(), {
  loading: false,
  errors: () => ({})
});

const emit = defineEmits<{
  submit: [data: ObligacionFormData];
  cancel: [];
}>();

// Estado del formulario
const form = ref<ObligacionFormData>({
  contrato_id: props.contratoId,
  parent_id: props.parentId || props.obligacion?.parent_id || null,
  titulo: props.obligacion?.titulo || '',
  descripcion: props.obligacion?.descripcion || '',
  archivos: [],
  archivos_eliminar: [],
});

const nuevosArchivos = ref<File[]>([]);
const obligacionesPadre = ref<ObligacionContrato[]>([]); // Cargar desde API para mostrar jerarquía

// Métodos
const handleSubmit = () => {
  const formData = {
    ...form.value,
    archivos: nuevosArchivos.value
  };
  emit('submit', formData);
};

const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (!input.files) return;

  const archivos = Array.from(input.files);

  // Validar tamaño (máximo 5MB por archivo)
  const archivosValidos = archivos.filter(archivo => {
    if (archivo.size > 5 * 1024 * 1024) {
      alert(`El archivo ${archivo.name} excede el tamaño máximo de 5MB`);
      return false;
    }
    return true;
  });

  nuevosArchivos.value.push(...archivosValidos);
  form.value.archivos = nuevosArchivos.value;

  // Limpiar input
  input.value = '';
};

const removerNuevoArchivo = (index: number) => {
  nuevosArchivos.value.splice(index, 1);
  form.value.archivos = nuevosArchivos.value;
};

const eliminarArchivo = (ruta: string) => {
  if (confirm('¿Estás seguro de eliminar este archivo?')) {
    form.value.archivos_eliminar = form.value.archivos_eliminar || [];
    form.value.archivos_eliminar.push(ruta);
  }
};

const formatFileSize = (bytes: number | undefined): string => {
  if (!bytes) return '0 KB';
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
};
</script>