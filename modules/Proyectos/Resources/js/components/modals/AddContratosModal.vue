<script setup lang="ts">
/**
 * Modal para seleccionar contratos.
 * Sigue el patrón de AddUsersModal.vue pero adaptado para contratos.
 * Soporta filtro por proyecto con Combobox, búsqueda con debounce y paginación.
 */
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@modules/Core/Resources/js/components/ui/dialog";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Checkbox } from "@modules/Core/Resources/js/components/ui/checkbox";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@modules/Core/Resources/js/components/ui/popover";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@modules/Core/Resources/js/components/ui/command";
import { ChevronLeft, ChevronRight, Loader2, Search, FileText, Check, ChevronsUpDown, X } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { toast } from 'vue-sonner';
import type { Contrato, EstadoContrato, Proyecto } from '@modules/Proyectos/Resources/js/types/contratos';
import { getEstadoLabel, getEstadoColor, formatMonto } from '@modules/Proyectos/Resources/js/types/contratos';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

// Interfaz simplificada para contratos en el listado
interface ContratoListItem {
    id: number;
    nombre: string;
    proyecto_id: number;
    estado: EstadoContrato;
    tipo: string;
    fecha_inicio: string;
    fecha_fin?: string;
    monto_total?: number;
    moneda: string;
    proyecto?: {
        id: number;
        nombre: string;
    };
}

interface Props {
    modelValue: boolean;
    title?: string;
    description?: string;
    searchEndpoint: string;
    excludedIds?: number[];
    proyectos?: Proyecto[];
    proyectoId?: number;
    estadosPermitidos?: EstadoContrato[];
    maxSelection?: number;
    submitButtonText?: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    noResultsMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Seleccionar Contrato',
    description: 'Selecciona el contrato que deseas utilizar',
    submitButtonText: 'Seleccionar',
    searchPlaceholder: 'Buscar por nombre, proyecto o contraparte...',
    emptyMessage: 'Escribe para buscar contratos disponibles',
    noResultsMessage: 'No se encontraron contratos con esa búsqueda',
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    'submit': [data: { contratoIds: number[]; contratos?: ContratoListItem[] }];
}>();

// Estado del modal
const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Estado de búsqueda y selección
const searchQuery = ref('');
const contratos = ref<ContratoListItem[]>([]);
const selectedContratoIds = ref<number[]>([]);
const loading = ref(false);
const submitting = ref(false);
const initialized = ref(false);

// Estado del filtro de proyecto (Combobox)
const proyectoFiltro = ref<number | null>(props.proyectoId || null);
const proyectoComboboxOpen = ref(false);

// Estado de paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalContratos = ref(0);

// Computed: proyecto seleccionado para mostrar en el combobox
const proyectoSeleccionado = computed(() => {
    if (!proyectoFiltro.value || !props.proyectos) return null;
    return props.proyectos.find(p => p.id === proyectoFiltro.value);
});

// Función de búsqueda de contratos
const searchContratos = async (page: number = 1) => {
    loading.value = true;
    try {
        const params: Record<string, any> = {
            page,
            search: searchQuery.value,
        };

        // Filtro por proyecto
        if (proyectoFiltro.value) {
            params.proyecto_id = proyectoFiltro.value;
        }

        // Estados permitidos
        if (props.estadosPermitidos && props.estadosPermitidos.length > 0) {
            params.estados = props.estadosPermitidos;
        }

        // IDs excluidos
        if (props.excludedIds && props.excludedIds.length > 0) {
            params.excluded_ids = props.excludedIds;
        }

        const response = await axios.get(props.searchEndpoint, { params });

        // Manejar respuesta
        if (response.data.contratos) {
            contratos.value = response.data.contratos || [];
            currentPage.value = response.data.current_page || 1;
            lastPage.value = response.data.last_page || 1;
            totalContratos.value = response.data.total || 0;
        } else {
            // Formato alternativo de paginación
            contratos.value = response.data.data || [];
            currentPage.value = response.data.current_page || 1;
            lastPage.value = response.data.last_page || 1;
            totalContratos.value = response.data.total || 0;
        }
    } catch (error) {
        console.error('Error buscando contratos:', error);
        toast.error('Error al buscar contratos', {
            description: 'Por favor intenta nuevamente',
            duration: 3000,
        });
        contratos.value = [];
    } finally {
        loading.value = false;
    }
};

