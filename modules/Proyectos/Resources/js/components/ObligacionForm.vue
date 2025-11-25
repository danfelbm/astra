<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <!-- Información básica -->
    <Card>
      <CardHeader>
        <CardTitle>Información Básica</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- Selector de contrato (solo si no viene de un contrato específico) -->
        <div v-if="!contratoId && contratos && contratos.length > 0">
          <Label for="contrato_id" required>Contrato</Label>
          <Select v-model="form.contrato_id">
            <SelectTrigger id="contrato_id" :class="{ 'border-red-500': errors?.contrato_id }">
              <SelectValue placeholder="Seleccionar contrato" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="contrato in contratos"
                :key="contrato.id"
                :value="contrato.id"
              >
                {{ contrato.nombre }}
                <span v-if="contrato.proyecto" class="text-muted-foreground ml-1">
                  ({{ contrato.proyecto.nombre }})
                </span>
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError v-if="errors?.contrato_id" :message="errors.contrato_id[0]" />
          <p class="text-xs text-muted-foreground mt-1">
            Selecciona el contrato al que pertenecerá esta obligación
          </p>
        </div>

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

        <!-- Obligación padre (solo si hay contrato seleccionado) -->
        <div v-if="contratoId || form.contrato_id">
          <Label for="parent_id">Obligación Padre</Label>
          <!-- Estado de carga -->
          <div v-if="cargandoPadres" class="flex items-center gap-2 h-10 px-3 border rounded-md bg-muted/50">
            <Loader2 class="h-4 w-4 animate-spin" />
            <span class="text-sm text-muted-foreground">Cargando obligaciones...</span>
          </div>
          <!-- Selector cuando no está cargando -->
          <Select v-else v-model="form.parent_id" :disabled="cargandoPadres">
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
          <p v-if="!cargandoPadres && obligacionesPadre.length === 0" class="text-xs text-muted-foreground mt-1">
            Este contrato no tiene obligaciones existentes. La nueva obligación será raíz.
          </p>
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
        :disabled="loading || !form.titulo || !form.contrato_id"
      >
        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
        {{ obligacion ? 'Actualizar' : 'Crear' }} Obligación
      </Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
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
  contratos?: Array<{ id: number; nombre: string; proyecto?: { id: number; nombre: string } }>;
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

// Estado para posibles padres cargados dinámicamente
const posiblesPadresDinamicos = ref<Array<{ id: number; titulo: string; nivel: number; parent_id?: number | null }>>([]);
const cargandoPadres = ref(false);

// Computed: usa props si vienen de un contrato específico, o los dinámicos si se seleccionó uno
const obligacionesPadre = computed(() => {
  // Si hay contratoId prop, usar los posiblesPadres del prop
  if (props.contratoId) {
    return props.posiblesPadres || [];
  }
  // Si no, usar los cargados dinámicamente
  return posiblesPadresDinamicos.value;
});

// Cargar posibles padres cuando cambia el contrato seleccionado
const cargarPosiblesPadres = async (contratoId: number) => {
  if (!contratoId) {
    posiblesPadresDinamicos.value = [];
    return;
  }

  cargandoPadres.value = true;
  try {
    const response = await axios.get('/admin/obligaciones/posibles-padres', {
      params: { contrato_id: contratoId }
    });

    if (response.data.success) {
      posiblesPadresDinamicos.value = response.data.posiblesPadres;
    }
  } catch (error) {
    console.error('Error cargando posibles padres:', error);
    posiblesPadresDinamicos.value = [];
  } finally {
    cargandoPadres.value = false;
  }
};

// Watch para cargar posibles padres cuando cambie el contrato (solo si no viene de un contrato específico)
watch(() => form.value.contrato_id, (newContratoId) => {
  // Solo cargar dinámicamente si no hay contratoId prop
  if (!props.contratoId && newContratoId) {
    // Limpiar parent_id al cambiar de contrato
    form.value.parent_id = null;
    cargarPosiblesPadres(newContratoId);
  }
});

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