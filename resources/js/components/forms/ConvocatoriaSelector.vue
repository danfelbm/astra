<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Calendar, MapPin, Users, AlertCircle } from 'lucide-vue-next';
import { computed, ref, onMounted, watch } from 'vue';
import axios from 'axios';

interface Convocatoria {
    id: number;
    nombre: string;
    descripcion?: string;
    cargo: {
        id: number;
        nombre: string;
        ruta_jerarquica?: string;
    };
    periodo_electoral: {
        id: number;
        nombre: string;
    };
    fecha_apertura: string;
    fecha_cierre: string;
    estado_temporal: 'abierta' | 'futura' | 'cerrada' | 'borrador';
    ubicacion: string;
    numero_postulaciones?: number;
    territorio_id?: number;
    departamento_id?: number;
    municipio_id?: number;
    localidad_id?: number;
}

interface Props {
    modelValue?: number;
    required?: boolean;
    disabled?: boolean;
    filtrarPorUbicacion?: boolean;
    showPostulacionWarning?: boolean; // Mostrar advertencia de que se creará postulación
}

interface Emits {
    (e: 'update:modelValue', value: number | undefined): void;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    disabled: false,
    filtrarPorUbicacion: true,
    showPostulacionWarning: true,
});

const emit = defineEmits<Emits>();

const convocatorias = ref<Convocatoria[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const selectedConvocatoria = ref<string | undefined>(props.modelValue?.toString());

// Cargar convocatorias disponibles
const cargarConvocatorias = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get('/api/convocatorias/disponibles', {
            params: {
                filtrar_ubicacion: props.filtrarPorUbicacion,
                incluir_futuras: true, // Incluir convocatorias futuras también
            }
        });
        convocatorias.value = response.data;
    } catch (err) {
        error.value = 'Error al cargar las convocatorias disponibles';
        console.error('Error cargando convocatorias:', err);
    } finally {
        loading.value = false;
    }
};

// Computed para agrupar convocatorias por estado
const convocatoriasAgrupadas = computed(() => {
    const grupos = {
        abiertas: [] as Convocatoria[],
        futuras: [] as Convocatoria[],
    };
    
    convocatorias.value.forEach(conv => {
        if (conv.estado_temporal === 'abierta') {
            grupos.abiertas.push(conv);
        } else if (conv.estado_temporal === 'futura') {
            grupos.futuras.push(conv);
        }
    });
    
    return grupos;
});

// Computed para obtener la convocatoria seleccionada
const convocatoriaSeleccionada = computed(() => {
    return convocatorias.value.find(c => c.id === Number(selectedConvocatoria.value));
});

// Watch para cambios en el valor seleccionado
watch(selectedConvocatoria, (newValue) => {
    emit('update:modelValue', newValue ? Number(newValue) : undefined);
});

// Watch para cambios externos en el modelValue
watch(() => props.modelValue, (newValue) => {
    selectedConvocatoria.value = newValue?.toString();
});

