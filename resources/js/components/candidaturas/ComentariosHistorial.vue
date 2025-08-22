<template>
    <div class="space-y-4">
        <!-- Header con contador -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <MessageSquare class="h-5 w-5" />
                Historial de Comentarios
                <Badge variant="secondary">{{ comentarios.length }}</Badge>
            </h3>
            <Button 
                v-if="puedeAgregar"
                @click="$emit('agregar-comentario')"
                size="sm"
                variant="outline"
            >
                <Plus class="h-4 w-4 mr-2" />
                Agregar comentario
            </Button>
        </div>

        <!-- Lista de comentarios -->
        <div v-if="comentarios.length > 0" class="space-y-3">
            <TransitionGroup name="fade">
                <div 
                    v-for="comentario in comentariosAgrupados" 
                    :key="comentario.id"
                    class="relative"
                >
                    <!-- Línea de tiempo -->
                    <div 
                        v-if="comentariosAgrupados.indexOf(comentario) < comentariosAgrupados.length - 1"
                        class="absolute left-5 top-10 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"
                    />
                    
                    <!-- Comentario -->
                    <div class="flex gap-3">
                        <!-- Icono del tipo -->
                        <div class="relative z-10 flex-shrink-0">
                            <div 
                                :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center',
                                    getColorClass(comentario.tipo)
                                ]"
                            >
                                <component 
                                    :is="getIcon(comentario.tipo_icon)" 
                                    class="h-5 w-5"
                                />
                            </div>
                        </div>
                        
                        <!-- Contenido -->
                        <div class="flex-1 pb-4">
                            <Card>
                                <CardContent class="pt-4">
                                    <!-- Metadata -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-medium text-sm">
                                                    {{ comentario.created_by?.name || 'Sistema' }}
                                                </span>
                                                <Badge :variant="getBadgeVariant(comentario.tipo)">
                                                    {{ comentario.tipo_label }}
                                                </Badge>
                                                <Badge v-if="comentario.version_candidatura" variant="outline">
                                                    v{{ comentario.version_candidatura }}
                                                </Badge>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                                <Calendar class="h-3 w-3" />
                                                <span>{{ formatearFecha(comentario.fecha) }}</span>
                                                <span>•</span>
                                                <span>{{ comentario.fecha_relativa }}</span>
                                                <span v-if="comentario.enviado_por_email" class="flex items-center gap-1">
                                                    <span>•</span>
                                                    <Mail class="h-3 w-3" />
                                                    <span>Notificado</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Comentario -->
                                    <div 
                                        class="prose prose-sm max-w-none dark:prose-invert"
                                        v-html="comentario.comentario"
                                    />
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>

        <!-- Estado vacío -->
        <div v-else class="text-center py-8">
            <MessageSquareOff class="h-12 w-12 text-gray-400 mx-auto mb-3" />
            <p class="text-sm text-muted-foreground">
                No hay comentarios históricos
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { 
    MessageSquare, 
    Plus, 
    Calendar, 
    Mail,
    MessageSquareOff,
    CheckCircle,
    XCircle,
    RotateCcw,
    MessageCircle,
    StickyNote
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

// Tipos
interface Comentario {
    id: number;
    comentario: string;
    tipo: string;
    tipo_label: string;
    tipo_color: string;
    tipo_icon: string;
    version_candidatura: number;
    enviado_por_email: boolean;
    created_by?: {
        id: number;
        name: string;
        email: string;
    };
    fecha: string;
    fecha_formateada: string;
    fecha_relativa: string;
}

interface Props {
    comentarios: Comentario[];
    puedeAgregar?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    puedeAgregar: false
});

// Emits
defineEmits<{
    'agregar-comentario': [];
}>();

// Computed
const comentariosAgrupados = computed(() => {
    // Ya vienen ordenados del backend por fecha desc
    return props.comentarios;
});

// Métodos de utilidad
const getIcon = (iconName: string) => {
    const icons: Record<string, any> = {
        'message-circle': MessageCircle,
        'check-circle': CheckCircle,
        'x-circle': XCircle,
        'rotate-ccw': RotateCcw,
        'sticky-note': StickyNote,
        'message-square': MessageSquare
    };
    return icons[iconName] || MessageSquare;
};

const getColorClass = (tipo: string) => {
    const colors: Record<string, string> = {
        'general': 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        'aprobacion': 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        'rechazo': 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        'borrador': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
        'nota_admin': 'bg-gray-100 text-gray-600 dark:bg-gray-900/30 dark:text-gray-400'
    };
    return colors[tipo] || colors['general'];
};

const getBadgeVariant = (tipo: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        'general': 'default',
        'aprobacion': 'default',
        'rechazo': 'destructive',
        'borrador': 'secondary',
        'nota_admin': 'outline'
    };
    return variants[tipo] || 'default';
};

const formatearFecha = (fecha: string) => {
    const date = new Date(fecha);
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-move {
    transition: transform 0.3s ease;
}
</style>