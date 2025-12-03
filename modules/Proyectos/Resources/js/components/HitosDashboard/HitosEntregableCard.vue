<script setup lang="ts">
/**
 * HitosEntregableCard - Card atómico para mostrar un entregable
 * Soporta variantes: default, compact, kanban
 */
import { computed } from 'vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import {
    Calendar, Clock, CheckCircle, AlertCircle, XCircle,
    User, Edit, Trash2, ChevronDown, ChevronRight, Users, Flag, Eye, MessageSquare, Activity
} from 'lucide-vue-next';
import type { Entregable, EstadoEntregable } from '@modules/Proyectos/Resources/js/types/hitos';
import { useEntregablesView, ESTADO_CONFIG, PRIORIDAD_CONFIG } from '@modules/Proyectos/Resources/js/composables/useEntregablesView';
import { ref, toRef } from 'vue';

// Props
interface Props {
    entregable: Entregable;
    canEdit?: boolean;
    canDelete?: boolean;
    canComplete?: boolean;
    expanded?: boolean;
    draggable?: boolean;
    variant?: 'default' | 'compact' | 'kanban';
}

const props = withDefaults(defineProps<Props>(), {
    canEdit: false,
    canDelete: false,
    canComplete: false,
    expanded: false,
    draggable: false,
    variant: 'default',
});

// Emits
const emit = defineEmits<{
    'toggle-expand': [];
    'view': [];
    'edit': [];
    'delete': [];
    'complete': [];
    'change-status': [nuevoEstado: EstadoEntregable];
    'show-comentarios': [];
    'show-actividad': [];
}>();

// Composable (con array vacío ya que solo usamos utilidades)
const entregablesRef = ref<Entregable[]>([]);
const {
    getEstadoColor,
    getPrioridadColor,
    formatDate,
    formatDateTime,
    getDiasRestantes,
    isVencido,
} = useEntregablesView(entregablesRef);

// Computed
const estadoConfig = computed(() => ESTADO_CONFIG[props.entregable.estado]);
const prioridadConfig = computed(() => PRIORIDAD_CONFIG[props.entregable.prioridad]);
const diasRestantes = computed(() => getDiasRestantes(props.entregable.fecha_fin));
const vencido = computed(() => isVencido(props.entregable.fecha_fin, props.entregable.estado));
const isCompletado = computed(() => props.entregable.estado === 'completado');
const isCancelado = computed(() => props.entregable.estado === 'cancelado');

// Icono según estado
const estadoIcon = computed(() => {
    switch (props.entregable.estado) {
        case 'completado': return CheckCircle;
        case 'en_progreso': return Clock;
        case 'cancelado': return XCircle;
        default: return AlertCircle;
    }
});

// Clases para el card según estado
const cardClasses = computed(() => {
    const base = 'transition-all duration-150';
    const borderByEstado: Record<string, string> = {
        en_progreso: 'border-l-4 border-l-blue-500',
        completado: 'border-l-4 border-l-green-500 bg-green-50/30',
        cancelado: 'border-l-4 border-l-red-500 bg-red-50/30',
    };

    const classes = [base];
    if (borderByEstado[props.entregable.estado]) {
        classes.push(borderByEstado[props.entregable.estado]);
    }
    if (props.draggable) {
        classes.push('cursor-grab active:cursor-grabbing hover:shadow-md');
    }
    return classes.join(' ');
});
</script>

