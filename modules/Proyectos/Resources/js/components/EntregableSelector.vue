<script setup lang="ts">
/**
 * Componente selector de entregables reutilizable.
 * Permite filtrar entregables por hito, estado y texto de búsqueda.
 */
import { ref, computed, watch } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { Checkbox } from '@modules/Core/Resources/js/components/ui/checkbox';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import { ScrollArea } from '@modules/Core/Resources/js/components/ui/scroll-area';
import {
    Search,
    X,
    Filter,
    RotateCcw,
    CheckCircle,
    Clock,
    AlertCircle,
    FileText,
    Calendar,
} from 'lucide-vue-next';
import type { EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';

// Interfaz para los entregables que recibe el componente
interface EntregableItem {
    id: number;
    nombre: string;
    hito: string;
    hito_id?: number;
    estado: string;
    fecha_fin: string | null;
}

// Props del componente
interface Props {
    modelValue: number[]; // IDs seleccionados
    entregables: EntregableItem[]; // Lista de entregables disponibles
    label?: string;
    description?: string;
    maxSelections?: number; // Límite de selecciones
    disabled?: boolean;
    showCard?: boolean; // Mostrar envuelto en Card o no
    height?: string; // Altura del área de scroll (default: 16rem)
}

const props = withDefaults(defineProps<Props>(), {
    label: 'Entregables Relacionados',
    description: 'Selecciona los entregables del proyecto que se relacionan',
    maxSelections: 50,
    disabled: false,
    showCard: false,
    height: '16rem',
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

// Estado de filtros (estado por defecto 'pendiente' para mostrar primero los entregables pendientes)
const filtros = ref({
    search: '',
    hito: null as string | null,
    estado: 'pendiente' as string | null,
});

// Obtener hitos únicos de los entregables
const hitosUnicos = computed(() => {
    const hitosSet = new Set<string>();
    props.entregables.forEach(e => {
        if (e.hito) hitosSet.add(e.hito);
    });
    return Array.from(hitosSet).sort();
});

// Estados disponibles para filtrar
const estadosDisponibles = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'en_progreso', label: 'En Progreso' },
    { value: 'completado', label: 'Completado' },
    { value: 'cancelado', label: 'Cancelado' },
];

// Entregables filtrados según los filtros activos
const entregablesFiltrados = computed(() => {
    let resultado = [...props.entregables];

    // Filtro por búsqueda de texto
    if (filtros.value.search) {
        const searchLower = filtros.value.search.toLowerCase();
        resultado = resultado.filter(e =>
            e.nombre.toLowerCase().includes(searchLower) ||
            e.hito.toLowerCase().includes(searchLower)
        );
    }

    // Filtro por hito
    if (filtros.value.hito) {
        resultado = resultado.filter(e => e.hito === filtros.value.hito);
    }

    // Filtro por estado
    if (filtros.value.estado) {
        resultado = resultado.filter(e => e.estado === filtros.value.estado);
    }

    return resultado;
});

// Verificar si hay filtros activos
const hayFiltrosActivos = computed(() => {
    return filtros.value.search !== '' ||
           filtros.value.hito !== null ||
           filtros.value.estado !== null;
});

// Contador de seleccionados
const contadorSeleccionados = computed(() => props.modelValue.length);

// Verificar si se puede seleccionar más
const puedeSeleccionarMas = computed(() => {
    return props.modelValue.length < props.maxSelections;
});

// Verificar si un entregable está seleccionado
const isSelected = (id: number): boolean => {
    return props.modelValue.includes(id);
};

// Toggle selección de un entregable
const toggleEntregable = (id: number) => {
    if (props.disabled) return;

    let newValue = [...props.modelValue];

    if (isSelected(id)) {
        // Quitar de la selección
        newValue = newValue.filter(i => i !== id);
    } else if (puedeSeleccionarMas.value) {
        // Agregar a la selección
        newValue.push(id);
    }

    emit('update:modelValue', newValue);
};

// Limpiar todos los filtros
const limpiarFiltros = () => {
    filtros.value = {
        search: '',
        hito: null,
        estado: null,
    };
};

// Seleccionar todos los filtrados
const seleccionarTodos = () => {
    if (props.disabled) return;

    const idsDisponibles = entregablesFiltrados.value.map(e => e.id);
    const nuevaSeleccion = [...new Set([...props.modelValue, ...idsDisponibles])];

    // Respetar límite máximo
    emit('update:modelValue', nuevaSeleccion.slice(0, props.maxSelections));
};

// Deseleccionar todos los filtrados
const deseleccionarTodos = () => {
    if (props.disabled) return;

    const idsFilterados = new Set(entregablesFiltrados.value.map(e => e.id));
    const nuevaSeleccion = props.modelValue.filter(id => !idsFilterados.has(id));

    emit('update:modelValue', nuevaSeleccion);
};

// Obtener color del badge de estado
const getEstadoBadgeClass = (estado: string) => {
    const clases: Record<string, string> = {
        pendiente: 'bg-gray-100 text-gray-700 border-gray-200',
        en_progreso: 'bg-blue-100 text-blue-700 border-blue-200',
        completado: 'bg-green-100 text-green-700 border-green-200',
        cancelado: 'bg-red-100 text-red-700 border-red-200',
    };
    return clases[estado] || clases.pendiente;
};

// Obtener icono del estado
const getEstadoIcon = (estado: string) => {
    switch (estado) {
        case 'completado':
            return CheckCircle;
        case 'en_progreso':
            return Clock;
        case 'cancelado':
            return X;
        default:
            return AlertCircle;
    }
};

// Formatear fecha
const formatFecha = (fecha: string | null) => {
    if (!fecha) return null;
    try {
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: 'short',
        });
    } catch {
        return fecha;
    }
};
</script>

