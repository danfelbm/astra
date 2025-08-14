<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { ArrowLeft, Save } from 'lucide-vue-next';
import GeographicSelector from '@/components/forms/GeographicSelector.vue';
import { type BreadcrumbItemType } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    role?: string; // Mantener por compatibilidad
    role_id?: number; // Nuevo campo para el ID del rol
    roles?: Array<{ id: number; name: string; display_name: string }>; // Relación con roles
    cargo_id?: number;
    documento_identidad?: string;
    telefono?: string;
    direccion?: string;
    territorio_id?: number;
    departamento_id?: number;
    municipio_id?: number;
    localidad_id?: number;
    activo: boolean;
    territorio?: { id: number; nombre: string };
    departamento?: { id: number; nombre: string };
    municipio?: { id: number; nombre: string };
    localidad?: { id: number; nombre: string };
    cargo?: { id: number; nombre: string };
}

interface Cargo {
    id: number;
    nombre: string;
}

interface Props {
    user: User;
    cargos: Cargo[];
    roles: Array<{ 
        value: number;
        label: string;
        name: string;
        is_system: boolean;
        description?: string;
    }>;
    canAssignRoles: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Usuarios', href: '/admin/usuarios' },
    { title: 'Editar Usuario', href: '#' },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role_id: props.user.role_id || null, // Usar role_id en lugar de role
    cargo_id: props.user.cargo_id ? props.user.cargo_id.toString() : 'none',
    documento_identidad: props.user.documento_identidad || '',
    telefono: props.user.telefono || '',
    direccion: props.user.direccion || '',
    territorio_id: props.user.territorio_id || null,
    departamento_id: props.user.departamento_id || null,
    municipio_id: props.user.municipio_id || null,
    localidad_id: props.user.localidad_id || null,
    activo: props.user.activo,
});

// Geographic data
const geographicData = ref({
    territorio_id: props.user.territorio_id || undefined,
    departamento_id: props.user.departamento_id || undefined,
    municipio_id: props.user.municipio_id || undefined,
    localidad_id: props.user.localidad_id || undefined,
});

// Update form when geographic selection changes
const handleGeographicChange = (value: any) => {
    geographicData.value = value;
    form.territorio_id = value.territorio_id || null;
    form.departamento_id = value.departamento_id || null;
    form.municipio_id = value.municipio_id || null;
    form.localidad_id = value.localidad_id || null;
};

const submit = () => {
    form.put(route('admin.usuarios.update', props.user.id));
};
</script>

<template>
    <Head title="Editar Usuario" />
    
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Editar Usuario</h1>
                    <p class="text-muted-foreground">
                        Modifica la información del usuario
                    </p>
                </div>
                <Link :href="route('admin.usuarios.index')">
                    <Button variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Información Básica -->
                <Card>
                    <CardHeader>
                        <CardTitle>Información Básica</CardTitle>
                        <CardDescription>
                            Datos principales del usuario
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="name">Nombre completo *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Juan Pérez"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <Label for="email">Correo electrónico *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="juan@ejemplo.com"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <Label for="documento_identidad">Documento de identidad</Label>
                                <Input
                                    id="documento_identidad"
                                    v-model="form.documento_identidad"
                                    type="text"
                                    placeholder="12345678"
                                    :class="{ 'border-red-500': form.errors.documento_identidad }"
                                />
                                <p v-if="form.errors.documento_identidad" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.documento_identidad }}
                                </p>
                            </div>

                            <div>
                                <Label for="telefono">Teléfono</Label>
                                <Input
                                    id="telefono"
                                    v-model="form.telefono"
                                    type="tel"
                                    placeholder="3001234567"
                                    :class="{ 'border-red-500': form.errors.telefono }"
                                />
                                <p v-if="form.errors.telefono" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.telefono }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <Label for="direccion">Dirección</Label>
                            <Input
                                id="direccion"
                                v-model="form.direccion"
                                type="text"
                                placeholder="Calle 123 #45-67"
                                :class="{ 'border-red-500': form.errors.direccion }"
                            />
                            <p v-if="form.errors.direccion" class="text-sm text-red-600 mt-1">
                                {{ form.errors.direccion }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Seguridad -->
                <Card>
                    <CardHeader>
                        <CardTitle>Seguridad</CardTitle>
                        <CardDescription>
                            Configuración de acceso y contraseña
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="p-4 bg-muted rounded-lg mb-4">
                            <p class="text-sm text-muted-foreground">
                                Deja los campos de contraseña vacíos si no deseas cambiarla.
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="password">Nueva contraseña</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="••••••••"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div>
                                <Label for="password_confirmation">Confirmar nueva contraseña</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                    :class="{ 'border-red-500': form.errors.password_confirmation }"
                                />
                                <p v-if="form.errors.password_confirmation" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.password_confirmation }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <!-- Campo de rol condicional basado en permisos -->
                            <div v-if="canAssignRoles">
                                <Label for="role_id">Rol *</Label>
                                <Select v-model="form.role_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.role_id }">
                                        <SelectValue placeholder="Selecciona un rol" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="role in roles" :key="role.value" :value="role.value">
                                            <div class="flex flex-col">
                                                <span>{{ role.label }}</span>
                                                <span v-if="role.description" class="text-xs text-muted-foreground">{{ role.description }}</span>
                                                <span v-if="role.is_system" class="text-xs text-blue-600">(Rol del Sistema)</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role_id" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.role_id }}
                                </p>
                            </div>
                            <!-- Mostrar rol actual cuando no tiene permisos para cambiarlo -->
                            <div v-else>
                                <Label>Rol actual</Label>
                                <div class="p-3 bg-muted rounded-md">
                                    <p class="text-sm font-medium">
                                        {{ roles.find(r => r.value === form.role_id)?.label || 'Sin rol asignado' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        No tienes permisos para cambiar el rol de este usuario
                                    </p>
                                </div>
                            </div>

                            <div>
                                <Label for="cargo_id">Cargo</Label>
                                <Select v-model="form.cargo_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.cargo_id }">
                                        <SelectValue placeholder="Selecciona un cargo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Sin cargo</SelectItem>
                                        <SelectItem v-for="cargo in cargos" :key="cargo.id" :value="cargo.id.toString()">
                                            {{ cargo.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.cargo_id" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.cargo_id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Switch
                                id="activo"
                                :checked="form.activo"
                                @update:checked="form.activo = $event"
                            />
                            <Label for="activo">Usuario activo</Label>
                        </div>
                    </CardContent>
                </Card>

                <!-- Ubicación Geográfica -->
                <Card>
                    <CardHeader>
                        <CardTitle>Ubicación Geográfica</CardTitle>
                        <CardDescription>
                            Define la ubicación del usuario (opcional)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <GeographicSelector
                            :model-value="geographicData"
                            @update:model-value="handleGeographicChange"
                            mode="single"
                            :show-card="false"
                            title=""
                            description=""
                        />
                        <div v-if="form.errors.territorio_id || form.errors.departamento_id || form.errors.municipio_id || form.errors.localidad_id" 
                             class="text-sm text-red-600 mt-2">
                            {{ form.errors.territorio_id || form.errors.departamento_id || form.errors.municipio_id || form.errors.localidad_id }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-2">
                    <Link :href="route('admin.usuarios.index')">
                        <Button variant="outline" type="button">
                            Cancelar
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Guardar Cambios
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>