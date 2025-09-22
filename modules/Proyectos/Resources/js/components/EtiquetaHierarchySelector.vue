<template>
    <div class="etiqueta-hierarchy-selector">
        <Label :for="id">{{ label }}</Label>
        <Popover v-model:open="open">
            <PopoverTrigger asChild>
                <Button
                    :id="id"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :aria-label="label"
                    class="w-full justify-between"
                >
                    <span class="truncate" v-if="etiquetaSeleccionada">
                        <span v-if="etiquetaSeleccionada.parent" class="text-muted-foreground text-xs mr-1">
                            {{ etiquetaSeleccionada.parent.nombre }} /
                        </span>
                        {{ etiquetaSeleccionada.nombre }}
                    </span>
                    <span v-else class="text-muted-foreground">{{ placeholder }}</span>
                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[400px] p-0">
                <Command>
                    <CommandInput
                        placeholder="Buscar etiqueta..."
                        v-model="busqueda"
                    />
                    <CommandList>
                        <CommandEmpty>No se encontraron etiquetas.</CommandEmpty>

                        <!-- Opción para no tener padre en su propio grupo -->
                        <CommandGroup v-if="allowNull">
                            <CommandItem
                                value=""
                                @select="seleccionarEtiqueta(null)"
                            >
                                <Check
                                    :class="[
                                        'mr-2 h-4 w-4',
                                        !modelValue ? 'opacity-100' : 'opacity-0'
                                    ]"
                                />
                                <span class="text-muted-foreground">Sin etiqueta padre (raíz)</span>
                            </CommandItem>
                        </CommandGroup>

                        <!-- Categorías con etiquetas -->
                        <CommandGroup
                            v-for="categoria in categoriasFiltradas"
                            :key="categoria.id"
                            :heading="categoria.nombre"
                        >
                            <!-- Renderizar árbol de etiquetas -->
                            <template v-for="etiqueta in etiquetasRaiz(categoria.id)" :key="etiqueta.id">
                                <EtiquetaTreeItem
                                    :etiqueta="etiqueta"
                                    :nivel="0"
                                    :selected-id="modelValue"
                                    :excluded-id="excludedId"
                                    @select="seleccionarEtiqueta"
                                />
                            </template>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>

        <!-- Mostrar ruta completa si hay selección -->
        <p v-if="etiquetaSeleccionada" class="text-xs text-muted-foreground mt-1">
            Ruta: {{ obtenerRutaCompleta(etiquetaSeleccionada) }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@modules/Core/Resources/js/components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@modules/Core/Resources/js/components/ui/popover";
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import axios from 'axios';
import type { Etiqueta, CategoriaEtiqueta } from '@modules/Proyectos/Resources/js/types/etiquetas';

// Componente hijo para renderizar items del árbol
import EtiquetaTreeItem from './EtiquetaTreeItem.vue';

interface Props {
    modelValue?: number | null;
    label?: string;
    placeholder?: string;
    allowNull?: boolean;
    excludedId?: number | null;
    categorias?: CategoriaEtiqueta[];
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Etiqueta Padre',
    placeholder: 'Seleccionar etiqueta padre...',
    allowNull: true,
    id: 'etiqueta-padre'
});

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const open = ref(false);
const busqueda = ref('');
const etiquetas = ref<Etiqueta[]>([]);
const loading = ref(false);

// Etiqueta actualmente seleccionada
const etiquetaSeleccionada = computed(() => {
    if (!props.modelValue) return null;
    return etiquetas.value.find(e => e.id === props.modelValue) || null;
});

// Categorías filtradas por búsqueda
const categoriasFiltradas = computed(() => {
    if (!props.categorias) return [];

    if (!busqueda.value) {
        return props.categorias;
    }

    // Filtrar categorías que tienen etiquetas que coinciden con la búsqueda
    return props.categorias.filter(categoria => {
        return etiquetasPorCategoria(categoria.id).some(etiqueta =>
            etiqueta.nombre.toLowerCase().includes(busqueda.value.toLowerCase()) ||
            (etiqueta.descripcion && etiqueta.descripcion.toLowerCase().includes(busqueda.value.toLowerCase()))
        );
    });
});

// Obtener etiquetas raíz de una categoría
const etiquetasRaiz = (categoriaId: number): Etiqueta[] => {
    return etiquetas.value.filter(e =>
        e.categoria_etiqueta_id === categoriaId &&
        !e.parent_id &&
        e.id !== props.excludedId
    );
};

// Obtener todas las etiquetas de una categoría
const etiquetasPorCategoria = (categoriaId: number): Etiqueta[] => {
    return etiquetas.value.filter(e =>
        e.categoria_etiqueta_id === categoriaId &&
        e.id !== props.excludedId
    );
};

// Obtener ruta completa de una etiqueta
const obtenerRutaCompleta = (etiqueta: Etiqueta): string => {
    const ruta: string[] = [etiqueta.nombre];
    let actual = etiqueta;

    while (actual.parent_id) {
        const padre = etiquetas.value.find(e => e.id === actual.parent_id);
        if (!padre) break;
        ruta.unshift(padre.nombre);
        actual = padre;
    }

    return ruta.join(' / ');
};

// Cargar etiquetas con jerarquía
const cargarEtiquetas = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/admin/etiquetas/para-selector', {
            params: {
                excluir_id: props.excludedId
            }
        });

        if (response.data.success) {
            etiquetas.value = response.data.etiquetas;
        }
    } catch (error) {
        console.error('Error cargando etiquetas:', error);
    } finally {
        loading.value = false;
    }
};

// Seleccionar etiqueta
const seleccionarEtiqueta = (etiqueta: Etiqueta | null) => {
    emit('update:modelValue', etiqueta?.id || null);
    open.value = false;
};

onMounted(() => {
    cargarEtiquetas();
});
</script>

<style scoped>
.etiqueta-hierarchy-selector {
    width: 100%;
}
</style>