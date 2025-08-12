<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Save } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Props {
    subscriptionPlans: Record<string, string>;
    timezones: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: 'Crear Tenant', href: '#' },
];

// Formulario con valores iniciales
const form = useForm({
    name: '',
    subdomain: '',
    subscription_plan: 'basic',
    active: true,
    settings: {
        logo: '',
        primary_color: '#3B82F6',
        otp_expiration: 10,
        timezone: 'America/Bogota',
    },
    limits: {
        max_users: null as number | null,
        max_votaciones: null as number | null,
        max_convocatorias: null as number | null,
    },
});

// Función para enviar formulario
const submit = () => {
    form.post('/admin/tenants', {
        preserveScroll: true,
    });
};

// Función para generar subdomain desde el nombre
const generateSubdomain = () => {
    if (form.name && !form.subdomain) {
        form.subdomain = form.name
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }
};
</script>

<template>
    <Head title="Crear Tenant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Crear Nuevo Tenant</h2>
                    <p class="text-muted-foreground">
                        Configura una nueva organización en el sistema
                    </p>
                </div>
                <Link :href="route('admin.tenants.index')">
                    <Button variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </Link>
            </div>

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
                                            @blur="generateSubdomain"
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
                                            Dejar vacío para sin límite
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
                                            Dejar vacío para sin límite
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
                                            Dejar vacío para sin límite
                                        </p>
                                    </div>
                                </div>

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
                        {{ form.processing ? 'Guardando...' : 'Crear Tenant' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>