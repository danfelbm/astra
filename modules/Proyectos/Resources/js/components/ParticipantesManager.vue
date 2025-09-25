<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h4 class="font-medium">{{ title }}</h4>
                <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
            <Button
                v-if="!disabled"
                type="button"
                variant="outline"
                size="sm"
                @click="openAddDialog"
            >
                <Plus class="h-4 w-4 mr-2" />
                Agregar Participante
            </Button>
        </div>

        <!-- Lista de participantes -->
        <div v-if="participantes.length > 0" class="space-y-2">
            <Card v-for="(participante, index) in participantes" :key="participante.user_id">
                <CardContent class="p-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ getUserName(participante.user_id) }}</span>
                                <Badge variant="secondary">{{ getRolLabel(participante.rol) }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ getUserEmail(participante.user_id) }}
                            </p>
                            <p v-if="participante.notas" class="text-sm">
                                {{ participante.notas }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="!disabled"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="editParticipante(index)"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>
                            <Button
                                v-if="!disabled"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeParticipante(index)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Estado vacío -->
        <div v-else class="text-center py-8 border-2 border-dashed rounded-lg">
            <Users class="h-12 w-12 mx-auto text-muted-foreground mb-2" />
            <p class="text-sm text-muted-foreground">No hay participantes agregados</p>
        </div>

        <!-- Dialog para agregar/editar participante -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editingIndex !== null ? 'Editar Participante' : 'Agregar Participante' }}
                    </DialogTitle>
                    <DialogDescription>
                        Seleccione un usuario y defina su rol en el {{ entityType }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Selector de usuario -->
                    <div class="space-y-2">
                        <Label>Usuario</Label>
                        <ContratoUserSelect
                            v-model="currentParticipante.user_id"
                            :users="availableUsers"
                            :disabled="editingIndex !== null"
                            placeholder="Seleccionar usuario..."
                            :error="errors.user_id"
                        />
                    </div>

                    <!-- Selector de rol -->
                    <div class="space-y-2">
                        <Label>Rol</Label>
                        <Select v-model="currentParticipante.rol">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar rol..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="rol in roles"
                                    :key="rol.value"
                                    :value="rol.value"
                                >
                                    {{ rol.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.rol" class="text-sm text-destructive">{{ errors.rol }}</p>
                    </div>

                    <!-- Notas -->
                    <div class="space-y-2">
                        <Label>Notas (opcional)</Label>
                        <Textarea
                            v-model="currentParticipante.notas"
                            placeholder="Agregar notas sobre la participación..."
                            class="min-h-[80px]"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="dialogOpen = false">
                        Cancelar
                    </Button>
                    <Button type="button" @click="saveParticipante">
                        {{ editingIndex !== null ? 'Actualizar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Card, CardContent } from '@modules/Core/Resources/js/components/ui/card';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Label } from '@modules/Core/Resources/js/components/ui/label';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@modules/Core/Resources/js/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@modules/Core/Resources/js/components/ui/select';
import { Plus, Edit, Trash2, Users } from 'lucide-vue-next';
import ContratoUserSelect from './ContratoUserSelect.vue';

// Tipos
interface User {
    id: number;
    name: string;
    email: string;
}

interface Participante {
    user_id: number;
    rol: string;
    notas?: string;
}

// Props
const props = withDefaults(defineProps<{
    modelValue?: Participante[];
    users?: User[];
    entityType?: string;
    title?: string;
    description?: string;
    disabled?: boolean;
    roles?: Array<{ value: string; label: string }>;
}>(), {
    modelValue: () => [],
    users: () => [],
    entityType: 'contrato',
    title: 'Participantes',
    disabled: false,
    roles: () => [
        { value: 'participante', label: 'Participante' },
        { value: 'observador', label: 'Observador' },
        { value: 'aprobador', label: 'Aprobador' },
    ],
});

// Emits
const emit = defineEmits<{
    'update:modelValue': [value: Participante[]];
}>();

// Estado local
const participantes = ref<Participante[]>([...props.modelValue]);
const dialogOpen = ref(false);
const editingIndex = ref<number | null>(null);
const currentParticipante = ref<Participante>({
    user_id: 0,
    rol: 'participante',
    notas: '',
});
const errors = ref<Record<string, string>>({});

// Computed
const availableUsers = computed(() => {
    const usedIds = participantes.value
        .filter((_, index) => index !== editingIndex.value)
        .map(p => p.user_id);
    return props.users.filter(u => !usedIds.includes(u.id));
});

// Métodos
function getUserName(userId: number): string {
    const user = props.users.find(u => u.id === userId);
    return user?.name || 'Usuario desconocido';
}

function getUserEmail(userId: number): string {
    const user = props.users.find(u => u.id === userId);
    return user?.email || '';
}

function getRolLabel(rol: string): string {
    const roleObj = props.roles.find(r => r.value === rol);
    return roleObj?.label || rol;
}

function openAddDialog() {
    editingIndex.value = null;
    currentParticipante.value = {
        user_id: 0,
        rol: 'participante',
        notas: '',
    };
    errors.value = {};
    dialogOpen.value = true;
}

function editParticipante(index: number) {
    editingIndex.value = index;
    const participante = participantes.value[index];
    currentParticipante.value = { ...participante };
    errors.value = {};
    dialogOpen.value = true;
}

function removeParticipante(index: number) {
    participantes.value.splice(index, 1);
    emit('update:modelValue', participantes.value);
}

function validateParticipante(): boolean {
    errors.value = {};
    let isValid = true;

    if (!currentParticipante.value.user_id) {
        errors.value.user_id = 'Debe seleccionar un usuario';
        isValid = false;
    }

    if (!currentParticipante.value.rol) {
        errors.value.rol = 'Debe seleccionar un rol';
        isValid = false;
    }

    return isValid;
}

function saveParticipante() {
    if (!validateParticipante()) {
        return;
    }

    if (editingIndex.value !== null) {
        // Editar existente
        participantes.value[editingIndex.value] = { ...currentParticipante.value };
    } else {
        // Agregar nuevo
        participantes.value.push({ ...currentParticipante.value });
    }

    emit('update:modelValue', participantes.value);
    dialogOpen.value = false;
}

// Sincronizar con prop cuando cambia
watch(() => props.modelValue, (newValue) => {
    participantes.value = [...newValue];
}, { deep: true });
</script>