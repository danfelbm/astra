<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import AddUsersModal from '@modules/Core/Resources/js/components/modals/AddUsersModal.vue';
import { UserPlus, X, User } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
}

interface Props {
    modelValue?: number[];
    gestores?: User[];
}

interface Emits {
    (e: 'update:modelValue', value: number[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    gestores: () => [],
});

const emit = defineEmits<Emits>();

// Estado del modal
const mostrarModal = ref(false);

// Gestores seleccionados
const gestoresSeleccionados = computed<User[]>(() => {
    if (!props.gestores || props.gestores.length === 0) {
        return [];
    }
    return props.gestores.filter(g => props.modelValue.includes(g.id));
});

// Añadir gestores
const handleAnadirGestores = ({ userIds }: { userIds: number[] }) => {
    const nuevosGestores = [...new Set([...props.modelValue, ...userIds])];
    emit('update:modelValue', nuevosGestores);
    mostrarModal.value = false;
};

// Eliminar gestor
const eliminarGestor = (gestorId: number) => {
    const gestoresActualizados = props.modelValue.filter(id => id !== gestorId);
    emit('update:modelValue', gestoresActualizados);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Gestores del Proyecto
                    </CardTitle>
                    <CardDescription>
                        Los gestores pueden editar el proyecto desde el panel de usuario
                    </CardDescription>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="mostrarModal = true"
                >
                    <UserPlus class="h-4 w-4 mr-2" />
                    Añadir Gestor
                </Button>
            </div>
        </CardHeader>

        <CardContent>
            <!-- Lista de gestores -->
            <div v-if="gestoresSeleccionados.length > 0" class="space-y-2">
                <div
                    v-for="gestor in gestoresSeleccionados"
                    :key="gestor.id"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                            <User class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-medium">{{ gestor.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ gestor.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary">Gestor</Badge>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="eliminarGestor(gestor.id)"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                <User class="h-12 w-12 mx-auto mb-2 opacity-50" />
                <p>No hay gestores asignados</p>
                <p class="text-sm mt-1">Haz clic en "Añadir Gestor" para asignar gestores al proyecto</p>
            </div>
        </CardContent>
    </Card>

    <!-- Modal de selección -->
    <AddUsersModal
        v-model="mostrarModal"
        title="Seleccionar Gestores"
        description="Selecciona los usuarios que podrán gestionar y editar este proyecto"
        :search-endpoint="route('admin.proyectos.search-users')"
        :excluded-ids="modelValue"
        submit-button-text="Añadir Gestores"
        @submit="handleAnadirGestores"
    />
</template>
