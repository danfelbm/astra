<script setup lang="ts">
/**
 * InlineEditUser - Componente para edición inline de usuario (responsable)
 * Abre AddUsersModal al activar edición
 */
import { ref, computed } from 'vue';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@modules/Core/Resources/js/components/ui/avatar';
import AddUsersModal from '@modules/Core/Resources/js/components/modals/AddUsersModal.vue';
import { Pencil, X, Loader2, User } from 'lucide-vue-next';

interface UserInfo {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

interface Props {
    // Usuario actual (null si no hay)
    modelValue: UserInfo | null;
    // ID del usuario actual (alternativa si no tienes el objeto completo)
    userId?: number | null;
    canEdit?: boolean;
    loading?: boolean;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    // Endpoint de búsqueda de usuarios
    searchEndpoint: string;
    // IDs a excluir de la búsqueda
    excludedIds?: number[];
    // Título del modal
    modalTitle?: string;
    // Descripción del modal
    modalDescription?: string;
    // Permitir remover usuario (poner null)
    allowClear?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    userId: null,
    canEdit: true,
    loading: false,
    disabled: false,
    placeholder: 'Sin responsable',
    excludedIds: () => [],
    modalTitle: 'Seleccionar Usuario',
    modalDescription: 'Busca y selecciona un usuario',
    allowClear: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: UserInfo | null];
    'save': [userId: number | null, user: UserInfo | null];
}>();

// Estado del modal
const showModal = ref(false);

// Obtener iniciales del nombre
const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// IDs a excluir (incluyendo el actual si no queremos duplicados)
const computedExcludedIds = computed(() => {
    const ids = [...props.excludedIds];
    // No excluir el usuario actual para permitir re-selección
    return ids;
});

// Abrir modal de selección
const openModal = () => {
    if (!props.canEdit || props.disabled || props.loading) return;
    showModal.value = true;
};

// Manejar selección de usuario
const handleUserSelect = (data: { userIds: number[]; users?: UserInfo[] }) => {
    if (data.userIds.length > 0) {
        const userId = data.userIds[0];
        // Si tenemos la info completa del usuario
        const user = data.users?.[0] || { id: userId, name: `Usuario #${userId}`, email: '' };
        emit('save', userId, user);
    }
    showModal.value = false;
};

// Remover usuario actual
const clearUser = () => {
    if (!props.canEdit || props.disabled || props.loading) return;
    emit('save', null, null);
};
</script>

<template>
    <div class="group inline-edit-user flex items-center gap-2">
        <!-- Usuario actual o placeholder -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <template v-if="modelValue">
                <Avatar class="h-7 w-7 flex-shrink-0">
                    <AvatarImage v-if="modelValue.avatar" :src="modelValue.avatar" />
                    <AvatarFallback class="text-xs">{{ getInitials(modelValue.name) }}</AvatarFallback>
                </Avatar>
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ modelValue.name }}</p>
                    <p class="text-xs text-muted-foreground truncate">{{ modelValue.email }}</p>
                </div>
            </template>
            <template v-else>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <User class="h-4 w-4" />
                    <span class="text-sm italic">{{ placeholder }}</span>
                </div>
            </template>
        </div>

        <!-- Controles -->
        <div class="flex items-center gap-1 flex-shrink-0">
            <!-- Loading indicator -->
            <Loader2
                v-if="loading"
                class="h-4 w-4 animate-spin text-muted-foreground"
            />

            <!-- Botón editar -->
            <Button
                v-if="canEdit && !disabled && !loading"
                variant="ghost"
                size="icon"
                class="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                @click.stop="openModal"
                :aria-label="`Cambiar ${label || 'usuario'}`"
            >
                <Pencil class="h-3.5 w-3.5" />
            </Button>

            <!-- Botón remover -->
            <Button
                v-if="allowClear && modelValue && canEdit && !disabled && !loading"
                variant="ghost"
                size="icon"
                class="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-red-600"
                @click.stop="clearUser"
                aria-label="Quitar usuario"
            >
                <X class="h-3.5 w-3.5" />
            </Button>
        </div>

        <!-- Modal de selección -->
        <AddUsersModal
            v-model="showModal"
            :title="modalTitle"
            :description="modalDescription"
            :search-endpoint="searchEndpoint"
            :excluded-ids="computedExcludedIds"
            :max-selection="1"
            submit-button-text="Seleccionar"
            search-placeholder="Buscar por nombre, email..."
            @submit="handleUserSelect"
        />
    </div>
</template>