// Debounce para búsqueda
let searchTimeout: number | null = null;
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (isOpen.value) {
            currentPage.value = 1;
            searchContratos(1);
        }
    }, 500);
});

// Watch para filtro de proyecto
watch(proyectoFiltro, () => {
    if (isOpen.value && initialized.value) {
        currentPage.value = 1;
        searchContratos(1);
    }
});

// Cargar contratos cuando se abre el modal
watch(isOpen, (newValue) => {
    if (newValue && !initialized.value) {
        initialized.value = true;
        searchContratos(1);
    }
    if (!newValue) {
        // Limpiar al cerrar
        selectedContratoIds.value = [];
        searchQuery.value = '';
        contratos.value = [];
        currentPage.value = 1;
        initialized.value = false;
        // No resetear proyectoFiltro si viene de props
        if (!props.proyectoId) {
            proyectoFiltro.value = null;
        }
    }
});

// Manejar selección de contratos
const toggleContratoSelection = (contratoId: number, checked: boolean) => {
    if (checked) {
        // Verificar límite máximo si existe
        if (props.maxSelection && selectedContratoIds.value.length >= props.maxSelection) {
            toast.warning(`Máximo ${props.maxSelection} contrato(s) permitido(s)`);
            return;
        }
        if (!selectedContratoIds.value.includes(contratoId)) {
            selectedContratoIds.value.push(contratoId);
        }
    } else {
        const index = selectedContratoIds.value.indexOf(contratoId);
        if (index > -1) {
            selectedContratoIds.value.splice(index, 1);
        }
    }
};

// Verificar si un contrato está seleccionado
const isContratoSelected = (contratoId: number) => {
    return selectedContratoIds.value.includes(contratoId);
};

// Cambiar página
const changePage = (page: number) => {
    currentPage.value = page;
    searchContratos(page);
};

// Enviar selección
const handleSubmit = async () => {
    if (selectedContratoIds.value.length === 0) return;

    // Obtener los datos completos de los contratos seleccionados
    const selectedContratos = contratos.value.filter(c => selectedContratoIds.value.includes(c.id));

    emit('submit', {
        contratoIds: selectedContratoIds.value,
        contratos: selectedContratos
    });
    isOpen.value = false;
};

// Seleccionar proyecto en combobox
const selectProyecto = (proyectoId: number | null) => {
    proyectoFiltro.value = proyectoId;
    proyectoComboboxOpen.value = false;
};

// Formatear fecha
const formatDate = (dateString: string | undefined) => {
    if (!dateString) return '-';
    try {
        return format(parseISO(dateString), 'dd MMM yyyy', { locale: es });
    } catch {
        return dateString;
    }
};

