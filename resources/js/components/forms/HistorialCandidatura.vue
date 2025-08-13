<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { type CandidaturaHistorial, type HistorialPaginado } from '@/types';
import { ChevronDown, ChevronRight, Clock, Eye, User } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    candidaturaId: number;
    versionActual: number;
    isAdmin?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isAdmin: false
});

// Estado
const loading = ref(true);
const error = ref<string | null>(null);
const historial = ref<HistorialPaginado | null>(null);
const expandedEntries = ref<Set<number>>(new Set());
const selectedEntry = ref<CandidaturaHistorial | null>(null);
const showDetailModal = ref(false);

// Computadas
const hayHistorial = computed(() => historial.value && historial.value.data.length > 0);

const historialesMostrados = computed(() => {
    if (!historial.value) return [];
    return historial.value.data;
});

// Métodos
const cargarHistorial = async (page: number = 1) => {
    try {
        loading.value = true;
        error.value = null;
        
        const url = props.isAdmin 
            ? `/admin/candidaturas/${props.candidaturaId}/historial?page=${page}`
            : `/candidaturas/${props.candidaturaId}/historial?page=${page}`;
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Error al cargar el historial');
        }

        historial.value = await response.json();
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Error desconocido';
        console.error('Error cargando historial:', err);
    } finally {
        loading.value = false;
    }
};

const toggleExpanded = (entryId: number) => {
    if (expandedEntries.value.has(entryId)) {
        expandedEntries.value.delete(entryId);
    } else {
        expandedEntries.value.add(entryId);
    }
};

const verDetalle = (entry: CandidaturaHistorial) => {
    selectedEntry.value = entry;
    showDetailModal.value = true;
};

const formatearCampo = (campo: string, valor: any): string => {
    if (valor === null || valor === undefined) {
        return 'No especificado';
    }
    
    if (Array.isArray(valor)) {
        return valor.join(', ');
    }
    
    if (typeof valor === 'object') {
        return JSON.stringify(valor, null, 2);
    }
    
    return String(valor);
};

