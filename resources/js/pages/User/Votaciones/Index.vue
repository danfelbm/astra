<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItemType } from '@/types';
import UserLayout from "@/layouts/UserLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import { Vote, LayoutGrid, List } from 'lucide-vue-next';
import VotacionGrid from '@/components/votaciones/VotacionGrid.vue';
import VotacionFilters from '@/components/votaciones/VotacionFilters.vue';
import VoteProcessingModal from '@/components/voting/VoteProcessingModal.vue';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { Button } from '@/components/ui/button';

interface Categoria {
    id: number;
    nombre: string;
    descripcion?: string;
    activa: boolean;
}

interface Votacion {
    id: number;
    titulo: string;
    descripcion?: string;
    categoria: Categoria;
    fecha_inicio: string;
    fecha_fin: string;
    estado: 'borrador' | 'activa' | 'finalizada';
    resultados_publicos: boolean;
    created_at: string;
    votantes_count?: number;
    ya_voto: boolean;
    puede_votar: boolean;
    ha_finalizado: boolean;
    puede_ver_voto: boolean;
    resultados_visibles: boolean;
    estado_visual: 'activa' | 'finalizada' | 'pendiente' | 'inactiva';
    vote_processing: boolean;
    vote_status: string | null;
}

interface Props {
    votaciones: {
        data: Votacion[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    categorias: Categoria[];
    filters: {
        search?: string;
        advanced_filters?: string;
        mostrar_pasadas?: boolean;
        categoria_id?: number;
        estado?: string;
    };
    mostrar_pasadas: boolean;
    filterFieldsConfig: any[];
    hasProcessingVotes: boolean;
    // Props de permisos
    canVote: boolean;
    canViewResults: boolean;
    canViewOwnVote: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/miembro/dashboard' },
    { title: 'Mis Votaciones', href: '/miembro/votaciones' },
];

// Estado para el modal de procesamiento
const showProcessingModal = ref(false);
const processingVotacionId = ref<number | null>(null);
const processingCheckUrl = ref<string>('');

// Estado de vista (grid o lista)
const viewMode = ref<'grid' | 'list'>('grid');

// Helper para obtener route
const { route } = window as any;

// Polling para votos en procesamiento
const pollingInterval = ref<NodeJS.Timeout | null>(null);
const isPolling = ref(false);

// Filtros locales
const localFilters = ref({
    search: props.filters.search || '',
    categoria: props.filters.categoria_id?.toString() || '',
    estado: props.filters.estado || '',
    mostrar_pasadas: props.mostrar_pasadas || false,
});

// Aplicar filtros
const handleFiltersApplied = (filters: any) => {
    const params: any = {};
    
    if (filters.search) params.search = filters.search;
    if (filters.categoria) params.categoria_id = filters.categoria;
    if (filters.estado) params.estado = filters.estado;
    if (filters.mostrar_pasadas) params.mostrar_pasadas = filters.mostrar_pasadas;
    
    // Navegar con los nuevos filtros
    router.get(route('user.votaciones.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Función para iniciar el polling
const startPolling = () => {
    if (isPolling.value || !props.hasProcessingVotes) return;
    
    isPolling.value = true;
    
    // Por ahora simplemente recargamos la página periódicamente si hay votos procesándose
    pollingInterval.value = setInterval(() => {
        // Recargar la página para actualizar el estado
        router.reload({ 
            preserveScroll: true,
            only: ['votaciones', 'hasProcessingVotes'],
            onSuccess: () => {
                // Si ya no hay votos procesándose, detener el polling
                if (!props.hasProcessingVotes) {
                    stopPolling();
                    toast.success('Todos los votos han sido procesados');
                }
            }
        });
    }, 5000); // Cada 5 segundos
};

// Función para detener el polling
const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
    isPolling.value = false;
};

// Manejar la votación procesándose
const handleVoteProcessing = (votacionId: number, checkUrl: string) => {
    processingVotacionId.value = votacionId;
    processingCheckUrl.value = checkUrl;
    showProcessingModal.value = true;
};

// Cerrar el modal de procesamiento
const handleProcessingComplete = () => {
    showProcessingModal.value = false;
    processingVotacionId.value = null;
    processingCheckUrl.value = '';
    // Recargar la página para actualizar el estado
    router.reload({ preserveScroll: true });
};

// Iniciar polling si hay votos procesándose
onMounted(() => {
    if (props.hasProcessingVotes) {
        startPolling();
    }
});

// Limpiar al desmontar
onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <Head title="Mis Votaciones" />

    <UserLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <Vote class="h-7 w-7" />
                        Mis Votaciones
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        Participa en las votaciones disponibles para ti
                    </p>
                </div>
                
                <!-- Controles de vista -->
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="viewMode = 'grid'"
                        :class="{ 'bg-accent': viewMode === 'grid' }"
                    >
                        <LayoutGrid class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="viewMode = 'list'"
                        :class="{ 'bg-accent': viewMode === 'list' }"
                    >
                        <List class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Filtros -->
            <VotacionFilters
                :categorias="categorias"
                :initial-filters="localFilters"
                @filters-changed="handleFiltersApplied"
            />

            <!-- Grid/Lista de votaciones -->
            <VotacionGrid
                :votaciones="votaciones.data"
                :view-mode="viewMode"
                :can-vote="canVote"
                :can-view-results="canViewResults"
                @vote-processing="handleVoteProcessing"
            />

            <!-- Paginación -->
            <div v-if="votaciones.last_page > 1" class="flex justify-center mt-6">
                <nav class="flex gap-1">
                    <template v-for="link in votaciones.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            v-html="link.label"
                        />
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>

            <!-- Modal de procesamiento -->
            <VoteProcessingModal
                v-if="showProcessingModal"
                :votacion-id="processingVotacionId!"
                :check-url="processingCheckUrl"
                @complete="handleProcessingComplete"
                @close="showProcessingModal = false"
            />
        </div>
    </UserLayout>
</template>