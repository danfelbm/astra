<template>
    <div>
        <!-- Vista Grid (por defecto) -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <VotacionCard
                v-for="votacion in votaciones"
                :key="votacion.id"
                :votacion="votacion"
                :can-vote="canVote"
                :can-view-results="canViewResults"
                :can-view-own-vote="canViewOwnVote"
                @votar="handleVotar"
                @reiniciar-urna="handleReiniciarUrna"
                @ver-voto="handleVerVoto"
                @ver-resultados="handleVerResultados"
            >
                <template #actions="{ votacion: vot }">
                    <slot name="card-actions" :votacion="vot" />
                </template>
            </VotacionCard>
        </div>

        <!-- Vista Lista (alternativa) -->
        <div v-else-if="viewMode === 'list'" class="space-y-3">
            <div
                v-for="votacion in votaciones"
                :key="votacion.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                    {{ votacion.titulo }}
                                </h3>
                                <p v-if="votacion.descripcion" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ votacion.descripcion }}
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3.5 w-3.5" />
                                        {{ formatDate(votacion.fecha_inicio) }}
                                    </span>
                                    <span>→</span>
                                    <span>{{ formatDate(votacion.fecha_fin) }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <VotacionStatus :estado="votacion.estado_visual" />
                                <Badge v-if="votacion.ya_voto" variant="success">
                                    <CheckCircle2 class="h-3 w-3 mr-1" />
                                    Votado
                                </Badge>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ml-4 flex items-center gap-2">
                        <slot name="list-actions" :votacion="votacion">
                            <!-- Botones dinámicos según estado de sesión de urna (vista lista) -->
                            
                            <!-- Sin sesión de urna: Mostrar botón "Votar" normal -->
                            <Button
                                v-if="!votacion.urna_session_status && votacion.puede_votar && canVote"
                                @click="handleVotar(votacion)"
                                size="sm"
                            >
                                <Vote class="h-4 w-4 mr-1" />
                                Votar
                            </Button>
                            
                            <!-- Sesión activa: Mostrar botón "Volver a la Urna" -->
                            <Button
                                v-if="votacion.urna_session_status === 'active' && votacion.puede_votar && canVote"
                                @click="handleVotar(votacion)"
                                size="sm"
                                variant="secondary"
                            >
                                <Clock class="h-4 w-4 mr-1" />
                                Volver
                            </Button>
                            
                            <!-- Sesión expirada: Mostrar botón "Reiniciar Urna" -->
                            <Button
                                v-if="votacion.urna_session_status === 'expired' && votacion.puede_votar && canVote"
                                @click="handleReiniciarUrna(votacion)"
                                size="sm"
                                variant="outline"
                            >
                                <RefreshCw class="h-4 w-4 mr-1" />
                                Reiniciar
                            </Button>
                            
                            <Button
                                v-if="votacion.puede_ver_voto && canViewOwnVote"
                                @click="handleVerVoto(votacion)"
                                variant="outline"
                                size="sm"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                            
                            <Button
                                v-if="votacion.resultados_visibles && canViewResults"
                                @click="handleVerResultados(votacion)"
                                variant="outline"
                                size="sm"
                            >
                                <BarChart2 class="h-4 w-4" />
                            </Button>
                        </slot>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado vacío -->
        <div v-if="!votaciones || votaciones.length === 0" class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <Inbox class="h-12 w-12" />
            </div>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                No hay votaciones
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ emptyMessage || 'No se encontraron votaciones disponibles.' }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import VotacionCard from './VotacionCard.vue';
import VotacionStatus from './VotacionStatus.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Calendar, 
    CheckCircle2, 
    Vote, 
    Eye, 
    BarChart2, 
    Inbox,
    Clock,
    RefreshCw 
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { router } from '@inertiajs/vue3';

interface Props {
    votaciones: any[];
    viewMode?: 'grid' | 'list';
    canVote?: boolean;
    canViewResults?: boolean;
    canViewOwnVote?: boolean;
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    viewMode: 'grid',
    canVote: true,
    canViewResults: true,
    canViewOwnVote: true
});

const emit = defineEmits<{
    votar: [votacion: any];
    'reiniciar-urna': [votacion: any];
    'ver-voto': [votacion: any];
    'ver-resultados': [votacion: any];
}>();

// Formatear fecha
const formatDate = (date: string) => {
    return format(new Date(date), "dd/MM/yyyy HH:mm", { locale: es });
};

// Handlers para las acciones
const handleVotar = (votacion: any) => {
    emit('votar', votacion);
    // Navegación por defecto si no hay listener
    if (!emit('votar')) {
        router.visit(route('user.votaciones.votar', votacion.id));
    }
};

const handleReiniciarUrna = (votacion: any) => {
    emit('reiniciar-urna', votacion);
    // Navegación por defecto si no hay listener - usaremos la nueva ruta de reinicio con POST
    if (!emit('reiniciar-urna')) {
        router.visit(route('user.votaciones.reset-urna', votacion.id), {
            method: 'post'
        });
    }
};

const handleVerVoto = (votacion: any) => {
    emit('ver-voto', votacion);
    // Navegación por defecto si no hay listener
    if (!emit('ver-voto')) {
        router.visit(route('user.votaciones.mi-voto', votacion.id));
    }
};

const handleVerResultados = (votacion: any) => {
    emit('ver-resultados', votacion);
    // Navegación por defecto si no hay listener
    if (!emit('ver-resultados')) {
        router.visit(route('user.votaciones.resultados', votacion.id));
    }
};
</script>