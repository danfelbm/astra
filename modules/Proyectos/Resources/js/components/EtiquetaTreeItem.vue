<template>
    <div>
        <!-- Item de la etiqueta -->
        <div
            class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
            :class="{ 'opacity-50': isDisabled }"
            @click="() => !isDisabled && handleSelect(etiqueta)"
        >
            <div class="flex items-center w-full">
                <!-- Indentación basada en el nivel -->
                <span
                    class="flex-shrink-0"
                    :style="{ width: `${nivel * 20}px` }"
                ></span>

                <!-- Icono de expansión/colapso si tiene hijos -->
                <button
                    v-if="etiqueta.children && etiqueta.children.length > 0"
                    @click.stop="toggleExpanded"
                    class="mr-1 p-0.5 hover:bg-accent rounded"
                    type="button"
                >
                    <ChevronRight
                        :class="[
                            'h-3 w-3 transition-transform',
                            expanded ? 'rotate-90' : ''
                        ]"
                    />
                </button>
                <span
                    v-else
                    class="w-5"
                ></span>

                <!-- Check para item seleccionado -->
                <Check
                    :class="[
                        'mr-2 h-4 w-4 flex-shrink-0',
                        selectedId === etiqueta.id ? 'opacity-100' : 'opacity-0'
                    ]"
                />

                <!-- Nombre de la etiqueta con categoría -->
                <div class="flex-1 min-w-0">
                    <span class="truncate">
                        {{ etiqueta.nombre }}
                        <span
                            v-if="showCategoria && etiqueta.categoria"
                            class="text-xs text-muted-foreground ml-1"
                        >
                            ({{ etiqueta.categoria.nombre }})
                        </span>
                    </span>

                    <!-- Indicador de cantidad de hijos -->
                    <span
                        v-if="etiqueta.children && etiqueta.children.length > 0"
                        class="text-xs text-muted-foreground ml-1"
                    >
                        ({{ etiqueta.children.length }})
                    </span>

                    <!-- Badge si tiene usos -->
                    <Badge
                        v-if="etiqueta.usos_count > 0"
                        variant="secondary"
                        class="ml-2 text-xs"
                    >
                        {{ etiqueta.usos_count }} usos
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Hijos recursivos -->
        <template v-if="expanded && etiqueta.children">
            <EtiquetaTreeItem
                v-for="hijo in etiqueta.children"
                :key="hijo.id"
                :etiqueta="hijo"
                :nivel="nivel + 1"
                :selected-id="selectedId"
                :excluded-id="excludedId"
                :show-categoria="showCategoria"
                @select="handleSelect"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Check, ChevronRight } from 'lucide-vue-next';
import type { Etiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';

interface Props {
    etiqueta: Etiqueta;
    nivel: number;
    selectedId?: number | null;
    excludedId?: number | null;
    showCategoria?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    nivel: 0,
    showCategoria: false
});

const emit = defineEmits<{
    select: [etiqueta: Etiqueta];
}>();

const expanded = ref(props.nivel < 2); // Expandir los primeros 2 niveles por defecto

// Verificar si el item está deshabilitado
const isDisabled = computed(() => {
    // Deshabilitar si es el item excluido o si es descendiente del excluido
    if (!props.excludedId) return false;

    if (props.etiqueta.id === props.excludedId) return true;

    // TODO: Verificar si es descendiente del excluido
    // Esto requeriría tener la información completa del árbol

    return false;
});

const toggleExpanded = () => {
    expanded.value = !expanded.value;
};

const handleSelect = (etiqueta: Etiqueta) => {
    if (!isDisabled.value) {
        emit('select', etiqueta);
    }
};
</script>