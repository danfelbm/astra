<template>
    <div 
        class="relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200"
        :class="{ 'opacity-75': votacion.vote_processing }"
    >
        <!-- Indicador de procesamiento -->
        <div v-if="votacion.vote_processing" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 rounded-lg flex items-center justify-center z-10">
            <div class="flex flex-col items-center space-y-2">
                <Loader2 class="h-8 w-8 animate-spin text-primary-600" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Firmando digitalmente...
                </span>
            </div>
        </div>

        <!-- Encabezado con estado -->
        <div class="flex justify-between items-start mb-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">
                {{ votacion.titulo }}
            </h3>
            <VotacionStatus :estado="votacion.estado_visual" :small="true" />
        </div>

        <!-- Categoría -->
        <div v-if="votacion.categoria" class="mb-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                {{ votacion.categoria.nombre }}
            </span>
        </div>

        <!-- Descripción -->
        <p v-if="votacion.descripcion" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
            {{ votacion.descripcion }}
        </p>

        <!-- Fechas -->
        <div class="text-sm mb-4">
            <!-- Horario oficial de la votación -->
            <div class="space-y-1 mb-2">
                <div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-medium">
                    <Calendar class="h-4 w-4" />
                    <span>Horario oficial ({{ getTimezoneAbbr(votacionTimezone) }}):</span>
                </div>
                <div class="ml-5 text-gray-600 dark:text-gray-400">
                    <div>Inicio: {{ formatVotacionDate(votacion.fecha_inicio) }}</div>
                    <div>Fin: {{ formatVotacionDate(votacion.fecha_fin) }}</div>
                </div>
            </div>
            
            <!-- Horario local (solo si es diferente) -->
            <div v-if="isLocalDifferent(votacion.fecha_inicio)" class="space-y-1">
                <div class="flex items-center gap-1 text-gray-500 dark:text-gray-500 text-xs">
                    <Globe class="h-3 w-3" />
                    <span>Tu hora local:</span>
                </div>
                <div class="ml-5 text-gray-500 dark:text-gray-500 text-xs">
                    <div>Inicio: {{ formatLocalDate(votacion.fecha_inicio) }}</div>
                    <div>Fin: {{ formatLocalDate(votacion.fecha_fin) }}</div>
                </div>
            </div>
        </div>

        <!-- Indicadores de estado -->
        <div class="flex flex-wrap gap-2 mb-4">
            <Badge v-if="votacion.ya_voto" variant="success">
                <CheckCircle2 class="h-3 w-3 mr-1" />
                Ya votaste
            </Badge>
            <Badge v-if="votacion.resultados_visibles" variant="secondary">
                <BarChart2 class="h-3 w-3 mr-1" />
                Resultados disponibles
            </Badge>
            <Badge v-if="votacion.votantes_count" variant="outline">
                <Users class="h-3 w-3 mr-1" />
                {{ votacion.votantes_count }} participantes
            </Badge>
        </div>

        <!-- Acciones -->
        <div class="flex gap-2">
            <slot name="actions" :votacion="votacion">
                <!-- Acciones por defecto - Botones dinámicos según estado de sesión de urna -->
                
                <!-- Sin sesión de urna: Mostrar botón "Votar" normal -->
                <Button
                    v-if="!votacion.urna_session_status && votacion.puede_votar && canVote"
                    @click="$emit('votar', votacion)"
                    size="sm"
                    class="flex-1"
                >
                    <Vote class="h-4 w-4 mr-1" />
                    Votar
                </Button>
                
                <!-- Sesión activa: Mostrar botón "Volver a la Urna" -->
                <Button
                    v-if="votacion.urna_session_status === 'active' && votacion.puede_votar && canVote"
                    @click="$emit('votar', votacion)"
                    size="sm"
                    class="flex-1"
                    variant="secondary"
                >
                    <Clock class="h-4 w-4 mr-1" />
                    Volver a la Urna
                </Button>
                
                <!-- Sesión expirada: Mostrar botón "Reiniciar Urna" -->
                <Button
                    v-if="votacion.urna_session_status === 'expired' && votacion.puede_votar && canVote"
                    @click="$emit('reiniciar-urna', votacion)"
                    size="sm"
                    class="flex-1"
                    variant="outline"
                >
                    <RefreshCw class="h-4 w-4 mr-1" />
                    Reiniciar Urna
                </Button>
                
                <Button
                    v-if="votacion.puede_ver_voto && canViewOwnVote"
                    @click="$emit('ver-voto', votacion)"
                    variant="outline"
                    size="sm"
                    class="flex-1"
                >
                    <Eye class="h-4 w-4 mr-1" />
                    Mi voto
                </Button>
                
                <Button
                    v-if="votacion.resultados_visibles && canViewResults"
                    @click="$emit('ver-resultados', votacion)"
                    variant="outline"
                    size="sm"
                    class="flex-1"
                >
                    <BarChart2 class="h-4 w-4 mr-1" />
                    Resultados
                </Button>
            </slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import VotacionStatus from './VotacionStatus.vue';
import { 
    Calendar, 
    Clock, 
    CheckCircle2, 
    BarChart2, 
    Users, 
    Vote, 
    Eye,
    Loader2,
    Globe,
    RefreshCw
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { formatInTimeZone } from 'date-fns-tz';
import { computed } from 'vue';

interface Props {
    votacion: any;
    canVote?: boolean;
    canViewResults?: boolean;
    canViewOwnVote?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canVote: true,
    canViewResults: true,
    canViewOwnVote: true
});

defineEmits<{
    votar: [votacion: any];
    'ver-voto': [votacion: any];
    'ver-resultados': [votacion: any];
}>();

// Obtener el timezone de la votación (por defecto America/Bogota)
const votacionTimezone = computed(() => props.votacion.timezone || 'America/Bogota');

// Obtener el nombre corto del timezone
const getTimezoneAbbr = (timezone: string) => {
    const abbrs: Record<string, string> = {
        'America/Bogota': 'COT',
        'America/New_York': 'EST',
        'America/Los_Angeles': 'PST',
        'Europe/Madrid': 'CET',
        'America/Mexico_City': 'CST',
    };
    return abbrs[timezone] || timezone;
};

// Formatear fecha en la zona horaria de la votación
const formatVotacionDate = (date: string) => {
    return formatInTimeZone(date, votacionTimezone.value, "dd/MM/yyyy HH:mm", { locale: es });
};

// Formatear fecha en hora local del usuario
const formatLocalDate = (date: string) => {
    return format(new Date(date), "dd/MM/yyyy HH:mm", { locale: es });
};

// Detectar si la hora local es diferente a la hora de la votación
const isLocalDifferent = (date: string) => {
    return formatVotacionDate(date) !== formatLocalDate(date);
};
</script>