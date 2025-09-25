<script setup lang="ts">
import { computed, ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { ScrollArea, ScrollBar } from '@modules/Core/Resources/js/components/ui/scroll-area';
import {
    Calendar,
    Clock,
    AlertTriangle,
    CheckCircle,
    Circle,
    FileText,
    XCircle,
    ChevronLeft,
    ChevronRight,
    DollarSign
} from 'lucide-vue-next';
import type { Contrato } from '@modules/Proyectos/Resources/js/types/contratos';

// Props
const props = defineProps<{
    contratos: Contrato[];
    titulo?: string;
    descripcion?: string;
    mostrarMonto?: boolean;
    compacto?: boolean;
}>();

// Estado
const mesActual = ref(new Date());
const vistaExpandida = ref(false);

// Computed
const contratosPorEstado = computed(() => {
    const estados = {
        activos: props.contratos.filter(c => c.estado === 'activo').length,
        finalizados: props.contratos.filter(c => c.estado === 'finalizado').length,
        borradores: props.contratos.filter(c => c.estado === 'borrador').length,
        cancelados: props.contratos.filter(c => c.estado === 'cancelado').length
    };
    return estados;
});

const contratosOrdenados = computed(() => {
    return [...props.contratos].sort((a, b) => {
        return new Date(a.fecha_inicio).getTime() - new Date(b.fecha_inicio).getTime();
    });
});

const rangoFechas = computed(() => {
    if (props.contratos.length === 0) return null;

    const fechas = props.contratos.flatMap(c => [
        new Date(c.fecha_inicio),
        c.fecha_fin ? new Date(c.fecha_fin) : new Date(c.fecha_inicio)
    ]);

    const minDate = new Date(Math.min(...fechas.map(f => f.getTime())));
    const maxDate = new Date(Math.max(...fechas.map(f => f.getTime())));

    return { min: minDate, max: maxDate };
});

const meses = computed(() => {
    if (!rangoFechas.value) return [];

    const resultado = [];
    const current = new Date(rangoFechas.value.min);
    current.setDate(1);

    while (current <= rangoFechas.value.max) {
        resultado.push(new Date(current));
        current.setMonth(current.getMonth() + 1);
    }

    return resultado;
});

const contratosEnMes = (mes: Date) => {
    const inicioMes = new Date(mes.getFullYear(), mes.getMonth(), 1);
    const finMes = new Date(mes.getFullYear(), mes.getMonth() + 1, 0);

    return props.contratos.filter(contrato => {
        const inicio = new Date(contrato.fecha_inicio);
        const fin = contrato.fecha_fin ? new Date(contrato.fecha_fin) : inicio;

        return (inicio <= finMes && fin >= inicioMes);
    });
};

const getEstadoConfig = (estado: string) => {
    const configs = {
        'borrador': { color: 'secondary', icon: FileText, label: 'Borrador' },
        'activo': { color: 'success', icon: CheckCircle, label: 'Activo' },
        'finalizado': { color: 'default', icon: CheckCircle, label: 'Finalizado' },
        'cancelado': { color: 'destructive', icon: XCircle, label: 'Cancelado' }
    };
    return configs[estado] || { color: 'default', icon: Circle, label: estado };
};

const getContratoPositionInMonth = (contrato: Contrato, mes: Date) => {
    const inicioMes = new Date(mes.getFullYear(), mes.getMonth(), 1);
    const finMes = new Date(mes.getFullYear(), mes.getMonth() + 1, 0);
    const diasEnMes = finMes.getDate();

    const inicioContrato = new Date(contrato.fecha_inicio);
    const finContrato = contrato.fecha_fin ? new Date(contrato.fecha_fin) : inicioContrato;

    const diaInicio = Math.max(1, inicioContrato.getDate());
    const diaFin = Math.min(diasEnMes, finContrato.getDate());

    const left = ((diaInicio - 1) / diasEnMes) * 100;
    const width = ((diaFin - diaInicio + 1) / diasEnMes) * 100;

    return { left: `${left}%`, width: `${width}%` };
};

const formatMonth = (date: Date) => {
    return date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const estaVencido = (contrato: Contrato) => {
    if (contrato.estado !== 'activo' || !contrato.fecha_fin) return false;
    return new Date(contrato.fecha_fin) < new Date();
};

const proximoVencer = (contrato: Contrato) => {
    if (contrato.estado !== 'activo' || !contrato.fecha_fin) return false;
    const diasRestantes = Math.ceil(
        (new Date(contrato.fecha_fin).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
    );
    return diasRestantes > 0 && diasRestantes <= 30;
};

// Métodos
const mesAnterior = () => {
    const nuevo = new Date(mesActual.value);
    nuevo.setMonth(nuevo.getMonth() - 1);
    mesActual.value = nuevo;
};

const mesSiguiente = () => {
    const nuevo = new Date(mesActual.value);
    nuevo.setMonth(nuevo.getMonth() + 1);
    mesActual.value = nuevo;
};

const irAHoy = () => {
    mesActual.value = new Date();
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex justify-between items-start">
                <div>
                    <CardTitle>{{ titulo || 'Timeline de Contratos' }}</CardTitle>
                    <CardDescription v-if="descripcion">
                        {{ descripcion }}
                    </CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="outline">
                        {{ contratos.length }} contratos
                    </Badge>
                    <Button
                        v-if="!compacto"
                        @click="vistaExpandida = !vistaExpandida"
                        size="sm"
                        variant="outline"
                    >
                        {{ vistaExpandida ? 'Vista compacta' : 'Vista expandida' }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Resumen de estados -->
            <div v-if="!compacto" class="grid grid-cols-4 gap-4 mb-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ contratosPorEstado.activos }}
                    </div>
                    <div class="text-xs text-muted-foreground">Activos</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">
                        {{ contratosPorEstado.finalizados }}
                    </div>
                    <div class="text-xs text-muted-foreground">Finalizados</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">
                        {{ contratosPorEstado.borradores }}
                    </div>
                    <div class="text-xs text-muted-foreground">Borradores</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">
                        {{ contratosPorEstado.cancelados }}
                    </div>
                    <div class="text-xs text-muted-foreground">Cancelados</div>
                </div>
            </div>

            <!-- Timeline -->
            <div v-if="contratos.length > 0" class="space-y-4">
                <!-- Controles de navegación -->
                <div v-if="!compacto" class="flex items-center justify-between mb-4">
                    <Button @click="mesAnterior" size="sm" variant="outline">
                        <ChevronLeft class="w-4 h-4" />
                    </Button>
                    <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-muted-foreground" />
                        <span class="font-medium">{{ formatMonth(mesActual) }}</span>
                    </div>
                    <div class="flex gap-2">
                        <Button @click="irAHoy" size="sm" variant="outline">
                            Hoy
                        </Button>
                        <Button @click="mesSiguiente" size="sm" variant="outline">
                            <ChevronRight class="w-4 h-4" />
                        </Button>
                    </div>
                </div>

                <!-- Vista de línea de tiempo -->
                <ScrollArea class="w-full whitespace-nowrap">
                    <div class="space-y-2">
                        <div
                            v-for="(contrato, index) in contratosOrdenados"
                            :key="contrato.id"
                            class="relative flex items-center gap-4 p-3 rounded-lg hover:bg-muted/50 transition-colors"
                        >
                            <!-- Línea conectora -->
                            <div
                                v-if="index < contratosOrdenados.length - 1"
                                class="absolute left-5 top-8 w-0.5 h-full bg-border"
                            />

                            <!-- Indicador de estado -->
                            <div class="relative z-10 flex-shrink-0">
                                <div
                                    :class="[
                                        'w-10 h-10 rounded-full flex items-center justify-center',
                                        {
                                            'bg-green-100': contrato.estado === 'activo',
                                            'bg-blue-100': contrato.estado === 'finalizado',
                                            'bg-gray-100': contrato.estado === 'borrador',
                                            'bg-red-100': contrato.estado === 'cancelado'
                                        }
                                    ]"
                                >
                                    <component
                                        :is="getEstadoConfig(contrato.estado).icon"
                                        :class="[
                                            'w-5 h-5',
                                            {
                                                'text-green-600': contrato.estado === 'activo',
                                                'text-blue-600': contrato.estado === 'finalizado',
                                                'text-gray-600': contrato.estado === 'borrador',
                                                'text-red-600': contrato.estado === 'cancelado'
                                            }
                                        ]"
                                    />
                                </div>
                            </div>

                            <!-- Información del contrato -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-semibold truncate">
                                            {{ contrato.nombre }}
                                        </h4>
                                        <div class="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
                                            <span class="flex items-center gap-1">
                                                <Calendar class="w-3 h-3" />
                                                {{ formatDate(contrato.fecha_inicio) }}
                                            </span>
                                            <span v-if="contrato.fecha_fin" class="flex items-center gap-1">
                                                →
                                                {{ formatDate(contrato.fecha_fin) }}
                                            </span>
                                            <Badge
                                                v-if="estaVencido(contrato)"
                                                variant="destructive"
                                                class="text-xs"
                                            >
                                                Vencido
                                            </Badge>
                                            <Badge
                                                v-else-if="proximoVencer(contrato)"
                                                variant="secondary"
                                                class="text-xs"
                                            >
                                                Próximo a vencer
                                            </Badge>
                                        </div>
                                    </div>
                                    <div v-if="mostrarMonto && contrato.monto_total" class="text-right">
                                        <div class="font-semibold">
                                            ${{ contrato.monto_total.toLocaleString() }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ contrato.moneda || 'USD' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Barra de progreso del tiempo -->
                                <div v-if="contrato.fecha_fin && !compacto" class="mt-2">
                                    <div class="w-full bg-muted rounded-full h-2">
                                        <div
                                            :class="[
                                                'h-2 rounded-full transition-all',
                                                {
                                                    'bg-green-500': contrato.estado === 'activo' && !estaVencido(contrato),
                                                    'bg-red-500': estaVencido(contrato),
                                                    'bg-orange-500': proximoVencer(contrato),
                                                    'bg-blue-500': contrato.estado === 'finalizado',
                                                    'bg-gray-400': contrato.estado === 'cancelado'
                                                }
                                            ]"
                                            :style="{
                                                width: `${Math.min(100, Math.max(0,
                                                    ((new Date().getTime() - new Date(contrato.fecha_inicio).getTime()) /
                                                    (new Date(contrato.fecha_fin).getTime() - new Date(contrato.fecha_inicio).getTime())) * 100
                                                ))}%`
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ScrollBar orientation="horizontal" />
                </ScrollArea>
            </div>

            <!-- Sin contratos -->
            <div v-else class="text-center py-8">
                <FileText class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                <p class="text-muted-foreground">No hay contratos para mostrar</p>
            </div>
        </CardContent>
    </Card>
</template>