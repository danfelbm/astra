<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChevronsUpDown, Check, Loader2, User, Mail, CreditCard, Phone, X, UserPlus } from 'lucide-vue-next';
import { useDebounce } from '@/composables/useDebounce';
import { cn } from '@/lib/utils';

interface UserOption {
    id: number;
    name: string;
    email: string;
    documento_identidad?: string;
    telefono?: string;
}

interface Props {
    votacionId: number;
    placeholder?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Buscar usuarios...',
    disabled: false,
});

const emit = defineEmits<{
    'add-users': [users: UserOption[]];
}>();

// Estado
const open = ref(false);
const searchQuery = ref('');
const isLoading = ref(false);
const users = ref<UserOption[]>([]);
const hasMore = ref(false);
const currentPage = ref(1);
const selectedUsers = ref<UserOption[]>([]);
const totalResults = ref(0);

// Debounce
const { debounce } = useDebounce();

// Computed
const buttonLabel = computed(() => {
    if (selectedUsers.value.length === 0) {
        return props.placeholder;
    }
    return `${selectedUsers.value.length} usuario${selectedUsers.value.length !== 1 ? 's' : ''} seleccionado${selectedUsers.value.length !== 1 ? 's' : ''}`;
});

const hasSelectedUsers = computed(() => selectedUsers.value.length > 0);

// Funciones
const searchUsers = async (query: string, page: number = 1) => {
    if (query.length < 2) {
        users.value = [];
        totalResults.value = 0;
        hasMore.value = false;
        return;
    }

    isLoading.value = true;
    
    try {
        const response = await fetch(`/admin/votaciones/${props.votacionId}/search-users?query=${encodeURIComponent(query)}&page=${page}`);
        const data = await response.json();
        
        if (page === 1) {
            users.value = data.users || [];
        } else {
            users.value = [...users.value, ...(data.users || [])];
        }
        
        hasMore.value = data.has_more || false;
        totalResults.value = data.total || 0;
        currentPage.value = data.page || 1;
    } catch (error) {
        console.error('Error buscando usuarios:', error);
        users.value = [];
        hasMore.value = false;
        totalResults.value = 0;
    } finally {
        isLoading.value = false;
    }
};

// Debounced search
const debouncedSearch = debounce((query: string) => {
    currentPage.value = 1;
    searchUsers(query, 1);
}, 300);

// Watch search query
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery);
});

// Verificar si un usuario está seleccionado
const isUserSelected = (user: UserOption) => {
    return selectedUsers.value.some(u => u.id === user.id);
};

// Toggle selección de usuario
const toggleUserSelection = (user: UserOption) => {
    if (isUserSelected(user)) {
        selectedUsers.value = selectedUsers.value.filter(u => u.id !== user.id);
    } else {
        selectedUsers.value.push(user);
    }
};

// Remover usuario seleccionado
const removeUser = (user: UserOption) => {
    selectedUsers.value = selectedUsers.value.filter(u => u.id !== user.id);
};

// Cargar más resultados
const loadMore = () => {
    if (!hasMore.value || isLoading.value) return;
    searchUsers(searchQuery.value, currentPage.value + 1);
};

// Agregar usuarios seleccionados
const addSelectedUsers = () => {
    if (selectedUsers.value.length === 0) return;
    
    emit('add-users', [...selectedUsers.value]);
    
    // Limpiar selección
    selectedUsers.value = [];
    searchQuery.value = '';
    users.value = [];
    open.value = false;
};

// Limpiar toda la selección
const clearSelection = () => {
    selectedUsers.value = [];
};

// Formatear documento
const formatDocumento = (doc?: string) => {
    if (!doc) return '';
    // Ocultar parte del documento por privacidad
    if (doc.length > 4) {
        return `***${doc.slice(-4)}`;
    }
    return '****';
};
</script>

