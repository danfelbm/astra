<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, Save, Users, Vote, FileText, AlertCircle } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Tenant {
    id: number;
    name: string;
    subdomain: string;
    active: boolean;
    subscription_plan: string;
    settings: {
        logo?: string;
        primary_color?: string;
        otp_expiration?: number;
        timezone?: string;
    };
    limits: {
        max_users?: number | null;
        max_votaciones?: number | null;
        max_convocatorias?: number | null;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    tenant: Tenant;
    subscriptionPlans: Record<string, string>;
    timezones: Record<string, string>;
    userCount: number;
    votacionCount: number;
    convocatoriaCount: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: `Editar: ${props.tenant.name}`, href: '#' },
];

// Formulario con valores del tenant
const form = useForm({
    name: props.tenant.name,
    subdomain: props.tenant.subdomain,
    subscription_plan: props.tenant.subscription_plan,
    active: props.tenant.active,
    settings: {
        logo: props.tenant.settings?.logo || '',
        primary_color: props.tenant.settings?.primary_color || '#3B82F6',
        otp_expiration: props.tenant.settings?.otp_expiration || 10,
        timezone: props.tenant.settings?.timezone || 'America/Bogota',
    },
    limits: {
        max_users: props.tenant.limits?.max_users || null,
        max_votaciones: props.tenant.limits?.max_votaciones || null,
        max_convocatorias: props.tenant.limits?.max_convocatorias || null,
    },
});

// Función para enviar formulario
const submit = () => {
    form.put(`/admin/tenants/${props.tenant.id}`, {
        preserveScroll: true,
    });
};

// Función para eliminar tenant
const deleteTenant = () => {
    if (confirm(`¿Estás seguro de eliminar el tenant "${props.tenant.name}"? Esta acción no se puede deshacer.`)) {
        router.delete(`/admin/tenants/${props.tenant.id}`);
    }
};
</script>