// Lifecycle
onMounted(() => {
    cargarHistorial();
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Clock class="h-5 w-5" />
                Historial de Cambios
            </CardTitle>
        </CardHeader>
        <CardContent>
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="flex items-center space-x-3">
                    <Skeleton class="h-10 w-10 rounded-full" />
                    <div class="space-y-2 flex-1">
                        <Skeleton class="h-4 w-3/4" />
                        <Skeleton class="h-3 w-1/2" />
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-8">
                <p class="text-red-600 mb-4">{{ error }}</p>
                <Button @click="cargarHistorial()" variant="outline">
                    Reintentar
                </Button>
            </div>

            <!-- Empty State -->
            <div v-else-if="!hayHistorial" class="text-center py-8">
                <Clock class="h-12 w-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" />
                <p class="text-gray-600 dark:text-gray-400 mb-2">No hay historial disponible</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    El historial se creará automáticamente cuando realices cambios a tu candidatura.
                </p>
            </div>

            <!-- Historial List -->
            <div v-else class="space-y-4">
                <div 
                    v-for="entry in historialesMostrados" 
                    :key="entry.id"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                >
                    <Collapsible>
                        <CollapsibleTrigger 
                            class="flex items-center justify-between w-full text-left"
                            @click="toggleExpanded(entry.id)"
                        >
                            <div class="flex items-center gap-3 flex-1 overflow-hidden">
                                <div class="flex-shrink-0">
                                    <Badge :class="entry.estado_color">
                                        v{{ entry.version }}
                                    </Badge>
                                </div>
                                
                                <div class="flex-1 min-w-0 overflow-hidden">
                                    <p class="font-medium text-gray-900 dark:text-gray-100 line-clamp-1">
                                        {{ entry.resumen_cambios }}
                                    </p>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        <User class="h-3 w-3" />
                                        <span>{{ entry.created_by }}</span>
                                        <span>•</span>
                                        <span>{{ entry.fecha_formateada }}</span>
                                        <span>•</span>
                                        <Badge variant="outline" :class="entry.estado_color">
                                            {{ entry.estado_label }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <Button
                                    @click.stop="verDetalle(entry)"
                                    variant="ghost"
                                    size="sm"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                                
                                <component 
                                    :is="expandedEntries.has(entry.id) ? ChevronDown : ChevronRight"
                                    class="h-4 w-4 text-gray-400 dark:text-gray-600"
                                />
                            </div>
                        </CollapsibleTrigger>
                        
                        <CollapsibleContent>
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div v-if="entry.motivo_cambio" class="mb-3">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo del cambio:</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-2 rounded line-clamp-2">
                                        {{ entry.motivo_cambio }}
                                    </p>
                                </div>
                                
                                <div v-if="entry.comentarios_admin_en_momento" class="mb-3">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comentarios de la comisión:</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-950/20 p-2 rounded border border-blue-200 dark:border-blue-800">
                                        {{ entry.comentarios_admin_en_momento }}
                                    </p>
                                </div>
                                
                                <div class="flex gap-2">
                                    <Button
                                        @click="verDetalle(entry)"
                                        variant="outline"
                                        size="sm"
                                    >
                                        <Eye class="h-4 w-4 mr-2" />
                                        Ver formulario completo
                                    </Button>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>
                </div>

                <!-- Paginación -->
                <div v-if="historial && historial.last_page > 1" class="flex justify-center mt-6">
                    <div class="flex gap-2">
                        <Button
                            v-for="page in historial.last_page"
                            :key="page"
                            @click="cargarHistorial(page)"
                            :variant="page === historial.current_page ? 'default' : 'outline'"
                            size="sm"
                        >
                            {{ page }}
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>

    <!-- Modal de Detalle -->
    <Dialog v-model:open="showDetailModal">
        <DialogContent class="max-w-4xl max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle v-if="selectedEntry">
                    Formulario - Versión {{ selectedEntry.version }}
                    <Badge :class="selectedEntry.estado_color" class="ml-2">
                        {{ selectedEntry.estado_label }}
                    </Badge>
                </DialogTitle>
            </DialogHeader>
            
            <div v-if="selectedEntry" class="space-y-6">
                <!-- Metadatos -->
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">Fecha del cambio:</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ selectedEntry.fecha_formateada }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">Modificado por:</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ selectedEntry.created_by }}</p>
                        </div>
                        <div v-if="selectedEntry.motivo_cambio" class="col-span-2">
                            <p class="font-medium text-gray-700 dark:text-gray-300">Motivo:</p>
                            <p class="text-gray-600 dark:text-gray-400 break-words">{{ selectedEntry.motivo_cambio }}</p>
                        </div>
                        <div v-if="selectedEntry.comentarios_admin_en_momento">
                            <p class="font-medium text-gray-700 dark:text-gray-300">Comentarios de la comisión:</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ selectedEntry.comentarios_admin_en_momento }}</p>
                        </div>
                    </div>
                </div>

                <!-- Datos del Formulario -->
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Datos del formulario en esta versión:</h4>
                    <div class="space-y-4">
                        <div
                            v-for="(valor, campo) in selectedEntry.formulario_data_con_nombres"
                            :key="campo"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-3"
                        >
                            <p class="font-medium text-gray-700 dark:text-gray-300 mb-2">{{ campo }}</p>
                            <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded text-sm">
                                <pre class="whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ formatearCampo(campo, valor) }}</pre>
                            </div>
                        </div>
                        
                        <!-- Fallback si no hay datos con nombres legibles -->
                        <div v-if="Object.keys(selectedEntry.formulario_data_con_nombres || {}).length === 0" class="text-center py-4">
                            <p class="text-gray-500 dark:text-gray-500">No hay datos de formulario disponibles para esta versión.</p>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>