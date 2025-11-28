<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Calendar, DollarSign, User, AlertCircle, FileText, Edit, Eye, ArrowRight } from 'lucide-vue-next';
import { Link, router } from '@inertiajs/vue3';

interface Contrato {
    id: number;
    nombre: string;
    descripcion?: string;
    fecha_inicio: string;
    fecha_fin?: string;
    estado: 'borrador' | 'activo' | 'finalizado' | 'cancelado';
    tipo: string;
    monto_total?: number;
    monto_formateado?: string;
    proyecto?: {
        id: number;
        nombre: string;
    };
    responsable?: {
        id: number;
        name: string;
    };
    dias_restantes?: number;
    porcentaje_transcurrido?: number;
    esta_vencido?: boolean;
    esta_proximo_vencer?: boolean;
}

interface Props {
    contrato: Contrato;
    showProyecto?: boolean;
    showActions?: boolean;
    viewMode?: 'admin' | 'user';
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showProyecto: true,
    showActions: true,
    viewMode: 'admin',
    compact: false,
});

// Clases de color según el estado
const estadoClasses = computed(() => {
    const clases = {
        'borrador': 'bg-gray-100 text-gray-800',
        'activo': 'bg-green-100 text-green-800',
        'finalizado': 'bg-blue-100 text-blue-800',
        'cancelado': 'bg-red-100 text-red-800',
    };
    return clases[props.contrato.estado] || 'bg-gray-100 text-gray-800';
});

// Clase para la barra de progreso
const progresoClass = computed(() => {
    if (props.contrato.estado === 'cancelado') return 'bg-red-500';
    if (props.contrato.estado === 'finalizado') return 'bg-blue-500';
    if (props.contrato.esta_vencido) return 'bg-red-500';
    if (props.contrato.esta_proximo_vencer) return 'bg-yellow-500';
    return 'bg-green-500';
});

// Formatear fecha
const formatDate = (date: string) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// URL para navegación cuando el card es cliqueable
const contratoUrl = computed(() => {
    if (props.viewMode === 'user') {
        return `/miembro/mis-contratos/${props.contrato.id}`;
    }
    return `/admin/contratos/${props.contrato.id}`;
});
</script>