<template>
    <component :is="showCard ? Card : 'div'">
        <CardHeader v-if="showCard" class="pb-3">
            <CardTitle class="text-base">{{ label }}</CardTitle>
            <CardDescription v-if="description">{{ description }}</CardDescription>
        </CardHeader>

        <component :is="showCard ? CardContent : 'div'" class="space-y-3">
            <!-- Label cuando no hay Card -->
            <div v-if="!showCard && label">
                <Label>{{ label }}</Label>
                <CardDescription v-if="description" class="mt-1">
                    {{ description }}
                </CardDescription>
            </div>

            <!-- Barra de filtros -->
            <div class="space-y-3 p-3 border rounded-lg bg-muted/30">
                <!-- Fila de búsqueda y contador -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="filtros.search"
                            placeholder="Buscar entregable..."
                            class="pl-8 h-9"
                            :disabled="disabled"
                        />
                        <button
                            v-if="filtros.search"
                            @click="filtros.search = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <Badge variant="outline" class="whitespace-nowrap">
                        {{ contadorSeleccionados }} seleccionados
                    </Badge>
                </div>

                <!-- Fila de filtros por hito y estado -->
                <div class="flex flex-wrap items-end gap-2">
                    <!-- Filtro por Hito -->
                    <div class="flex-1 min-w-[140px]">
                        <Label class="text-xs text-muted-foreground mb-1 block">Hito</Label>
                        <Select
                            :model-value="filtros.hito || 'all'"
                            @update:model-value="(val) => filtros.hito = val === 'all' ? null : val"
                            :disabled="disabled"
                        >
                            <SelectTrigger class="h-9">
                                <SelectValue placeholder="Todos los hitos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los hitos</SelectItem>
                                <SelectItem
                                    v-for="hito in hitosUnicos"
                                    :key="hito"
                                    :value="hito"
                                >
                                    {{ hito }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtro por Estado -->
                    <div class="flex-1 min-w-[140px]">
                        <Label class="text-xs text-muted-foreground mb-1 block">Estado</Label>
                        <Select
                            :model-value="filtros.estado || 'all'"
                            @update:model-value="(val) => filtros.estado = val === 'all' ? null : val"
                            :disabled="disabled"
                        >
                            <SelectTrigger class="h-9">
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem
                                    v-for="estado in estadosDisponibles"
                                    :key="estado.value"
                                    :value="estado.value"
                                >
                                    {{ estado.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Botón limpiar filtros -->
                    <Button
                        v-if="hayFiltrosActivos"
                        variant="ghost"
                        size="sm"
                        @click="limpiarFiltros"
                        class="h-9"
                    >
                        <RotateCcw class="h-4 w-4 mr-1" />
                        Limpiar
                    </Button>
                </div>

                <!-- Acciones masivas -->
                <div v-if="entregablesFiltrados.length > 0" class="flex items-center gap-2 pt-2 border-t">
                    <span class="text-xs text-muted-foreground">
                        {{ entregablesFiltrados.length }} encontrados
                    </span>
                    <div class="flex-1" />
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="seleccionarTodos"
                        :disabled="disabled || !puedeSeleccionarMas"
                        class="h-7 text-xs"
                    >
                        Seleccionar todos
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="deseleccionarTodos"
                        :disabled="disabled"
                        class="h-7 text-xs"
                    >
                        Deseleccionar
                    </Button>
                </div>
            </div>

            <!-- Lista de entregables -->
            <ScrollArea :style="{ height: height }" class="border rounded-lg">
                <div class="p-2 space-y-1">
                    <!-- Mensaje cuando no hay resultados -->
                    <div
                        v-if="entregablesFiltrados.length === 0"
                        class="flex flex-col items-center justify-center py-8 text-center"
                    >
                        <FileText class="h-10 w-10 text-muted-foreground/50 mb-2" />
                        <p class="text-sm text-muted-foreground">
                            {{ hayFiltrosActivos ? 'No se encontraron entregables con los filtros aplicados' : 'No hay entregables disponibles' }}
                        </p>
                        <Button
                            v-if="hayFiltrosActivos"
                            variant="link"
                            size="sm"
                            @click="limpiarFiltros"
                            class="mt-2"
                        >
                            Limpiar filtros
                        </Button>
                    </div>

                    <!-- Lista de items -->
                    <div
                        v-for="entregable in entregablesFiltrados"
                        :key="entregable.id"
                        class="flex items-start gap-3 p-2 rounded-md hover:bg-accent/50 transition-colors cursor-pointer"
                        :class="{ 'opacity-50': disabled }"
                        @click="toggleEntregable(entregable.id)"
                    >
                        <Checkbox
                            :id="`entregable-${entregable.id}`"
                            :checked="isSelected(entregable.id)"
                            :disabled="disabled || (!isSelected(entregable.id) && !puedeSeleccionarMas)"
                            @update:checked="toggleEntregable(entregable.id)"
                            @click.stop
                            class="mt-0.5"
                        />

                        <div class="flex-1 min-w-0">
                            <span class="text-sm block">
                                {{ entregable.nombre }}
                            </span>

                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                <!-- Badge del hito -->
                                <Badge variant="secondary" class="text-xs font-normal">
                                    {{ entregable.hito }}
                                </Badge>

                                <!-- Badge del estado -->
                                <Badge
                                    variant="outline"
                                    :class="['text-xs font-normal', getEstadoBadgeClass(entregable.estado)]"
                                >
                                    <component
                                        :is="getEstadoIcon(entregable.estado)"
                                        class="h-3 w-3 mr-1"
                                    />
                                    {{ entregable.estado.replace('_', ' ') }}
                                </Badge>

                                <!-- Fecha de vencimiento -->
                                <span
                                    v-if="entregable.fecha_fin"
                                    class="text-xs text-muted-foreground flex items-center gap-1"
                                >
                                    <Calendar class="h-3 w-3" />
                                    {{ formatFecha(entregable.fecha_fin) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </ScrollArea>

            <!-- Mensaje de límite alcanzado -->
            <p
                v-if="!puedeSeleccionarMas && maxSelections < 50"
                class="text-xs text-amber-600"
            >
                Has alcanzado el límite de {{ maxSelections }} selecciones.
            </p>
        </component>
    </component>
</template>
