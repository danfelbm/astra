<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AdvancedFilters from '@/components/filters/AdvancedFilters.vue';
import { ref, onMounted } from 'vue';
import { ArrowLeft, Save, Target, Filter, Settings, Users, AlertCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

interface Segment {
    id: number;
    name: string;
    description?: string;
    model_type: string;
    filters: any;
    is_dynamic: boolean;
    cache_duration: number;
    metadata?: {
        contacts_count: number;
        last_calculated_at?: string;
    };
    created_at: string;
    updated_at: string;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    description?: string;
}

interface Props {
    segment: Segment;
    roles: Role[];
    filterFieldsConfig: any[];
    modelTypes: Record<string, string>;
    selectedRoles: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Segmentos', href: '/admin/segments' },
    { title: 'Editar Segmento', href: '#' },
];

const form = useForm({
    name: props.segment.name,
    description: props.segment.description || '',
    model_type: props.segment.model_type,
    filters: props.segment.filters || {},
    is_dynamic: props.segment.is_dynamic,
    cache_duration: props.segment.cache_duration,
    role_ids: props.selectedRoles || [],
});

const advancedFiltersRef = ref(null);

const toggleRole = (roleId: number) => {
    const index = form.role_ids.indexOf(roleId);
    if (index > -1) {
        form.role_ids.splice(index, 1);
    } else {
        form.role_ids.push(roleId);
    }
};

const handleFiltersChanged = (filters: any) => {
    form.filters = filters;
};

const handleSubmit = () => {
    // Los filtros ya están actualizados a través del evento @apply

    form.put(`/admin/segments/${props.segment.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Redirección manejada por el backend
        },
    });
};

const handleDelete = () => {
    if (confirm(`¿Estás seguro de eliminar el segmento "${props.segment.name}"? Esta acción no se puede deshacer.`)) {
        router.delete(`/admin/segments/${props.segment.id}`);
    }
};

const getCacheDurationLabel = (seconds: number): string => {
    if (seconds < 60) return `${seconds} segundos`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutos`;
    return `${Math.floor(seconds / 3600)} horas`;
};

const formatLastCalculated = (metadata?: any): string => {
    if (!metadata?.last_calculated_at) return 'Nunca';
    return new Date(metadata.last_calculated_at).toLocaleString();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Segmento" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Editar Segmento</h2>
                    <p class="text-muted-foreground">
                        Modifica los filtros y configuración del segmento
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        @click="handleDelete"
                        variant="destructive"
                        type="button"
                    >
                        Eliminar Segmento
                    </Button>
                    <Link :href="route('admin.segments.index')">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Información del segmento -->
            <Alert>
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    Este segmento tiene {{ segment.metadata?.contacts_count || 0 }} usuarios. 
                    Última evaluación: {{ formatLastCalculated(segment.metadata) }}
                </AlertDescription>
            </Alert>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <Tabs defaultValue="general" class="space-y-4">
                    <TabsList class="grid w-full grid-cols-4">
                        <TabsTrigger value="general">
                            <Target class="mr-2 h-4 w-4" />
                            General
                        </TabsTrigger>
                        <TabsTrigger value="filters">
                            <Filter class="mr-2 h-4 w-4" />
                            Filtros
                        </TabsTrigger>
                        <TabsTrigger value="settings">
                            <Settings class="mr-2 h-4 w-4" />
                            Configuración
                        </TabsTrigger>
                        <TabsTrigger value="roles">
                            <Users class="mr-2 h-4 w-4" />
                            Roles
                        </TabsTrigger>
                    </TabsList>

                    <!-- Tab General -->
                    <TabsContent value="general">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información General</CardTitle>
                                <CardDescription>
                                    Define el nombre y descripción del segmento
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label htmlFor="name">Nombre del Segmento</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Usuarios por Región Norte"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="space-y-2">
                                    <Label htmlFor="description">Descripción</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Describe el propósito y criterios de este segmento..."
                                        rows="4"
                                    />
                                    <InputError :message="form.errors.description" />
                                </div>

                                <div class="space-y-2">
                                    <Label htmlFor="model_type">Tipo de Modelo</Label>
                                    <Select v-model="form.model_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona el tipo de modelo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="(label, modelType) in modelTypes"
                                                :key="modelType"
                                                :value="modelType"
                                            >
                                                {{ label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-xs text-muted-foreground">
                                        Selecciona el modelo de datos sobre el que aplicar el segmento
                                    </p>
                                    <InputError :message="form.errors.model_type" />
                                </div>

                                <!-- Información adicional -->
                                <div class="pt-4 border-t space-y-2 text-sm text-muted-foreground">
                                    <p>ID del Segmento: {{ segment.id }}</p>
                                    <p>Usuarios actuales: {{ segment.metadata?.contacts_count || 0 }}</p>
                                    <p>Creado: {{ new Date(segment.created_at).toLocaleDateString() }}</p>
                                    <p>Última actualización: {{ new Date(segment.updated_at).toLocaleDateString() }}</p>
                                    <p>Última evaluación: {{ formatLastCalculated(segment.metadata) }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Filtros -->
                    <TabsContent value="filters">
                        <Card>
                            <CardHeader>
                                <CardTitle>Criterios de Filtrado</CardTitle>
                                <CardDescription>
                                    Modifica los filtros que determinan qué usuarios pertenecen a este segmento
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <AdvancedFilters
                                    ref="advancedFiltersRef"
                                    :config="{ fields: filterFieldsConfig, showQuickSearch: false }"
                                    route=""
                                    :initialFilters="segment.filters?.advanced_filters ? { rootGroup: segment.filters.advanced_filters } : undefined"
                                    @apply="handleFiltersChanged"
                                />
                                <InputError :message="form.errors.filters" />
                                <div class="mt-4 p-4 bg-muted/50 rounded-lg">
                                    <p class="text-sm text-muted-foreground">
                                        <strong>Nota:</strong> Los cambios en los filtros actualizarán automáticamente 
                                        el segmento y recalcularán los usuarios que pertenecen a él.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Configuración -->
                    <TabsContent value="settings">
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración del Segmento</CardTitle>
                                <CardDescription>
                                    Ajusta el comportamiento y cache del segmento
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <Label>Tipo de Segmento</Label>
                                        <p class="text-sm text-muted-foreground">
                                            Los segmentos dinámicos se recalculan en tiempo real
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Label htmlFor="is_dynamic" class="text-sm">Dinámico</Label>
                                        <Switch
                                            id="is_dynamic"
                                            v-model="form.is_dynamic"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label htmlFor="cache_duration">Duración del Cache (segundos)</Label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <Input
                                            id="cache_duration"
                                            v-model.number="form.cache_duration"
                                            type="number"
                                            min="0"
                                            max="86400"
                                        />
                                        <div class="flex items-center px-3 py-2 bg-muted rounded-md">
                                            <span class="text-sm text-muted-foreground">
                                                {{ getCacheDurationLabel(form.cache_duration) }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Tiempo que se mantendrán en cache los resultados del segmento (0 = sin cache)
                                    </p>
                                    <InputError :message="form.errors.cache_duration" />
                                </div>

                                <!-- Valores predefinidos -->
                                <div>
                                    <Label class="text-sm font-medium">Valores Predefinidos</Label>
                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="form.cache_duration = 60"
                                        >
                                            1 minuto
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="form.cache_duration = 300"
                                        >
                                            5 minutos
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="form.cache_duration = 1800"
                                        >
                                            30 minutos
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="form.cache_duration = 3600"
                                        >
                                            1 hora
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Tab Roles -->
                    <TabsContent value="roles">
                        <Card>
                            <CardHeader>
                                <CardTitle>Roles Asociados</CardTitle>
                                <CardDescription>
                                    Vincula este segmento con roles específicos para delimitar el alcance de datos
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="roles.length > 0" class="space-y-3">
                                    <div
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="flex items-start space-x-3 p-3 border rounded-lg hover:bg-muted/50"
                                    >
                                        <Checkbox
                                            :checked="form.role_ids.includes(role.id)"
                                            @update:checked="toggleRole(role.id)"
                                            class="mt-1"
                                        />
                                        <div class="flex-1">
                                            <Label class="text-sm font-medium cursor-pointer">
                                                {{ role.display_name }}
                                            </Label>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                {{ role.description || 'Sin descripción' }}
                                            </p>
                                            <Badge variant="outline" class="mt-2">
                                                {{ role.name }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-muted-foreground">
                                    No hay roles disponibles
                                </div>
                                <InputError :message="form.errors.role_ids" />
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

                <!-- Botones de acción -->
                <div class="flex justify-end gap-4">
                    <Link :href="route('admin.segments.index')">
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