<template>
    <Link
        v-if="viewMode === 'user'"
        :href="contratoUrl"
        class="block"
    >
        <Card class="hover:shadow-lg transition-all duration-300 cursor-pointer hover:border-primary">
        <CardHeader :class="compact ? 'pb-3' : ''">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <CardTitle :class="compact ? 'text-base' : 'text-lg'" class="font-semibold mb-1">
                        {{ contrato.nombre }}
                    </CardTitle>
                    <div v-if="showProyecto && contrato.proyecto" :class="compact ? 'text-xs' : 'text-sm'" class="text-gray-600 dark:text-gray-400">
                        <a
                            v-if="viewMode === 'user'"
                            :href="`/miembro/mis-proyectos/${contrato.proyecto.id}`"
                            class="hover:text-primary transition-colors"
                            @click.stop.prevent="router.visit(`/miembro/mis-proyectos/${contrato.proyecto.id}`)"
                        >
                            {{ contrato.proyecto.nombre }}
                        </a>
                        <Link
                            v-else
                            :href="`/admin/proyectos/${contrato.proyecto.id}`"
                            class="hover:text-primary transition-colors"
                        >
                            {{ contrato.proyecto.nombre }}
                        </Link>
                    </div>
                </div>
                <Badge :class="[estadoClasses, compact ? 'text-xs px-2 py-0.5' : '']">
                    {{ contrato.estado }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent :class="compact ? 'space-y-2 pt-0' : 'space-y-4'">
            <!-- Descripción -->
            <p
                v-if="contrato.descripcion && !compact"
                class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2"
            >
                {{ contrato.descripcion }}
            </p>

            <!-- Información principal -->
            <div
                v-if="viewMode === 'admin'"
                :class="[
                    'grid grid-cols-2 text-sm',
                    compact ? 'gap-2' : 'gap-3'
                ]"
            >
                <!-- Fechas -->
                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-gray-400" />
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Inicio:</span>
                        <span class="ml-1 font-medium">{{ formatDate(contrato.fecha_inicio) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-gray-400" />
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Fin:</span>
                        <span class="ml-1 font-medium">{{ formatDate(contrato.fecha_fin) }}</span>
                    </div>
                </div>

                <!-- Monto y Responsable -->
                <div v-if="contrato.monto_formateado" class="flex items-center gap-2">
                    <DollarSign class="h-4 w-4 text-gray-400" />
                    <span class="font-medium">{{ contrato.monto_formateado }}</span>
                </div>

                <div v-if="contrato.responsable" class="flex items-center gap-2">
                    <User class="h-4 w-4 text-gray-400" />
                    <span class="truncate">{{ contrato.responsable.name }}</span>
                </div>
            </div>

            <!-- Información simplificada para vista de usuario -->
            <div
                v-else-if="viewMode === 'user'"
                :class="compact ? 'text-xs space-y-1' : 'text-sm'"
            >
                <div v-if="contrato.responsable" class="flex items-center gap-2">
                    <User :class="compact ? 'h-3 w-3' : 'h-4 w-4'" class="text-gray-400" />
                    <span v-if="!compact" class="text-gray-600 dark:text-gray-400">Responsable:</span>
                    <span class="truncate font-medium">{{ contrato.responsable.name }}</span>
                </div>

                <!-- Fechas en modo compacto para user -->
                <div v-if="compact" class="flex items-center gap-3 text-xs text-gray-500">
                    <div class="flex items-center gap-1">
                        <Calendar class="h-3 w-3" />
                        <span>{{ formatDate(contrato.fecha_inicio) }}</span>
                    </div>
                    <span v-if="contrato.fecha_fin">→</span>
                    <div v-if="contrato.fecha_fin" class="flex items-center gap-1">
                        <span>{{ formatDate(contrato.fecha_fin) }}</span>
                    </div>
                </div>
            </div>

            <!-- Alertas de vencimiento -->
            <div v-if="contrato.esta_vencido || contrato.esta_proximo_vencer" class="flex items-center gap-2">
                <AlertCircle
                    :class="[
                        'h-4 w-4',
                        contrato.esta_vencido ? 'text-red-500' : 'text-yellow-500'
                    ]"
                />
                <span
                    :class="[
                        'text-sm font-medium',
                        contrato.esta_vencido ? 'text-red-600' : 'text-yellow-600'
                    ]"
                >
                    {{ contrato.esta_vencido ? 'Contrato vencido' : `Vence en ${contrato.dias_restantes} días` }}
                </span>
            </div>

            <!-- Barra de progreso -->
            <div
                v-if="!compact && contrato.estado === 'activo' && contrato.porcentaje_transcurrido !== undefined"
            >
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Progreso</span>
                    <span>{{ contrato.porcentaje_transcurrido }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        :class="progresoClass"
                        class="h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${contrato.porcentaje_transcurrido}%` }"
                    />
                </div>
            </div>

            <!-- Tipo de contrato -->
            <div class="flex items-center justify-between">
                <Badge variant="outline" :class="compact ? 'text-xs py-0.5' : 'text-xs'">
                    <FileText :class="compact ? 'h-2.5 w-2.5 mr-1' : 'h-3 w-3 mr-1'" />
                    {{ contrato.tipo }}
                </Badge>

                <!-- Acciones - Solo en admin o cuando no es compact en user -->
                <div v-if="showActions && (viewMode === 'admin' || !compact)" class="flex gap-2">
                    <Button
                        v-if="viewMode === 'user'"
                        size="sm"
                        variant="ghost"
                        @click.stop="$inertia.get(`/miembro/mis-contratos/${contrato.id}`)"
                    >
                        <Eye class="h-4 w-4" />
                    </Button>
                    <template v-else>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click.stop="$inertia.get(`/admin/contratos/${contrato.id}`)"
                        >
                            <Eye class="h-4 w-4" />
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click.stop="$inertia.get(`/admin/contratos/${contrato.id}/edit`)"
                        >
                            <Edit class="h-4 w-4" />
                        </Button>
                    </template>
                </div>
            </div>
        </CardContent>

        <!-- Botón Ver detalles cosmético para modo user -->
        <CardFooter v-if="viewMode === 'user'" class="pt-0">
            <Button variant="outline" class="w-full group" @click.stop>
                Ver detalles
                <ArrowRight class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" />
            </Button>
        </CardFooter>
    </Card>
    </Link>

    <!-- Card para admin (no cliqueable) -->
    <Card
        v-else
        class="hover:shadow-lg transition-all duration-300"
    >
        <CardHeader :class="compact ? 'pb-3' : ''">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <CardTitle :class="compact ? 'text-base' : 'text-lg'" class="font-semibold mb-1">
                        {{ contrato.nombre }}
                    </CardTitle>
                    <div v-if="showProyecto && contrato.proyecto" :class="compact ? 'text-xs' : 'text-sm'" class="text-gray-600 dark:text-gray-400">
                        <Link
                            :href="`/admin/proyectos/${contrato.proyecto.id}`"
                            class="hover:text-primary transition-colors"
                        >
                            {{ contrato.proyecto.nombre }}
                        </Link>
                    </div>
                </div>
                <Badge :class="[estadoClasses, compact ? 'text-xs px-2 py-0.5' : '']">
                    {{ contrato.estado }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent :class="compact ? 'space-y-2 pt-0' : 'space-y-4'">
            <!-- Descripción -->
            <p
                v-if="contrato.descripcion && !compact"
                class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2"
            >
                {{ contrato.descripcion }}
            </p>

            <!-- Información principal -->
            <div
                v-if="viewMode === 'admin'"
                :class="[
                    'grid grid-cols-2 text-sm',
                    compact ? 'gap-2' : 'gap-3'
                ]"
            >
                <!-- Fechas -->
                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-gray-400" />
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Inicio:</span>
                        <span class="ml-1 font-medium">{{ formatDate(contrato.fecha_inicio) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-gray-400" />
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Fin:</span>
                        <span class="ml-1 font-medium">{{ formatDate(contrato.fecha_fin) }}</span>
                    </div>
                </div>

                <!-- Monto y Responsable -->
                <div v-if="contrato.monto_formateado" class="flex items-center gap-2">
                    <DollarSign class="h-4 w-4 text-gray-400" />
                    <span class="font-medium">{{ contrato.monto_formateado }}</span>
                </div>

                <div v-if="contrato.responsable" class="flex items-center gap-2">
                    <User class="h-4 w-4 text-gray-400" />
                    <span class="truncate">{{ contrato.responsable.name }}</span>
                </div>
            </div>

            <!-- Alertas de vencimiento -->
            <div v-if="contrato.esta_vencido || contrato.esta_proximo_vencer" class="flex items-center gap-2">
                <AlertCircle
                    :class="[
                        'h-4 w-4',
                        contrato.esta_vencido ? 'text-red-500' : 'text-yellow-500'
                    ]"
                />
                <span
                    :class="[
                        'text-sm font-medium',
                        contrato.esta_vencido ? 'text-red-600' : 'text-yellow-600'
                    ]"
                >
                    {{ contrato.esta_vencido ? 'Contrato vencido' : `Vence en ${contrato.dias_restantes} días` }}
                </span>
            </div>

            <!-- Barra de progreso -->
            <div
                v-if="!compact && contrato.estado === 'activo' && contrato.porcentaje_transcurrido !== undefined"
            >
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Progreso</span>
                    <span>{{ contrato.porcentaje_transcurrido }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        :class="progresoClass"
                        class="h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${contrato.porcentaje_transcurrido}%` }"
                    />
                </div>
            </div>

            <!-- Tipo de contrato -->
            <div class="flex items-center justify-between">
                <Badge variant="outline" :class="compact ? 'text-xs py-0.5' : 'text-xs'">
                    <FileText :class="compact ? 'h-2.5 w-2.5 mr-1' : 'h-3 w-3 mr-1'" />
                    {{ contrato.tipo }}
                </Badge>

                <!-- Acciones -->
                <div v-if="showActions" class="flex gap-2">
                    <Button
                        size="sm"
                        variant="ghost"
                        @click.stop="router.visit(`/admin/contratos/${contrato.id}`)"
                    >
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click.stop="router.visit(`/admin/contratos/${contrato.id}/edit`)"
                    >
                        <Edit class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>