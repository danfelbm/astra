<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "../ui/dialog";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Checkbox } from "../ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { ChevronLeft, ChevronRight, Loader2, Search, Users } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { toast } from 'vue-sonner';

interface User {
    id: number;
    name: string;
    email: string;
    documento_identidad?: string;
    telefono?: string;
}

interface ExtraField {
    name: string;
    label: string;
    type: 'select' | 'text' | 'number';
    options?: { value: string; label: string }[];
    value: any;
    required?: boolean;
}

interface Props {
    modelValue: boolean;
    title?: string;
    description?: string;
    searchEndpoint: string;
    addEndpoint?: string;
    excludedIds?: number[];
    extraFields?: ExtraField[];
    maxSelection?: number;
    submitButtonText?: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    noResultsMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Añadir Usuarios',
    description: 'Selecciona los usuarios que deseas añadir',
    submitButtonText: 'Añadir',
    searchPlaceholder: 'Buscar por nombre, email, documento o teléfono...',
    emptyMessage: 'Escribe para buscar usuarios disponibles',
    noResultsMessage: 'No se encontraron usuarios con esa búsqueda',
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    'submit': [data: { userIds: number[]; extraData: Record<string, any> }];
}>();

// Estado del modal
const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Estado de búsqueda y selección
const searchQuery = ref('');
const users = ref<User[]>([]);
const selectedUserIds = ref<number[]>([]);
const loading = ref(false);
const submitting = ref(false);
const initialized = ref(false);

// Estado de paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalUsers = ref(0);

// Estado de campos extra
const extraFieldValues = ref<Record<string, any>>({});

// Inicializar valores de campos extra
if (props.extraFields) {
    props.extraFields.forEach(field => {
        extraFieldValues.value[field.name] = field.value;
    });
}

// Función de búsqueda de usuarios
const searchUsers = async (page: number = 1) => {
    loading.value = true;
    try {
        const params: any = {
            page,
            search: searchQuery.value, // Usar 'search' como en Asambleas
        };

        // Si se proporcionaron IDs excluidos, enviarlos
        if (props.excludedIds && props.excludedIds.length > 0) {
            params.excluded_ids = props.excludedIds;
        }

        const response = await axios.get(props.searchEndpoint, { params });

        // Manejar diferentes formatos de respuesta
        if (response.data.participantes_disponibles) {
            // Formato de Asambleas
            const data = response.data.participantes_disponibles;
            users.value = data.data || [];
            currentPage.value = data.current_page || 1;
            lastPage.value = data.last_page || 1;
            totalUsers.value = data.total || 0;
        } else if (response.data.users) {
            // Formato de Votaciones (ajustaremos el backend)
            users.value = response.data.users || [];
            currentPage.value = response.data.current_page || 1;
            lastPage.value = response.data.last_page || 1;
            totalUsers.value = response.data.total || 0;
        } else {
            // Formato directo
            users.value = response.data.data || [];
            currentPage.value = response.data.current_page || 1;
            lastPage.value = response.data.last_page || 1;
            totalUsers.value = response.data.total || 0;
        }
    } catch (error) {
        console.error('Error buscando usuarios:', error);
        toast.error('Error al buscar usuarios', {
            description: 'Por favor intenta nuevamente',
            duration: 3000,
        });
        users.value = [];
    } finally {
        loading.value = false;
    }
};

// Debounce para búsqueda
let searchTimeout: number | null = null;
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (isOpen.value) {
            currentPage.value = 1;
            searchUsers(1);
        }
    }, 500);
});

// Cargar usuarios cuando se abre el modal
watch(isOpen, (newValue) => {
    if (newValue && !initialized.value) {
        initialized.value = true;
        searchUsers(1);
    }
    if (!newValue) {
        // Limpiar al cerrar
        selectedUserIds.value = [];
        searchQuery.value = '';
        users.value = [];
        currentPage.value = 1;
        initialized.value = false;
    }
});

// Manejar selección de usuarios
const toggleUserSelection = (userId: number, checked: boolean) => {
    if (checked) {
        // Verificar límite máximo si existe
        if (props.maxSelection && selectedUserIds.value.length >= props.maxSelection) {
            toast.warning(`Máximo ${props.maxSelection} usuarios permitidos`);
            return;
        }
        if (!selectedUserIds.value.includes(userId)) {
            selectedUserIds.value.push(userId);
        }
    } else {
        const index = selectedUserIds.value.indexOf(userId);
        if (index > -1) {
            selectedUserIds.value.splice(index, 1);
        }
    }
};

// Verificar si un usuario está seleccionado
const isUserSelected = (userId: number) => {
    return selectedUserIds.value.includes(userId);
};

// Cambiar página
const changePage = (page: number) => {
    currentPage.value = page;
    searchUsers(page);
};

