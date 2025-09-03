<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Switch } from "@modules/Core/Resources/js/components/ui/switch";
import { Checkbox } from "@modules/Core/Resources/js/components/ui/checkbox";
import { ArrowLeft, Save } from 'lucide-vue-next';
import GeographicSelector from "@modules/Core/Resources/js/components/forms/GeographicSelector.vue";
import AvatarUpload from "@modules/Core/Resources/js/components/forms/AvatarUpload.vue";
import { type BreadcrumbItemType } from '@/types';
import { toast } from 'vue-sonner';

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
    role_ids: [] as number[], // Cambiar a array para múltiples roles
    cargo_id: 'none' as string,
    documento_identidad: '',
    telefono: '',
    direccion: '',
    territorio_id: null as number | null,
    departamento_id: null as number | null,
    municipio_id: null as number | null,
    localidad_id: null as number | null,
    activo: true,
    avatar: null as File | null, // Campo para el archivo de avatar
    created_at: '', // Campo para fecha de creación personalizada
});

// Estado para el preview del avatar
const tempAvatarUrl = ref<string | null>(null);

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

// Manejo de avatar
const handleAvatarUpload = (file: File) => {
    form.avatar = file;
    
    // Crear preview temporal
    const reader = new FileReader();
    reader.onload = (e) => {
        tempAvatarUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
    
    toast.success('Avatar seleccionado. Se cargará al crear el usuario.');
};

const handleAvatarDelete = () => {
    form.avatar = null;
    tempAvatarUrl.value = null;
    toast.success('Avatar eliminado');
};

const submit = () => {
    // Si hay avatar, usar FormData
    if (form.avatar) {
        form.transform((data) => {
            const formData = new FormData();
            Object.keys(data).forEach(key => {
                if (key === 'avatar' && data[key]) {
                    formData.append('avatar', data[key]);
                } else if (key === 'role_ids' && Array.isArray(data[key])) {
                    data[key].forEach((id: number) => {
                        formData.append('role_ids[]', id.toString());
                    });
                } else if (key === 'created_at' && data[key]) {
                    // Convertir formato datetime-local (YYYY-MM-DDTHH:MM:SS) a MySQL (YYYY-MM-DD HH:MM:SS)
                    const dateValue = data[key].toString().replace('T', ' ');
                    formData.append(key, dateValue);
                } else if (data[key] !== null && data[key] !== undefined) {
                    formData.append(key, data[key].toString());
                }
            });
            return formData;
        }).post(route('admin.usuarios.store'), {
            forceFormData: true,
        });
    } else {
        form.transform((data) => {
            // Convertir formato datetime-local a MySQL para requests sin avatar
            if (data.created_at) {
                data.created_at = data.created_at.replace('T', ' ');
            }
            return data;
        }).post(route('admin.usuarios.store'));
    }
};
</script>

<template>
    <Head title="Crear Usuario" />
    
    <AdminLayout :breadcrumbs="breadcrumbs">

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
                <!-- Avatar del Usuario -->
                <Card>
                    <CardHeader>
                        <CardTitle>Foto de Perfil</CardTitle>
                        <CardDescription>
                            Avatar personalizado del usuario (opcional)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AvatarUpload
                            :model-value="tempAvatarUrl"
                            :user-name="form.name || 'Nuevo Usuario'"
                            label="Avatar del usuario"
                            description="Sube una foto de perfil para el nuevo usuario. JPG, PNG o WEBP. Máximo 5MB."
                            @upload="handleAvatarUpload"
                            @delete="handleAvatarDelete"
                        />
                    </CardContent>
                </Card>

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
                                <Label for="documento_identidad">Documento de identidad *</Label>
                                <Input
                                    id="documento_identidad"
                                    v-model="form.documento_identidad"
                                    type="text"
                                    placeholder="12345678"
                                    :class="{ 'border-red-500': form.errors.documento_identidad }"
                                    required
                                />
                                <p v-if="form.errors.documento_identidad" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.documento_identidad }}
                                </p>
                            </div>

                            <div>
                                <Label for="telefono">Teléfono *</Label>
                                <Input
                                    id="telefono"
                                    v-model="form.telefono"
                                    type="tel"
                                    placeholder="3001234567"
                                    :class="{ 'border-red-500': form.errors.telefono }"
                                    required
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

                        <div>
                            <Label for="created_at">Fecha de creación (opcional)</Label>
                            <Input
                                id="created_at"
                                v-model="form.created_at"
                                type="datetime-local"
                                :class="{ 'border-red-500': form.errors.created_at }"
                                step="1"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Deja vacío para usar la fecha actual. Formato: YYYY-MM-DD HH:MM:SS
                            </p>
                            <p v-if="form.errors.created_at" class="text-sm text-red-600 mt-1">
                                {{ form.errors.created_at }}
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

                        <div class="space-y-4">
                            <!-- Campo de roles múltiples condicional basado en permisos -->
                            <div v-if="canAssignRoles">
                                <Label>Roles del usuario *</Label>
                                <p class="text-sm text-muted-foreground mb-3">
                                    Selecciona uno o más roles para este usuario
                                </p>
                                <div class="space-y-2 max-h-64 overflow-y-auto border rounded-lg p-4">
                                    <div v-for="role in roles" :key="role.value" class="flex items-start space-x-3 py-2">
                                        <Checkbox
                                            :id="`role-${role.value}`"
                                            :checked="form.role_ids.includes(role.value)"
                                            @update:checked="(checked) => {
                                                if (checked) {
                                                    if (!form.role_ids.includes(role.value)) {
                                                        form.role_ids.push(role.value);
                                                    }
                                                } else {
                                                    const index = form.role_ids.indexOf(role.value);
                                                    if (index > -1) {
                                                        form.role_ids.splice(index, 1);
                                                    }
                                                }
                                            }"
                                        />
                                        <div class="flex-1">
                                            <Label :for="`role-${role.value}`" class="text-base font-medium cursor-pointer">
                                                {{ role.label }}
                                            </Label>
                                            <p v-if="role.description" class="text-sm text-muted-foreground">
                                                {{ role.description }}
                                            </p>
                                            <span v-if="role.is_system" class="text-xs text-blue-600">
                                                (Rol del Sistema)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.role_ids" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.role_ids }}
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
    </AdminLayout>
</template>