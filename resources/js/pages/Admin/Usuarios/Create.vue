<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { ArrowLeft, Save } from 'lucide-vue-next';
import GeographicSelector from '@/components/forms/GeographicSelector.vue';
import { type BreadcrumbItemType } from '@/types';

interface Cargo {
    id: number;
    nombre: string;
}

interface Props {
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
    { title: 'Nuevo Usuario', href: '#' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: null as number | null, // Cambiar de 'role' a 'role_id'
    cargo_id: 'none' as string,
    documento_identidad: '',
    telefono: '',
    direccion: '',
    territorio_id: null as number | null,
    departamento_id: null as number | null,
    municipio_id: null as number | null,
    localidad_id: null as number | null,
    activo: true,
});

// Geographic data
const geographicData = ref({
    territorio_id: undefined,
    departamento_id: undefined,
    municipio_id: undefined,
    localidad_id: undefined,
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
    form.post(route('admin.usuarios.store'));
};
</script>

<template>
    <Head title="Crear Usuario" />
    
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Crear Usuario</h1>
                    <p class="text-muted-foreground">
                        Registra un nuevo usuario en el sistema
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
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="password">Contraseña *</Label>
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
                                <Label for="password_confirmation">Confirmar contraseña *</Label>
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
                            <!-- Mensaje informativo cuando no tiene permisos -->
                            <div v-else>
                                <Label>Rol</Label>
                                <div class="p-3 bg-muted rounded-md">
                                    <p class="text-sm text-muted-foreground">
                                        Se asignará el rol por defecto al usuario
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
                        Crear Usuario
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>