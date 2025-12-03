<script setup lang="ts">
/**
 * InlineEditEtiquetas - Componente para edición inline de etiquetas
 * Abre EtiquetaSelector al activar edición
 */
import { ref, computed } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@modules/Core/Resources/js/components/ui/dialog';
import EtiquetaSelector from '@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue';
import { Pencil, Loader2, Tag } from 'lucide-vue-next';
import type { CategoriaEtiqueta, Etiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';

interface Props {
    // IDs de etiquetas seleccionadas
    modelValue: number[];
    // Etiquetas actuales con info completa (para display)
    etiquetas?: Etiqueta[];
    // Categorías disponibles
    categorias: CategoriaEtiqueta[];
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    maxEtiquetas?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    etiquetas: () => [],
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Sin etiquetas',
    maxEtiquetas: 10,
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
    'save': [value: number[]];
}>();

// Estado del modal
const showModal = ref(false);
// Valor temporal durante edición
const tempValue = ref<number[]>([...props.modelValue]);

// Abrir modal
const openModal = () => {
    if (!props.canEdit || props.disabled || props.loading) return;
    tempValue.value = [...props.modelValue];
    showModal.value = true;
};

// Guardar etiquetas
const save = () => {
    // Verificar si cambió
    const changed = tempValue.value.length !== props.modelValue.length ||
        tempValue.value.some(id => !props.modelValue.includes(id));

    if (changed) {
        emit('save', tempValue.value);
    }
    showModal.value = false;
};

// Cancelar
const cancel = () => {
    tempValue.value = [...props.modelValue];
    showModal.value = false;
};
</script>

<template>
    <div class="group inline-edit-etiquetas">
        <!-- Display de etiquetas -->
        <div class="flex items-center gap-2 flex-wrap">
            <template v-if="etiquetas && etiquetas.length > 0">
                <Badge
                    v-for="etiqueta in etiquetas"
                    :key="etiqueta.id"
                    variant="secondary"
                    :style="etiqueta.color ? { backgroundColor: etiqueta.color + '20', color: etiqueta.color, borderColor: etiqueta.color } : {}"
                    class="text-xs"
                >
                    {{ etiqueta.nombre }}
                </Badge>
            </template>
            <span v-else class="text-sm text-muted-foreground italic">
                {{ placeholder }}
            </span>

            <!-- Loading indicator -->
            <Loader2
                v-if="loading"
                class="h-4 w-4 animate-spin text-muted-foreground"
            />

            <!-- Botón editar -->
            <Button
                v-if="canEdit && !disabled && !loading"
                variant="ghost"
                size="icon"
                class="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                @click.stop="openModal"
                :aria-label="`Editar ${label || 'etiquetas'}`"
            >
                <Pencil class="h-3.5 w-3.5" />
            </Button>
        </div>

        <!-- Modal de edición -->
        <Dialog v-model:open="showModal">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Tag class="h-5 w-5" />
                        Editar Etiquetas
                    </DialogTitle>
                </DialogHeader>

                <div class="py-4">
                    <EtiquetaSelector
                        v-model="tempValue"
                        :categorias="categorias"
                        :max-etiquetas="maxEtiquetas"
                        placeholder="Seleccionar etiquetas..."
                    />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="cancel">
                        Cancelar
                    </Button>
                    <Button @click="save" :disabled="loading">
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        Guardar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
