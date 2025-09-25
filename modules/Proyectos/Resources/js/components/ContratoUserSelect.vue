<template>
    <div class="space-y-2">
        <!-- Label -->
        <div v-if="label">
            <Label :for="inputId">{{ label }}</Label>
            <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
        </div>

        <!-- Selector de usuario -->
        <Popover v-model:open="open">
            <PopoverTrigger asChild>
                <Button
                    :id="inputId"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :disabled="disabled"
                    class="w-full justify-between"
                >
                    <span class="text-left truncate">
                        {{ selectedUserName || placeholder }}
                    </span>
                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>

            <PopoverContent class="w-full p-0" align="start">
                <Command>
                    <CommandInput
                        v-model="searchQuery"
                        placeholder="Buscar usuario..."
                    />

                    <CommandList>
                        <CommandEmpty>No se encontraron usuarios</CommandEmpty>

                        <CommandGroup>
                            <CommandItem
                                v-for="user in filteredUsers"
                                :key="user.id"
                                :value="user"
                                @select="selectUser(user)"
                                class="cursor-pointer"
                            >
                                <Check
                                    class="mr-2 h-4 w-4"
                                    :class="modelValue === user.id ? 'opacity-100' : 'opacity-0'"
                                />
                                <div class="flex-1">
                                    <div class="font-medium">{{ user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                </div>
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>

        <!-- Información del usuario seleccionado -->
        <div v-if="selectedUser" class="p-3 bg-muted rounded-lg space-y-1">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="font-medium">{{ selectedUser.name }}</p>
                    <p class="text-sm text-muted-foreground">{{ selectedUser.email }}</p>
                </div>
                <Button
                    v-if="!required"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="clearSelection"
                    class="h-auto p-1"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Error message -->
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@modules/Core/Resources/js/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@modules/Core/Resources/js/components/ui/popover';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Check, ChevronsUpDown, X } from 'lucide-vue-next';

// Tipo para usuario
interface User {
    id: number;
    name: string;
    email: string;
}

// Props
const props = withDefaults(defineProps<{
    modelValue?: number | null;
    users?: User[];
    label?: string;
    placeholder?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string;
}>(), {
    modelValue: null,
    users: () => [],
    placeholder: 'Seleccionar usuario...',
    disabled: false,
    required: false,
});

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    'change': [user: User | null];
}>();

// Estado local
const open = ref(false);
const searchQuery = ref('');
const inputId = `user-select-${Math.random().toString(36).substr(2, 9)}`;

// Computed
const selectedUser = computed(() => {
    if (!props.modelValue) return null;
    return props.users.find(u => u.id === props.modelValue) || null;
});

const selectedUserName = computed(() => {
    return selectedUser.value?.name || '';
});

const filteredUsers = computed(() => {
    if (!searchQuery.value) {
        return props.users;
    }

    const query = searchQuery.value.toLowerCase();
    return props.users.filter(user =>
        user.name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
    );
});

// Métodos
function selectUser(user: User) {
    emit('update:modelValue', user.id);
    emit('change', user);
    open.value = false;
    searchQuery.value = '';
}

function clearSelection() {
    emit('update:modelValue', null);
    emit('change', null);
}

// Limpiar búsqueda cuando se cierra el popover
watch(open, (isOpen) => {
    if (!isOpen) {
        searchQuery.value = '';
    }
});
</script>