<template>
    <Card
        :class="cardClasses"
        :data-id="entregable.id"
        :data-estado="entregable.estado"
    >
        <CardContent :class="variant === 'kanban' ? 'p-3' : 'p-4'">
            <!-- Encabezado del entregable -->
            <div class="flex items-start gap-2">
                <!-- Botón expandir (solo en default/compact) -->
                <Button
                    v-if="variant !== 'kanban'"
                    variant="ghost"
                    size="sm"
                    class="h-6 w-6 p-0 flex-shrink-0"
                    @click="emit('toggle-expand')"
                >
                    <ChevronRight v-if="!expanded" class="h-4 w-4" />
                    <ChevronDown v-else class="h-4 w-4" />
                </Button>

                <!-- Icono de estado (solo kanban) -->
                <component
                    v-if="variant === 'kanban'"
                    :is="estadoIcon"
                    class="h-4 w-4 flex-shrink-0 mt-0.5"
                    :class="estadoConfig.color"
                />

                <!-- Contenido principal -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4
                            class="font-medium truncate"
                            :class="{
                                'line-through text-muted-foreground': isCompletado,
                                'text-sm': variant === 'kanban'
                            }"
                        >
                            {{ entregable.nombre }}
                        </h4>

                        <!-- Badges (solo en default/compact) -->
                        <template v-if="variant !== 'kanban'">
                            <Badge
                                :class="getPrioridadColor(entregable.prioridad)"
                                variant="outline"
                                class="text-[10px] px-1.5"
                            >
                                {{ prioridadConfig.label }}
                            </Badge>
                            <Badge :class="getEstadoColor(entregable.estado)" class="text-[10px] px-1.5">
                                {{ estadoConfig.label }}
                            </Badge>
                        </template>

                        <!-- Badge prioridad alta (solo kanban) -->
                        <Badge
                            v-if="variant === 'kanban' && entregable.prioridad === 'alta'"
                            class="bg-red-100 text-red-800 text-[10px] px-1"
                        >
                            <Flag class="h-3 w-3" />
                        </Badge>
                    </div>

                    <!-- Info mínima en kanban -->
                    <div v-if="variant === 'kanban'" class="mt-1.5 flex items-center gap-2 text-xs text-muted-foreground">
                        <span v-if="entregable.fecha_fin" class="flex items-center gap-1" :class="{ 'text-red-600': vencido }">
                            <Calendar class="h-3 w-3" />
                            {{ formatDate(entregable.fecha_fin) }}
                        </span>
                        <span v-if="entregable.responsable" class="flex items-center gap-1 truncate">
                            <User class="h-3 w-3" />
                            {{ entregable.responsable.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Botones Ver/Editar/Comentarios (solo kanban) -->
            <div v-if="variant === 'kanban'" class="flex gap-1 mt-2 pt-2 border-t">
                <Button
                    variant="outline"
                    size="sm"
                    class="flex-1 h-7 text-xs"
                    @click.stop="emit('view')"
                >
                    <Eye class="h-3 w-3 mr-1" />
                    Ver detalles
                </Button>
                <!-- Botones de acción solo para gestores/responsables -->
                <Button
                    v-if="canComplete && !isCompletado && !isCancelado"
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click.stop="emit('complete')"
                >
                    <CheckCircle class="h-3 w-3 mr-1" />
                    Completar
                </Button>
                <Button
                    v-if="canEdit && entregable.estado === 'pendiente'"
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click.stop="emit('change-status', 'en_progreso')"
                >
                    <Clock class="h-3 w-3 mr-1" />
                    En progreso
                </Button>
                <Button
                    v-if="canEdit"
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click.stop="emit('edit')"
                >
                    <Edit class="h-3 w-3 mr-1" />
                    Editar
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-7 px-2 text-xs"
                    @click.stop="emit('show-comentarios')"
                    title="Comentarios"
                >
                    <MessageSquare class="h-3 w-3" />
                    <span v-if="entregable.comentarios_count && entregable.comentarios_count > 0" class="ml-1">
                        {{ entregable.comentarios_count }}
                    </span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-7 px-2 text-xs"
                    @click.stop="emit('show-actividad')"
                    title="Actividad"
                >
                    <Activity class="h-3 w-3" />
                </Button>
            </div>

            <!-- Detalles expandidos (solo default/compact cuando expanded) -->
            <div v-if="expanded && variant !== 'kanban'" class="ml-8 mt-3 space-y-3">
                <p v-if="entregable.descripcion" class="text-sm text-muted-foreground">
                    {{ entregable.descripcion }}
                </p>

                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-1 text-muted-foreground">
                        <Calendar class="h-4 w-4" />
                        <span>{{ formatDate(entregable.fecha_inicio) }} - {{ formatDate(entregable.fecha_fin) }}</span>
                    </div>
                    <div v-if="entregable.responsable" class="flex items-center gap-1 text-muted-foreground">
                        <User class="h-4 w-4" />
                        <span>{{ entregable.responsable.name }}</span>
                    </div>
                    <div
                        v-if="diasRestantes"
                        class="flex items-center gap-1"
                        :class="{ 'text-red-600': vencido }"
                    >
                        <Clock class="h-4 w-4" />
                        <span>{{ diasRestantes }}</span>
                    </div>
                </div>

                <!-- Usuarios asignados -->
                <div v-if="entregable.usuarios && entregable.usuarios.length > 0" class="flex items-center gap-2">
                    <Users class="h-4 w-4 text-muted-foreground" />
                    <div class="flex gap-1 flex-wrap">
                        <Badge v-for="usuario in entregable.usuarios" :key="usuario.id" variant="secondary" class="text-xs">
                            {{ usuario.name }}
                        </Badge>
                    </div>
                </div>

                <!-- Info de completado -->
                <template v-if="isCompletado">
                    <div class="text-sm text-muted-foreground space-y-1">
                        <div v-if="entregable.completado_at" class="flex items-center gap-1">
                            <Clock class="h-3 w-3" />
                            <span>Completado: {{ formatDateTime(entregable.completado_at) }}</span>
                        </div>
                        <div v-if="entregable.completado_por_usuario" class="flex items-center gap-1">
                            <User class="h-3 w-3" />
                            <span>Por: {{ entregable.completado_por_usuario.name }}</span>
                        </div>
                        <p v-if="entregable.observaciones_estado" class="italic mt-2">
                            "{{ entregable.observaciones_estado }}"
                        </p>
                    </div>
                </template>
            </div>

            <!-- Acciones (siempre visible en variante default) -->
            <div
                v-if="variant === 'default'"
                class="flex flex-wrap items-center gap-1 mt-3 pt-3 border-t"
            >
                <!-- Botones siempre visibles para todos los usuarios -->
                <Button
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click="emit('view')"
                >
                    <Eye class="h-3 w-3 mr-1" />
                    Ver detalles
                </Button>

                <!-- Acciones de estado solo para gestores/responsables -->
                <template v-if="!isCompletado && !isCancelado">
                    <Button
                        v-if="canComplete"
                        variant="outline"
                        size="sm"
                        class="h-7 text-xs"
                        @click="emit('complete')"
                    >
                        <CheckCircle class="h-3 w-3 mr-1" />
                        Completar
                    </Button>
                    <Button
                        v-if="canEdit && entregable.estado === 'pendiente'"
                        variant="outline"
                        size="sm"
                        class="h-7 text-xs"
                        @click="emit('change-status', 'en_progreso')"
                    >
                        <Clock class="h-3 w-3 mr-1" />
                        En progreso
                    </Button>
                </template>

                <template v-else-if="isCompletado">
                    <Button
                        v-if="canEdit"
                        variant="outline"
                        size="sm"
                        class="h-7 text-xs"
                        @click="emit('change-status', 'pendiente')"
                    >
                        <AlertCircle class="h-3 w-3 mr-1" />
                        Reabrir
                    </Button>
                </template>

                <!-- Editar solo para gestores/responsables -->
                <Button
                    v-if="canEdit"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    @click="emit('edit')"
                >
                    <Edit class="h-3 w-3 mr-1" />
                    Editar
                </Button>

                <!-- Comentarios y Actividad siempre visibles -->
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    @click="emit('show-comentarios')"
                >
                    <MessageSquare class="h-3 w-3 mr-1" />
                    Comentarios
                    <Badge
                        v-if="entregable.comentarios_count && entregable.comentarios_count > 0"
                        variant="secondary"
                        class="ml-1 text-xs"
                    >
                        {{ entregable.comentarios_count }}
                    </Badge>
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    @click="emit('show-actividad')"
                >
                    <Activity class="h-3 w-3 mr-1" />
                    Actividad
                </Button>

                <!-- Eliminar solo para usuarios con permiso -->
                <Button
                    v-if="canDelete"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs text-red-600 hover:text-red-700 hover:bg-red-50"
                    @click="emit('delete')"
                >
                    <Trash2 class="h-3 w-3 mr-1" />
                    Eliminar
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
