<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { RadioGroup, RadioGroupItem } from '@modules/Core/Resources/js/components/ui/radio-group';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { Input } from '@modules/Core/Resources/js/components/ui/input';
import { 
    Users, Search, Filter, MapPin, Calendar, 
    Shield, Info, CheckCircle2, AlertCircle 
} from 'lucide-vue-next';

interface Segment {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'geografico' | 'rol' | 'personalizado' | 'dinamico';
    filtros?: any;
    count?: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    modelValue?: number | null;
    segments: Segment[];
    required?: boolean;
    showPreview?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    required: false,
    showPreview: true
});

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    'change': [segment: Segment | null];
}>();

const selectedSegmentId = ref(props.modelValue);
const searchQuery = ref('');
const filterType = ref<string>('all');

// Segmento seleccionado
const selectedSegment = computed(() => {
    return props.segments.find(s => s.id === selectedSegmentId.value) || null;
});

// Segmentos filtrados
const filteredSegments = computed(() => {
    let segments = props.segments;
    
    // Filtrar por búsqueda
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        segments = segments.filter(s => 
            s.nombre.toLowerCase().includes(query) ||
            s.descripcion?.toLowerCase().includes(query)
        );
    }
    
    // Filtrar por tipo
    if (filterType.value !== 'all') {
        segments = segments.filter(s => s.tipo === filterType.value);
    }
    
    return segments;
});

// Estadísticas de segmentos
const segmentStats = computed(() => {
    const stats = {
        total: props.segments.length,
        geografico: 0,
        rol: 0,
        personalizado: 0,
        dinamico: 0,
        totalUsuarios: 0
    };
    
    props.segments.forEach(segment => {
        stats[segment.tipo]++;
        stats.totalUsuarios += segment.count || 0;
    });
    
    return stats;
});

// Seleccionar segmento
const selectSegment = (segmentId: number) => {
    selectedSegmentId.value = segmentId;
    const segment = props.segments.find(s => s.id === segmentId) || null;
    emit('update:modelValue', segmentId);
    emit('change', segment);
};

// Obtener icono del tipo
const getTypeIcon = (tipo: string) => {
    const icons: Record<string, any> = {
        geografico: MapPin,
        rol: Shield,
        personalizado: Filter,
        dinamico: Calendar
    };
    return icons[tipo] || Users;
};

// Obtener color del tipo
const getTypeColor = (tipo: string) => {
    const colors: Record<string, string> = {
        geografico: 'text-blue-600',
        rol: 'text-purple-600',
        personalizado: 'text-green-600',
        dinamico: 'text-orange-600'
    };
    return colors[tipo] || 'text-gray-600';
};

// Obtener label del tipo
const getTypeLabel = (tipo: string) => {
    const labels: Record<string, string> = {
        geografico: 'Geográfico',
        rol: 'Por Rol',
        personalizado: 'Personalizado',
        dinamico: 'Dinámico'
    };
    return labels[tipo] || tipo;
};

// Formatear número
const formatNumber = (num?: number): string => {
    if (!num) return '0';
    if (num >= 1000000) {
        return `${(num / 1000000).toFixed(1)}M`;
    }
    if (num >= 1000) {
        return `${(num / 1000).toFixed(1)}K`;
    }
    return num.toLocaleString();
};

// Watch para cambios externos
watch(() => props.modelValue, (value) => {
    selectedSegmentId.value = value;
});
</script>

