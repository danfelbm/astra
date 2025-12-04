<script setup lang="ts">
import { Checkbox } from "@modules/Core/Resources/js/components/ui/checkbox";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@modules/Core/Resources/js/components/ui/avatar";
import { ScrollArea } from "@modules/Core/Resources/js/components/ui/scroll-area";
import { Search, Users, MessageSquare, CheckSquare, XSquare } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

interface WhatsAppGroup {
    id: number;
    group_jid: string;
    nombre: string;
    descripcion?: string;
    tipo: 'grupo' | 'comunidad';
    avatar_url?: string;
    participantes_count: number;
}

interface Props {
    grupos: WhatsAppGroup[];
    modelValue: number[];
}

const props = withDefaults(defineProps<Props>(), {
    grupos: () => [],
    modelValue: () => [],
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number[]): void;
}>();

// Estado local
const searchQuery = ref('');

// Grupos seleccionados
const selectedIds = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Grupos filtrados por búsqueda
const filteredGrupos = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.grupos;
    }

    const query = searchQuery.value.toLowerCase();
    return props.grupos.filter((grupo) =>
        grupo.nombre.toLowerCase().includes(query) ||
        grupo.group_jid.toLowerCase().includes(query) ||
        (grupo.descripcion?.toLowerCase().includes(query) ?? false)
    );
});

// Verificar si un grupo está seleccionado
const isSelected = (id: number): boolean => {
    return selectedIds.value.includes(id);
};

// Toggle selección de un grupo
const toggleSelection = (id: number): void => {
    const currentSelection = [...selectedIds.value];
    const index = currentSelection.indexOf(id);

    if (index === -1) {
        currentSelection.push(id);
    } else {
        currentSelection.splice(index, 1);
    }

    selectedIds.value = currentSelection;
};

// Seleccionar todos los grupos visibles
const selectAll = (): void => {
    const allVisibleIds = filteredGrupos.value.map(g => g.id);
    const newSelection = [...new Set([...selectedIds.value, ...allVisibleIds])];
    selectedIds.value = newSelection;
};

// Deseleccionar todos
const deselectAll = (): void => {
    selectedIds.value = [];
};

// Obtener resumen de selección
const selectionSummary = computed(() => {
    const count = selectedIds.value.length;
    if (count === 0) return null;

    const selectedGrupos = props.grupos.filter(g => selectedIds.value.includes(g.id));
    const totalParticipantes = selectedGrupos.reduce((sum, g) => sum + g.participantes_count, 0);

    return {
        grupos: count,
        participantes: totalParticipantes,
    };
});

// Formatear JID para mostrar
const formatJid = (jid: string): string => {
    return jid.replace('@g.us', '');
};
</script>

<template>
    <div class="space-y-4">
        <!-- Barra de búsqueda y acciones -->
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar grupos..."
                    class="pl-10"
                />
            </div>
            <div class="flex gap-2">
                <Button type="button" variant="outline" size="sm" @click="selectAll">
                    <CheckSquare class="h-4 w-4 mr-1" />
                    Todos
                </Button>
                <Button type="button" variant="outline" size="sm" @click="deselectAll">
                    <XSquare class="h-4 w-4 mr-1" />
                    Ninguno
                </Button>
            </div>
        </div>

        <!-- Resumen de selección -->
        <div v-if="selectionSummary" class="p-3 bg-muted rounded-md">
            <div class="flex items-center gap-2 text-sm">
                <MessageSquare class="h-4 w-4" />
                <span class="font-medium">{{ selectionSummary.grupos }} grupo(s)</span>
                <span class="text-muted-foreground">seleccionados</span>
                <span class="text-muted-foreground">·</span>
                <Users class="h-4 w-4" />
                <span class="font-medium">{{ selectionSummary.participantes.toLocaleString() }}</span>
                <span class="text-muted-foreground">participantes totales</span>
            </div>
        </div>

        <!-- Lista de grupos -->
        <ScrollArea class="h-[300px] border rounded-md">
            <div class="p-2 space-y-1">
                <div
                    v-for="grupo in filteredGrupos"
                    :key="grupo.id"
                    :class="[
                        'flex items-center gap-3 p-2 rounded-md cursor-pointer transition-colors',
                        isSelected(grupo.id) ? 'bg-primary/10 border border-primary/20' : 'hover:bg-muted'
                    ]"
                    @click="toggleSelection(grupo.id)"
                >
                    <Checkbox
                        :checked="isSelected(grupo.id)"
                        @update:checked="toggleSelection(grupo.id)"
                        @click.stop
                    />

                    <Avatar class="h-10 w-10">
                        <AvatarImage :src="grupo.avatar_url || undefined" />
                        <AvatarFallback>
                            <MessageSquare class="h-5 w-5" />
                        </AvatarFallback>
                    </Avatar>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium truncate">{{ grupo.nombre }}</span>
                            <Badge
                                :variant="grupo.tipo === 'comunidad' ? 'default' : 'secondary'"
                                class="text-xs"
                            >
                                {{ grupo.tipo === 'comunidad' ? 'COMUNIDAD' : 'GRUPO' }}
                            </Badge>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Users class="h-3 w-3" />
                            <span>{{ grupo.participantes_count.toLocaleString() }} participantes</span>
                        </div>
                    </div>
                </div>

                <!-- Mensaje si no hay resultados -->
                <div v-if="filteredGrupos.length === 0" class="text-center py-8 text-muted-foreground">
                    <MessageSquare class="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p v-if="searchQuery">No se encontraron grupos que coincidan</p>
                    <p v-else>No hay grupos disponibles</p>
                </div>
            </div>
        </ScrollArea>

        <!-- Información adicional -->
        <p class="text-xs text-muted-foreground">
            Selecciona los grupos de WhatsApp a los que deseas enviar la campaña.
            Los mensajes a grupos no soportan personalización con variables.
        </p>
    </div>
</template>
