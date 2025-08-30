<template>
    <Badge 
        :variant="getVariant()"
        :class="[
            small ? 'text-xs px-2 py-0.5' : 'text-sm px-3 py-1',
            'font-medium'
        ]"
    >
        <component :is="getIcon()" v-if="!small" class="h-3 w-3 mr-1" />
        {{ getLabel() }}
    </Badge>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { 
    Clock, 
    PlayCircle, 
    CheckCircle, 
    XCircle,
    AlertCircle
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    estado: 'pendiente' | 'activa' | 'finalizada' | 'cancelada' | 'inactiva' | string;
    small?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    small: false
});

const getVariant = () => {
    switch (props.estado) {
        case 'activa':
            return 'success';
        case 'pendiente':
            return 'warning';
        case 'finalizada':
            return 'secondary';
        case 'cancelada':
            return 'destructive';
        case 'inactiva':
        default:
            return 'outline';
    }
};

const getIcon = () => {
    switch (props.estado) {
        case 'activa':
            return PlayCircle;
        case 'pendiente':
            return Clock;
        case 'finalizada':
            return CheckCircle;
        case 'cancelada':
            return XCircle;
        case 'inactiva':
        default:
            return AlertCircle;
    }
};

const getLabel = () => {
    switch (props.estado) {
        case 'activa':
            return 'Activa';
        case 'pendiente':
            return 'Pendiente';
        case 'finalizada':
            return 'Finalizada';
        case 'cancelada':
            return 'Cancelada';
        case 'inactiva':
            return 'Inactiva';
        default:
            return props.estado;
    }
};
</script>