<template>
    <div class="space-y-4">
        <!-- Header con estadísticas -->
        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <CardTitle class="text-base">Seleccionar Segmento</CardTitle>
                    <Badge variant="outline">
                        {{ segmentStats.total }} segmentos disponibles
                    </Badge>
                </div>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <MapPin class="w-4 h-4 text-blue-600" />
                        <span>{{ segmentStats.geografico }} Geográficos</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Shield class="w-4 h-4 text-purple-600" />
                        <span>{{ segmentStats.rol }} Por Rol</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Filter class="w-4 h-4 text-green-600" />
                        <span>{{ segmentStats.personalizado }} Personalizados</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-orange-600" />
                        <span>{{ segmentStats.dinamico }} Dinámicos</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Filtros -->
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Buscar segmento..."
                        class="pl-8"
                    />
                </div>
            </div>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :class="{ 'bg-accent': filterType === 'all' }"
                    @click="filterType = 'all'"
                >
                    Todos
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :class="{ 'bg-accent': filterType === 'geografico' }"
                    @click="filterType = 'geografico'"
                >
                    <MapPin class="w-4 h-4 mr-1" />
                    Geográfico
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :class="{ 'bg-accent': filterType === 'rol' }"
                    @click="filterType = 'rol'"
                >
                    <Shield class="w-4 h-4 mr-1" />
                    Rol
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :class="{ 'bg-accent': filterType === 'personalizado' }"
                    @click="filterType = 'personalizado'"
                >
                    <Filter class="w-4 h-4 mr-1" />
                    Personalizado
                </Button>
            </div>
        </div>

        <!-- Lista de segmentos -->
        <Card>
            <CardContent class="p-2">
                <RadioGroup v-model:modelValue="selectedSegmentId" @update:modelValue="selectSegment">
                    <div class="space-y-2 max-h-96 overflow-y-auto p-2">
                        <div
                            v-for="segment in filteredSegments"
                            :key="segment.id"
                            class="flex items-start space-x-3 p-3 rounded-lg border hover:bg-accent cursor-pointer"
                            :class="{ 'bg-accent border-primary': selectedSegmentId === segment.id }"
                        >
                            <RadioGroupItem :value="segment.id" :id="`segment-${segment.id}`" />
                            <Label :for="`segment-${segment.id}`" class="flex-1 cursor-pointer">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <component
                                                :is="getTypeIcon(segment.tipo)"
                                                :class="['w-4 h-4', getTypeColor(segment.tipo)]"
                                            />
                                            <span class="font-medium">{{ segment.nombre }}</span>
                                            <Badge variant="outline" class="ml-2">
                                                {{ getTypeLabel(segment.tipo) }}
                                            </Badge>
                                        </div>
                                        <p v-if="segment.descripcion" class="text-sm text-muted-foreground mt-1">
                                            {{ segment.descripcion }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold">{{ formatNumber(segment.count) }}</p>
                                        <p class="text-xs text-muted-foreground">usuarios</p>
                                    </div>
                                </div>
                            </Label>
                        </div>
                        
                        <div v-if="filteredSegments.length === 0" class="text-center py-8 text-muted-foreground">
                            No se encontraron segmentos
                        </div>
                    </div>
                </RadioGroup>
            </CardContent>
        </Card>

        <!-- Preview del segmento seleccionado -->
        <Card v-if="showPreview && selectedSegment">
            <CardHeader>
                <CardTitle class="text-base flex items-center gap-2">
                    <CheckCircle2 class="w-4 h-4 text-green-600" />
                    Segmento Seleccionado
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div>
                        <h4 class="font-medium flex items-center gap-2">
                            <component
                                :is="getTypeIcon(selectedSegment.tipo)"
                                :class="['w-4 h-4', getTypeColor(selectedSegment.tipo)]"
                            />
                            {{ selectedSegment.nombre }}
                        </h4>
                        <p v-if="selectedSegment.descripcion" class="text-sm text-muted-foreground mt-1">
                            {{ selectedSegment.descripcion }}
                        </p>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-muted rounded-md">
                        <div>
                            <p class="text-sm text-muted-foreground">Total de destinatarios</p>
                            <p class="text-2xl font-bold">{{ formatNumber(selectedSegment.count) }}</p>
                        </div>
                        <Users class="w-8 h-8 text-muted-foreground" />
                    </div>
                    
                    <Alert>
                        <Info class="h-4 w-4" />
                        <AlertDescription>
                            Los usuarios de este segmento recibirán la campaña según los criterios definidos.
                            El número de destinatarios puede variar al momento del envío.
                        </AlertDescription>
                    </Alert>
                </div>
            </CardContent>
        </Card>

        <!-- Mensaje de requerido -->
        <Alert v-if="required && !selectedSegmentId" variant="warning">
            <AlertCircle class="h-4 w-4" />
            <AlertDescription>
                Debes seleccionar un segmento para continuar
            </AlertDescription>
        </Alert>
    </div>
</template>