// Enviar selección
const handleSubmit = async () => {
    if (selectedUserIds.value.length === 0) return;

    // Si se proporciona un endpoint de añadir, hacer la petición
    if (props.addEndpoint) {
        submitting.value = true;
        try {
            const data: any = {
                user_ids: selectedUserIds.value,
                ...extraFieldValues.value,
            };

            await axios.post(props.addEndpoint, data);
            
            toast.success(`${selectedUserIds.value.length} usuario(s) añadido(s) exitosamente`, {
                duration: 2000,
            });
            
            isOpen.value = false;
            emit('submit', { 
                userIds: selectedUserIds.value, 
                extraData: extraFieldValues.value 
            });
        } catch (error) {
            console.error('Error añadiendo usuarios:', error);
            toast.error('Error al añadir usuarios', {
                description: 'Por favor intenta nuevamente',
                duration: 3000,
            });
        } finally {
            submitting.value = false;
        }
    } else {
        // Solo emitir el evento para que el componente padre maneje la lógica
        emit('submit', { 
            userIds: selectedUserIds.value, 
            extraData: extraFieldValues.value 
        });
        isOpen.value = false;
    }
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Campos extra y búsqueda -->
                <div class="grid gap-4" :class="extraFields && extraFields.length > 0 ? 'md:grid-cols-2' : ''">
                    <!-- Campos extra configurables -->
                    <div v-for="field in extraFields" :key="field.name">
                        <label class="text-sm font-medium">{{ field.label }}</label>
                        <Select v-if="field.type === 'select'" v-model="extraFieldValues[field.name]">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem 
                                    v-for="option in field.options" 
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Input 
                            v-else
                            v-model="extraFieldValues[field.name]"
                            :type="field.type"
                            :required="field.required"
                        />
                    </div>

                    <!-- Campo de búsqueda -->
                    <div>
                        <label class="text-sm font-medium">Buscar Usuarios</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input 
                                v-model="searchQuery"
                                :placeholder="searchPlaceholder"
                                type="text"
                                class="pl-10"
                            />
                        </div>
                    </div>
                </div>

                <!-- Lista de usuarios -->
                <div class="border rounded-lg">
                    <div class="max-h-96 overflow-y-auto p-4">
                        <!-- Loading -->
                        <div v-if="loading" class="flex items-center justify-center py-8">
                            <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                            <span class="ml-2 text-muted-foreground">Buscando usuarios...</span>
                        </div>

                        <!-- Lista de usuarios -->
                        <div v-else-if="users.length > 0" class="space-y-2">
                            <div 
                                v-for="user in users" 
                                :key="user.id" 
                                class="flex items-start space-x-3 py-2 hover:bg-gray-50 rounded px-2"
                            >
                                <Checkbox
                                    :id="`user-${user.id}`"
                                    :checked="isUserSelected(user.id)"
                                    @update:checked="(value) => toggleUserSelection(user.id, value)"
                                    class="mt-1"
                                />
                                <label 
                                    :for="`user-${user.id}`"
                                    class="flex-1 cursor-pointer"
                                >
                                    <div class="font-medium">{{ user.name }}</div>
                                    <div class="text-sm text-muted-foreground space-y-1">
                                        <div>{{ user.email }}</div>
                                        <div class="flex gap-3">
                                            <span v-if="user.documento_identidad">
                                                Doc: {{ user.documento_identidad }}
                                            </span>
                                            <span v-if="user.telefono">
                                                Tel: {{ user.telefono }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Sin resultados -->
                        <div v-else class="text-center py-8">
                            <Users class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
                            <p class="text-muted-foreground">
                                {{ searchQuery ? noResultsMessage : emptyMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div v-if="lastPage > 1" class="border-t px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">
                                Página {{ currentPage }} de {{ lastPage }}
                                <span v-if="totalUsers > 0">
                                    ({{ totalUsers }} usuarios disponibles)
                                </span>
                            </span>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === 1"
                                    @click="changePage(currentPage - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === lastPage"
                                    @click="changePage(currentPage + 1)"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <div class="flex items-center justify-between w-full">
                    <span class="text-sm text-muted-foreground">
                        <span v-if="selectedUserIds.length > 0" class="font-medium text-foreground">
                            {{ selectedUserIds.length }} usuario(s) seleccionado(s)
                        </span>
                        <span v-else>
                            Selecciona usuarios para continuar
                        </span>
                    </span>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="isOpen = false">
                            Cancelar
                        </Button>
                        <Button 
                            @click="handleSubmit"
                            :disabled="selectedUserIds.length === 0 || submitting"
                        >
                            <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
                            {{ submitButtonText }} 
                            <span v-if="selectedUserIds.length > 0">
                                ({{ selectedUserIds.length }})
                            </span>
                        </Button>
                    </div>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>