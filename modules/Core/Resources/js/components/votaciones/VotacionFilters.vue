<template>
    <div class="space-y-4">
        <!-- Barra de búsqueda y filtros principales -->
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Búsqueda -->
            <div class="flex-1">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                        v-model="localFilters.search"
                        @input="debouncedUpdate"
                        placeholder="Buscar votaciones..."
                        class="pl-10"
                    />
                </div>
            </div>

            <!-- Filtros rápidos -->
            <div class="flex gap-2">
                <!-- Categoría -->
                <Select v-if="showCategoryFilter && categorias.length > 0" v-model="localFilters.categoria_id" @update:model-value="updateFilters">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Todas las categorías" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">
                            Todas las categorías
                        </SelectItem>
                        <SelectItem 
                            v-for="categoria in categorias" 
                            :key="categoria.id" 
                            :value="categoria.id"
                        >
                            {{ categoria.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Estado -->
                <Select v-if="showStatusFilter" v-model="localFilters.estado" @update:model-value="updateFilters">
                    <SelectTrigger class="w-[150px]">
                        <SelectValue placeholder="Todos los estados" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">
                            Todos los estados
                        </SelectItem>
                        <SelectItem value="activa">Activas</SelectItem>
                        <SelectItem value="pendiente">Pendientes</SelectItem>
                        <SelectItem value="finalizada">Finalizadas</SelectItem>
                        <SelectItem value="cancelada">Canceladas</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Toggle mostrar pasadas -->
                <div v-if="showPastToggle" class="flex items-center space-x-2">
                    <Switch 
                        :id="'show-past-' + uid"
                        v-model:checked="localFilters.mostrar_pasadas"
                        @update:checked="updateFilters"
                    />
                    <Label :for="'show-past-' + uid" class="cursor-pointer">
                        Mostrar pasadas
                    </Label>
                </div>

                <!-- Botón de filtros avanzados -->
                <Button 
                    v-if="showAdvancedFilters"
                    @click="showAdvancedModal = true"
                    variant="outline"
                    size="default"
                >
                    <Filter class="h-4 w-4 mr-2" />
                    Filtros
                    <Badge v-if="activeFiltersCount > 0" class="ml-2" variant="secondary">
                        {{ activeFiltersCount }}
                    </Badge>
                </Button>

                <!-- Botón limpiar filtros -->
                <Button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    variant="ghost"
                    size="icon"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Chips de filtros activos -->
        <div v-if="activeFilterChips.length > 0" class="flex flex-wrap gap-2">
            <Badge 
                v-for="chip in activeFilterChips" 
                :key="chip.key"
                variant="secondary"
                class="cursor-pointer hover:bg-secondary/80"
                @click="removeFilter(chip.key)"
            >
                {{ chip.label }}: {{ chip.value }}
                <X class="h-3 w-3 ml-1" />
            </Badge>
        </div>
    </div>

    <!-- Modal de filtros avanzados -->
    <Dialog v-model:open="showAdvancedModal" v-if="showAdvancedFilters">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Filtros avanzados</DialogTitle>
                <DialogDescription>
                    Configura filtros detallados para encontrar las votaciones que buscas.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Aquí se integraría el componente de filtros avanzados existente -->
                <slot name="advanced-filters" :filters="localFilters" />
            </div>

            <DialogFooter>
                <Button @click="showAdvancedModal = false" variant="outline">
                    Cancelar
                </Button>
                <Button @click="applyAdvancedFilters">
                    Aplicar filtros
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import { Input } from "../ui/input";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Label } from "../ui/label";
import { Switch } from "../ui/switch";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../ui/select";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "../ui/dialog";
import { Search, Filter, X } from 'lucide-vue-next';

interface Props {
    filters: Record<string, any>;
    categorias?: any[];
    showCategoryFilter?: boolean;
    showStatusFilter?: boolean;
    showPastToggle?: boolean;
    showAdvancedFilters?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    categorias: () => [],
    showCategoryFilter: true,
    showStatusFilter: true,
    showPastToggle: true,
    showAdvancedFilters: false
});

const emit = defineEmits<{
    update: [filters: Record<string, any>];
}>();

// Estado local
const localFilters = ref({ ...props.filters });
const showAdvancedModal = ref(false);

// UID único para los elementos del formulario
const uid = Math.random().toString(36).substr(2, 9);

// Sincronizar con props
watch(() => props.filters, (newFilters) => {
    localFilters.value = { ...newFilters };
}, { deep: true });

// Actualizar filtros con debounce para búsqueda
const debouncedUpdate = debounce(() => {
    updateFilters();
}, 300);

// Actualizar filtros inmediatamente
const updateFilters = () => {
    emit('update', { ...localFilters.value });
};

// Limpiar filtros
const clearFilters = () => {
    localFilters.value = {
        search: '',
        categoria_id: null,
        estado: null,
        mostrar_pasadas: false,
        advanced_filters: null
    };
    updateFilters();
};

// Aplicar filtros avanzados
const applyAdvancedFilters = () => {
    showAdvancedModal.value = false;
    updateFilters();
};

// Remover un filtro específico
const removeFilter = (key: string) => {
    if (key === 'advanced_filters') {
        localFilters.value.advanced_filters = null;
    } else {
        localFilters.value[key] = null;
    }
    updateFilters();
};

// Computed properties
const hasActiveFilters = computed(() => {
    return localFilters.value.search ||
           localFilters.value.categoria_id ||
           localFilters.value.estado ||
           localFilters.value.mostrar_pasadas ||
           localFilters.value.advanced_filters;
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (localFilters.value.advanced_filters) {
        count += Object.keys(localFilters.value.advanced_filters).length;
    }
    return count;
});

const activeFilterChips = computed(() => {
    const chips = [];
    
    if (localFilters.value.search) {
        chips.push({
            key: 'search',
            label: 'Búsqueda',
            value: localFilters.value.search
        });
    }
    
    if (localFilters.value.categoria_id && props.categorias) {
        const categoria = props.categorias.find(c => c.id === localFilters.value.categoria_id);
        if (categoria) {
            chips.push({
                key: 'categoria_id',
                label: 'Categoría',
                value: categoria.nombre
            });
        }
    }
    
    if (localFilters.value.estado) {
        chips.push({
            key: 'estado',
            label: 'Estado',
            value: localFilters.value.estado
        });
    }
    
    return chips;
});
</script>