<template>
    <Head :title="`Editar Tenant: ${tenant.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Editar Tenant</h2>
                    <p class="text-muted-foreground">
                        Modifica la configuración de {{ tenant.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="tenant.id !== 1"
                        @click="deleteTenant"
                        variant="destructive"
                        :disabled="userCount > 0"
                    >
                        Eliminar Tenant
                    </Button>
                    <Link :href="route('admin.tenants.index')">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Estadísticas del Tenant -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Usuarios</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ userCount }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ form.limits.max_users ? `de ${form.limits.max_users} máximo` : 'Sin límite' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Votaciones</CardTitle>
                        <Vote class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ votacionCount }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ form.limits.max_votaciones ? `de ${form.limits.max_votaciones} máximo` : 'Sin límite' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Convocatorias</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ convocatoriaCount }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ form.limits.max_convocatorias ? `de ${form.limits.max_convocatorias} máximo` : 'Sin límite' }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Alerta para tenant principal -->
            <Alert v-if="tenant.id === 1">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Este es el tenant principal del sistema. Algunas opciones pueden estar limitadas.
                </AlertDescription>
            </Alert>

            <!-- Formulario -->
            <form @submit.prevent="submit">
                <Tabs default-value="general" class="w-full">
                    <TabsList class="grid w-full grid-cols-3">
                        <TabsTrigger value="general">Información General</TabsTrigger>
                        <TabsTrigger value="settings">Configuración</TabsTrigger>
                        <TabsTrigger value="limits">Límites y Cuotas</TabsTrigger>
                    </TabsList>

                    <!-- Tab Información General -->
                    <TabsContent value="general">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información General</CardTitle>
                                <CardDescription>
                                    Datos básicos de la organización
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <!-- Nombre -->
                                    <div class="space-y-2">
                                        <Label for="name">Nombre de la Organización *</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            placeholder="Mi Organización"
                                            required
                                        />
                                        <p v-if="form.errors.name" class="text-sm text-red-500">
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- Subdominio -->
                                    <div class="space-y-2">
                                        <Label for="subdomain">Subdominio *</Label>
                                        <div class="flex">
                                            <Input
                                                id="subdomain"
                                                v-model="form.subdomain"
                                                placeholder="mi-organizacion"
                                                pattern="[a-z0-9-]+"
                                                required
                                                class="rounded-r-none"
                                            />
                                            <span class="inline-flex items-center rounded-r-md border border-l-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                                .votaciones.test
                                            </span>
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Solo letras minúsculas, números y guiones
                                        </p>
                                        <p v-if="form.errors.subdomain" class="text-sm text-red-500">
                                            {{ form.errors.subdomain }}
                                        </p>
                                    </div>

                                    <!-- Plan de Suscripción -->
                                    <div class="space-y-2">
                                        <Label for="subscription_plan">Plan de Suscripción *</Label>
                                        <Select v-model="form.subscription_plan">
                                            <SelectTrigger id="subscription_plan">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="(label, value) in subscriptionPlans"
                                                    :key="value"
                                                    :value="value"
                                                >
                                                    {{ label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.subscription_plan" class="text-sm text-red-500">
                                            {{ form.errors.subscription_plan }}
                                        </p>
                                    </div>

                                    <!-- Estado Activo -->
                                    <div class="space-y-2">
                                        <Label for="active">Estado</Label>
                                        <div class="flex items-center space-x-2">
                                            <Switch
                                                id="active"
                                                v-model:checked="form.active"
                                            />
                                            <Label for="active" class="font-normal">
                                                {{ form.active ? 'Activo' : 'Inactivo' }}
                                            </Label>
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Los tenants inactivos no pueden acceder al sistema
                                        </p>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="mt-4 rounded-lg bg-muted p-4">
                                    <div class="grid gap-2 text-sm">
                                        <div>
                                            <span class="font-medium">ID del Tenant:</span>
                                            <span class="ml-2">{{ tenant.id }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Creado:</span>
                                            <span class="ml-2">{{ new Date(tenant.created_at).toLocaleString() }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Última actualización:</span>
                                            <span class="ml-2">{{ new Date(tenant.updated_at).toLocaleString() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Configuración -->
                    <TabsContent value="settings">
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración del Sistema</CardTitle>
                                <CardDescription>
                                    Personalización y configuraciones específicas
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <!-- Logo URL -->
                                    <div class="space-y-2">
                                        <Label for="logo">URL del Logo</Label>
                                        <Input
                                            id="logo"
                                            v-model="form.settings.logo"
                                            placeholder="https://ejemplo.com/logo.png"
                                            type="url"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            URL de la imagen del logo de la organización
                                        </p>
                                    </div>

                                    <!-- Color Primario -->
                                    <div class="space-y-2">
                                        <Label for="primary_color">Color Primario</Label>
                                        <div class="flex gap-2">
                                            <Input
                                                id="primary_color"
                                                v-model="form.settings.primary_color"
                                                placeholder="#3B82F6"
                                                pattern="#[0-9A-Fa-f]{6}"
                                            />
                                            <Input
                                                type="color"
                                                v-model="form.settings.primary_color"
                                                class="w-12 p-1"
                                            />
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Color principal de la interfaz
                                        </p>
                                    </div>

                                    <!-- Expiración OTP -->
                                    <div class="space-y-2">
                                        <Label for="otp_expiration">Expiración OTP (minutos)</Label>
                                        <Input
                                            id="otp_expiration"
                                            v-model.number="form.settings.otp_expiration"
                                            type="number"
                                            min="5"
                                            max="60"
                                            placeholder="10"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Tiempo de validez del código OTP (5-60 minutos)
                                        </p>
                                    </div>

                                    <!-- Zona Horaria -->
                                    <div class="space-y-2">
                                        <Label for="timezone">Zona Horaria</Label>
                                        <Select v-model="form.settings.timezone">
                                            <SelectTrigger id="timezone">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="(label, value) in timezones"
                                                    :key="value"
                                                    :value="value"
                                                >
                                                    {{ label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-muted-foreground">
                                            Zona horaria por defecto para votaciones
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Límites y Cuotas -->
                    <TabsContent value="limits">
                        <Card>
                            <CardHeader>
                                <CardTitle>Límites y Cuotas</CardTitle>
                                <CardDescription>
                                    Define los límites de recursos para esta organización
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-3">
                                    <!-- Máximo de Usuarios -->
                                    <div class="space-y-2">
                                        <Label for="max_users">Usuarios Máximos</Label>
                                        <Input
                                            id="max_users"
                                            v-model.number="form.limits.max_users"
                                            type="number"
                                            min="0"
                                            placeholder="Sin límite"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Actual: {{ userCount }} usuarios
                                        </p>
                                    </div>

                                    <!-- Máximo de Votaciones -->
                                    <div class="space-y-2">
                                        <Label for="max_votaciones">Votaciones Máximas</Label>
                                        <Input
                                            id="max_votaciones"
                                            v-model.number="form.limits.max_votaciones"
                                            type="number"
                                            min="0"
                                            placeholder="Sin límite"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Actual: {{ votacionCount }} votaciones
                                        </p>
                                    </div>

                                    <!-- Máximo de Convocatorias -->
                                    <div class="space-y-2">
                                        <Label for="max_convocatorias">Convocatorias Máximas</Label>
                                        <Input
                                            id="max_convocatorias"
                                            v-model.number="form.limits.max_convocatorias"
                                            type="number"
                                            min="0"
                                            placeholder="Sin límite"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Actual: {{ convocatoriaCount }} convocatorias
                                        </p>
                                    </div>
                                </div>

                                <Alert v-if="userCount > (form.limits.max_users || 0) && form.limits.max_users">
                                    <AlertCircle class="h-4 w-4" />
                                    <AlertDescription>
                                        El número actual de usuarios excede el límite establecido.
                                    </AlertDescription>
                                </Alert>

                                <div class="rounded-lg bg-muted p-4">
                                    <p class="text-sm text-muted-foreground">
                                        <strong>Nota:</strong> Los límites se aplican según el plan de suscripción.
                                        Un valor vacío significa que no hay límite establecido para ese recurso.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                <!-- Botones de acción -->
                <div class="mt-6 flex justify-end gap-4">
                    <Link :href="route('admin.tenants.index')">
                        <Button type="button" variant="outline">
                            Cancelar
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>