<template>
    <div class="space-y-3">
        <!-- Tags de usuarios seleccionados -->
        <div v-if="selectedUsers.length > 0" class="flex flex-wrap gap-2">
            <Badge 
                v-for="user in selectedUsers" 
                :key="user.id"
                variant="secondary"
                class="pl-2 pr-1 py-1"
            >
                <span class="text-xs">{{ user.name }}</span>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-4 w-4 p-0 ml-2 hover:bg-transparent"
                    @click="removeUser(user)"
                >
                    <X class="h-3 w-3" />
                </Button>
            </Badge>
            <Button
                v-if="selectedUsers.length > 0"
                variant="ghost"
                size="sm"
                class="h-6 px-2 text-xs"
                @click="clearSelection"
            >
                Limpiar todo
            </Button>
        </div>

        <!-- Combobox principal -->
        <div class="flex gap-2">
            <Popover v-model:open="open">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        role="combobox"
                        :aria-expanded="open"
                        :disabled="disabled"
                        class="flex-1 justify-between"
                    >
                        <span :class="hasSelectedUsers ? '' : 'text-muted-foreground'">
                            {{ buttonLabel }}
                        </span>
                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-[400px] p-0" align="start">
                    <Command>
                        <CommandInput 
                            v-model="searchQuery"
                            placeholder="Buscar por nombre, email, cédula o teléfono..."
                            class="h-9"
                        />
                        
                        <CommandList>
                            <!-- Estado de carga -->
                            <div v-if="isLoading && users.length === 0" class="py-6 text-center text-sm">
                                <Loader2 class="h-4 w-4 animate-spin mx-auto mb-2" />
                                <p>Buscando usuarios...</p>
                            </div>
                            
                            <!-- Sin resultados -->
                            <CommandEmpty v-else-if="searchQuery.length >= 2 && users.length === 0 && !isLoading">
                                <div class="py-6 text-center text-sm">
                                    <User class="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
                                    <p>No se encontraron usuarios</p>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Intenta con otro término de búsqueda
                                    </p>
                                </div>
                            </CommandEmpty>
                            
                            <!-- Mensaje inicial -->
                            <div v-else-if="searchQuery.length < 2" class="py-6 text-center text-sm text-muted-foreground">
                                <p>Escribe al menos 2 caracteres para buscar</p>
                            </div>
                            
                            <!-- Lista de usuarios -->
                            <CommandGroup v-else>
                                <div v-if="totalResults > 0" class="px-2 py-1.5 text-xs text-muted-foreground">
                                    {{ totalResults }} {{ totalResults === 1 ? 'resultado' : 'resultados' }}
                                </div>
                                
                                <CommandItem
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id.toString()"
                                    @select="toggleUserSelection(user)"
                                    class="cursor-pointer"
                                    :class="isUserSelected(user) ? 'bg-accent' : ''"
                                >
                                    <div class="flex items-start justify-between w-full">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <User class="h-3 w-3 text-muted-foreground shrink-0" />
                                                <span class="font-medium truncate">{{ user.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <Mail class="h-3 w-3 text-muted-foreground shrink-0" />
                                                <span class="text-xs text-muted-foreground truncate">{{ user.email }}</span>
                                            </div>
                                            <div class="flex items-center gap-4 mt-1">
                                                <div v-if="user.documento_identidad" class="flex items-center gap-1">
                                                    <CreditCard class="h-3 w-3 text-muted-foreground shrink-0" />
                                                    <span class="text-xs text-muted-foreground">
                                                        {{ formatDocumento(user.documento_identidad) }}
                                                    </span>
                                                </div>
                                                <div v-if="user.telefono" class="flex items-center gap-1">
                                                    <Phone class="h-3 w-3 text-muted-foreground shrink-0" />
                                                    <span class="text-xs text-muted-foreground">
                                                        {{ user.telefono }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <Check
                                            v-if="isUserSelected(user)"
                                            class="h-4 w-4 text-primary shrink-0 ml-2"
                                        />
                                    </div>
                                </CommandItem>
                                
                                <!-- Botón para cargar más -->
                                <div v-if="hasMore && !isLoading" class="p-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="w-full"
                                        @click.stop="loadMore"
                                    >
                                        Cargar más resultados
                                    </Button>
                                </div>
                                
                                <!-- Indicador de carga de más resultados -->
                                <div v-if="isLoading && users.length > 0" class="p-2 text-center">
                                    <Loader2 class="h-4 w-4 animate-spin mx-auto" />
                                </div>
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            
            <!-- Botón de agregar -->
            <Button 
                @click="addSelectedUsers" 
                :disabled="!hasSelectedUsers"
                variant="default"
            >
                <UserPlus class="mr-2 h-4 w-4" />
                Agregar {{ selectedUsers.length || '' }}
            </Button>
        </div>
    </div>
</template>