// Obtener variante del badge según estado
const getEstadoBadgeVariant = (estado: EstadoContrato) => {
    const variants: Record<string, any> = {
        'borrador': 'secondary',
        'activo': 'success',
        'finalizado': 'default',
        'cancelado': 'destructive'
    };
    return variants[estado] || 'secondary';
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Filtros: Proyecto (Combobox) y Búsqueda -->
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Filtro de proyecto (Combobox con búsqueda) -->
                    <div v-if="proyectos && proyectos.length > 0 && !proyectoId">
                        <label class="text-sm font-medium">Filtrar por Proyecto</label>
                        <Popover v-model:open="proyectoComboboxOpen">
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    :aria-expanded="proyectoComboboxOpen"
                                    class="w-full justify-between"
                                >
                                    <span class="truncate">
                                        {{ proyectoSeleccionado?.nombre || 'Todos los proyectos' }}
                                    </span>
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[300px] p-0">
                                <Command>
                                    <CommandInput placeholder="Buscar proyecto..." />
                                    <CommandList>
                                        <CommandEmpty>No se encontraron proyectos.</CommandEmpty>
                                        <CommandGroup>
                                            <!-- Opción: Todos los proyectos -->
                                            <CommandItem
                                                value="todos"
                                                @select="selectProyecto(null)"
                                            >
                                                <Check
                                                    :class="[
                                                        'mr-2 h-4 w-4',
                                                        !proyectoFiltro ? 'opacity-100' : 'opacity-0'
                                                    ]"
                                                />
                                                Todos los proyectos
                                            </CommandItem>
                                            <!-- Lista de proyectos -->
                                            <CommandItem
                                                v-for="proyecto in proyectos"
                                                :key="proyecto.id"
                                                :value="proyecto.nombre"
                                                @select="selectProyecto(proyecto.id)"
                                            >
                                                <Check
                                                    :class="[
                                                        'mr-2 h-4 w-4',
                                                        proyectoFiltro === proyecto.id ? 'opacity-100' : 'opacity-0'
                                                    ]"
                                                />
                                                {{ proyecto.nombre }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Campo de búsqueda -->
                    <div :class="{ 'md:col-span-2': !proyectos || proyectos.length === 0 || proyectoId }">
                        <label class="text-sm font-medium">Buscar Contratos</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                :placeholder="searchPlaceholder"
                                type="text"
                                class="pl-10"
                            />
                        </div>
                    </div>
                </div>

                <!-- Lista de contratos -->
                <div class="border rounded-lg">
                    <div class="max-h-96 overflow-y-auto p-4">
                        <!-- Loading -->
                        <div v-if="loading" class="flex items-center justify-center py-8">
                            <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                            <span class="ml-2 text-muted-foreground">Buscando contratos...</span>
                        </div>

                        <!-- Lista de contratos -->
                        <div v-else-if="contratos.length > 0" class="space-y-2">
                            <div
                                v-for="contrato in contratos"
                                :key="contrato.id"
                                class="flex items-start space-x-3 py-3 px-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded transition-colors border-b last:border-b-0"
                            >
                                <Checkbox
                                    :id="`contrato-${contrato.id}`"
                                    :checked="isContratoSelected(contrato.id)"
                                    @update:checked="(value) => toggleContratoSelection(contrato.id, value)"
                                    class="mt-1"
                                />
                                <label
                                    :for="`contrato-${contrato.id}`"
                                    class="flex-1 cursor-pointer"
                                >
                                    <!-- Nombre del contrato -->
                                    <div class="font-medium">{{ contrato.nombre }}</div>

                                    <!-- Proyecto -->
                                    <div v-if="contrato.proyecto" class="text-sm text-muted-foreground">
                                        {{ contrato.proyecto.nombre }}
                                    </div>

                                    <!-- Estado y tipo -->
                                    <div class="flex items-center gap-2 mt-1">
                                        <Badge :variant="getEstadoBadgeVariant(contrato.estado)" class="text-xs">
                                            {{ getEstadoLabel(contrato.estado) }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            {{ contrato.tipo }}
                                        </span>
                                    </div>

                                    <!-- Fechas y monto -->
                                    <div class="flex flex-wrap gap-3 mt-1 text-xs text-muted-foreground">
                                        <span>
                                            {{ formatDate(contrato.fecha_inicio) }}
                                            <span v-if="contrato.fecha_fin"> → {{ formatDate(contrato.fecha_fin) }}</span>
                                        </span>
                                        <span v-if="contrato.monto_total" class="font-medium">
                                            {{ formatMonto(contrato.monto_total, contrato.moneda) }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Sin resultados -->
                        <div v-else class="text-center py-8">
                            <FileText class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
                            <p class="text-muted-foreground">
                                {{ searchQuery || proyectoFiltro ? noResultsMessage : emptyMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="lastPage > 1" class="border-t px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">
                                Página {{ currentPage }} de {{ lastPage }}
                                <span v-if="totalContratos > 0">
                                    ({{ totalContratos }} contratos disponibles)
                                </span>
                            </span>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === 1"
                                    @click="changePage(currentPage - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === lastPage"
                                    @click="changePage(currentPage + 1)"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <div class="flex items-center justify-between w-full">
                    <span class="text-sm text-muted-foreground">
                        <span v-if="selectedContratoIds.length > 0" class="font-medium text-foreground">
                            {{ selectedContratoIds.length }} contrato(s) seleccionado(s)
                        </span>
                        <span v-else>
                            Selecciona contratos para continuar
                        </span>
                    </span>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="isOpen = false">
                            Cancelar
                        </Button>
                        <Button
                            @click="handleSubmit"
                            :disabled="selectedContratoIds.length === 0 || submitting"
                        >
                            <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
                            {{ submitButtonText }}
                            <span v-if="selectedContratoIds.length > 0">
                                ({{ selectedContratoIds.length }})
                            </span>
                        </Button>
                    </div>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