// Formatear fecha
const formatearFecha = (fecha: string) => {
    const date = new Date(fecha);
    return date.toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Obtener color del estado
const getEstadoColor = (estado: string) => {
    switch (estado) {
        case 'abierta':
            return 'bg-green-100 text-green-800';
        case 'futura':
            return 'bg-blue-100 text-blue-800';
        case 'cerrada':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
};

// Obtener label del estado
const getEstadoLabel = (estado: string) => {
    switch (estado) {
        case 'abierta':
            return 'Abierta';
        case 'futura':
            return 'Próxima';
        case 'cerrada':
            return 'Cerrada';
        default:
            return 'Borrador';
    }
};

onMounted(() => {
    cargarConvocatorias();
});
</script>

<template>
    <div class="space-y-4">

        <!-- Loading -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            <p class="mt-2 text-sm text-muted-foreground">Cargando convocatorias disponibles...</p>
        </div>

        <!-- Error -->
        <Alert v-else-if="error" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Lista de convocatorias -->
        <div v-else-if="convocatorias.length > 0" class="space-y-6">
            <RadioGroup v-model="selectedConvocatoria" :disabled="disabled">
                <!-- Convocatorias Abiertas -->
                <div v-if="convocatoriasAgrupadas.abiertas.length > 0">
                    <h4 class="text-sm font-medium text-muted-foreground mb-3">
                        Convocatorias Abiertas ({{ convocatoriasAgrupadas.abiertas.length }})
                    </h4>
                    <div class="space-y-3">
                        <Card
                            v-for="convocatoria in convocatoriasAgrupadas.abiertas"
                            :key="convocatoria.id"
                            class="cursor-pointer hover:shadow-md transition-all duration-200"
                            :class="{ 
                                'ring-2 ring-primary bg-primary/5 dark:bg-primary/10': selectedConvocatoria === convocatoria.id.toString(),
                                'hover:bg-muted/50': selectedConvocatoria !== convocatoria.id.toString()
                            }"
                            @click="selectedConvocatoria = convocatoria.id.toString()"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-start gap-3">
                                    <RadioGroupItem 
                                        :value="convocatoria.id.toString()"
                                        :id="`conv-${convocatoria.id}`"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <Label 
                                                :for="`conv-${convocatoria.id}`" 
                                                class="text-base font-semibold cursor-pointer"
                                            >
                                                {{ convocatoria.nombre }}
                                            </Label>
                                            <Badge :class="getEstadoColor(convocatoria.estado_temporal)">
                                                {{ getEstadoLabel(convocatoria.estado_temporal) }}
                                            </Badge>
                                        </div>
                                        <CardDescription class="mt-1">
                                            {{ convocatoria.descripcion }}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <MapPin class="h-4 w-4" />
                                        <span>{{ convocatoria.cargo.nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <Calendar class="h-4 w-4" />
                                        <span>{{ convocatoria.periodo_electoral.nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <MapPin class="h-4 w-4" />
                                        <span>{{ convocatoria.ubicacion }}</span>
                                    </div>
                                    <div v-if="convocatoria.numero_postulaciones !== undefined" 
                                         class="flex items-center gap-2 text-muted-foreground">
                                        <Users class="h-4 w-4" />
                                        <span>{{ convocatoria.numero_postulaciones }} postulaciones</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-muted-foreground">
                                    <span class="font-medium">Cierre:</span> {{ formatearFecha(convocatoria.fecha_cierre) }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Convocatorias Futuras -->
                <div v-if="convocatoriasAgrupadas.futuras.length > 0">
                    <h4 class="text-sm font-medium text-muted-foreground mb-3">
                        Próximas Convocatorias ({{ convocatoriasAgrupadas.futuras.length }})
                    </h4>
                    <div class="space-y-3">
                        <Card
                            v-for="convocatoria in convocatoriasAgrupadas.futuras"
                            :key="convocatoria.id"
                            class="cursor-pointer hover:shadow-md transition-all duration-200 opacity-90"
                            :class="{ 
                                'ring-2 ring-primary bg-primary/5 dark:bg-primary/10': selectedConvocatoria === convocatoria.id.toString(),
                                'hover:bg-muted/50': selectedConvocatoria !== convocatoria.id.toString()
                            }"
                            @click="selectedConvocatoria = convocatoria.id.toString()"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-start gap-3">
                                    <RadioGroupItem 
                                        :value="convocatoria.id.toString()"
                                        :id="`conv-${convocatoria.id}`"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <Label 
                                                :for="`conv-${convocatoria.id}`" 
                                                class="text-base font-semibold cursor-pointer"
                                            >
                                                {{ convocatoria.nombre }}
                                            </Label>
                                            <Badge :class="getEstadoColor(convocatoria.estado_temporal)">
                                                {{ getEstadoLabel(convocatoria.estado_temporal) }}
                                            </Badge>
                                        </div>
                                        <CardDescription class="mt-1">
                                            {{ convocatoria.descripcion }}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <MapPin class="h-4 w-4" />
                                        <span>{{ convocatoria.cargo.nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <Calendar class="h-4 w-4" />
                                        <span>{{ convocatoria.periodo_electoral.nombre }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <MapPin class="h-4 w-4" />
                                        <span>{{ convocatoria.ubicacion }}</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-muted-foreground">
                                    <span class="font-medium">Apertura:</span> {{ formatearFecha(convocatoria.fecha_apertura) }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </RadioGroup>
        </div>

        <!-- Sin convocatorias -->
        <Alert v-else>
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Sin convocatorias disponibles</AlertTitle>
            <AlertDescription>
                No hay convocatorias disponibles en este momento para tu ubicación.
                Por favor, intenta más tarde o contacta al administrador.
            </AlertDescription>
        </Alert>

        <!-- Información de la convocatoria seleccionada -->
        <div v-if="convocatoriaSeleccionada && !disabled" class="mt-4 p-3 bg-muted/50 rounded-lg">
            <p class="text-sm font-medium">Convocatoria seleccionada:</p>
            <p class="text-sm text-muted-foreground">{{ convocatoriaSeleccionada.nombre }}</p>
        </div>
    </div>
</template>