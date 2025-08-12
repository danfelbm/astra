<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { ref } from 'vue';
import { ArrowLeft, Edit, Target, Users, Settings, Filter, RefreshCw, Trash, Calendar, Hash, User, Mail } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    activo: boolean;
    created_at: string;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
}

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
    roles?: Role[];
    created_by?: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    segment: Segment;
    users: User[] | {
        data: User[];
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    metadata: any;
    isPaginated: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Administración', href: '#' },
    { title: 'Segmentos', href: '/admin/segments' },
    { title: props.segment.name, href: '#' },
];

const isEvaluating = ref(false);

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (date: string): string => {
    return new Date(date).toLocaleString('es-ES');
};

const formatCacheDuration = (seconds: number): string => {
    if (seconds < 60) return `${seconds} segundos`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutos`;
    return `${Math.floor(seconds / 3600)} horas`;
};

const formatModelType = (modelType: string): string => {
    return modelType.split('\\').pop() || modelType;
};

const formatLastCalculated = (metadata?: any): string => {
    if (!metadata?.last_calculated_at) return 'Nunca';
    return new Date(metadata.last_calculated_at).toLocaleString();
};

const handleEvaluateSegment = async () => {
    isEvaluating.value = true;
    try {
        const response = await fetch(`/admin/segments/${props.segment.id}/evaluate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const result = await response.json();
        
        if (result.success) {
            // Recargar la página para mostrar los nuevos datos
            router.reload();
        }
    } catch (error) {
        console.error('Error evaluating segment:', error);
    } finally {
        isEvaluating.value = false;
    }
};

const handleClearCache = () => {
    router.post(`/admin/segments/${props.segment.id}/clear-cache`);
};

const getUsersData = () => {
    return props.isPaginated ? props.users as any : { data: props.users as User[] };
};

const renderFilterValue = (value: any): string => {
    if (Array.isArray(value)) {
        return value.join(', ');
    }
    if (typeof value === 'object' && value !== null) {
        return JSON.stringify(value);
    }
    return String(value);
};

const renderFilters = (filters: any): any[] => {
    if (!filters || typeof filters !== 'object') return [];
    
    const result = [];
    for (const [key, value] of Object.entries(filters)) {
        if (value !== null && value !== undefined) {
            result.push({ field: key, value });
        }
    }
    return result;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Segmento: ${segment.name}`" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <Target class="h-8 w-8 text-primary" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">{{ segment.name }}</h2>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="segment.is_dynamic ? 'default' : 'secondary'">
                                {{ segment.is_dynamic ? 'Dinámico' : 'Estático' }}
                            </Badge>
                            <Badge variant="outline">{{ formatModelType(segment.model_type) }}</Badge>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        @click="handleEvaluateSegment"
                        variant="secondary"
                        :disabled="isEvaluating"
                    >
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isEvaluating }" />
                        {{ isEvaluating ? 'Evaluando...' : 'Recalcular' }}
                    </Button>
                    <Button
                        @click="handleClearCache"
                        variant="ghost"
                    >
                        <Trash class="mr-2 h-4 w-4" />
                        Limpiar Cache
                    </Button>
                    <Link :href="`/admin/segments/${segment.id}/edit`">
                        <Button>
                            <Edit class="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                    <Link :href="route('admin.segments.index')">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid gap-6 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Usuarios Totales</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metadata?.contacts_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">usuarios en este segmento</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Cache</CardTitle>
                        <Settings class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCacheDuration(segment.cache_duration) }}</div>
                        <p class="text-xs text-muted-foreground">duración del cache</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Última Evaluación</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-bold">{{ formatLastCalculated(metadata) }}</div>
                        <p class="text-xs text-muted-foreground">última actualización</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Roles</CardTitle>
                        <Hash class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ segment.roles?.length || 0 }}</div>
                        <p class="text-xs text-muted-foreground">roles asociados</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs con detalles -->
            <Tabs defaultValue="users" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="users">Usuarios</TabsTrigger>
                    <TabsTrigger value="filters">Filtros</TabsTrigger>
                    <TabsTrigger value="roles">Roles</TabsTrigger>
                    <TabsTrigger value="details">Detalles</TabsTrigger>
                </TabsList>

                <!-- Tab Usuarios -->
                <TabsContent value="users">
                    <Card>
                        <CardHeader>
                            <CardTitle>Usuarios en este Segmento</CardTitle>
                            <CardDescription>
                                Lista de usuarios que cumplen con los criterios del segmento
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table v-if="getUsersData().data?.length">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Usuario</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Registro</TableHead>
                                        <TableHead class="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="user in getUsersData().data" :key="user.id">
                                        <TableCell>
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-8 w-8">
                                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                                    <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                                                </Avatar>
                                                <span class="font-medium">{{ user.name }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1">
                                                <Mail class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm">{{ user.email }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="user.activo ? 'default' : 'secondary'">
                                                {{ user.activo ? 'Activo' : 'Inactivo' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                                        <TableCell class="text-right">
                                            <Link :href="`/admin/users/${user.id}`">
                                                <Button size="sm" variant="ghost">
                                                    <User class="mr-2 h-4 w-4" />
                                                    Ver Usuario
                                                </Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No hay usuarios en este segmento
                            </div>

                            <!-- Información de paginación -->
                            <div v-if="isPaginated" class="mt-4 text-sm text-muted-foreground text-center">
                                Mostrando {{ (users as any).data?.length }} de {{ (users as any).total }} usuarios
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
                                Filtros aplicados para determinar los usuarios de este segmento
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="segment.filters && Object.keys(segment.filters).length > 0">
                                <ScrollArea class="h-[400px]">
                                    <div class="space-y-4">
                                        <div
                                            v-for="filter in renderFilters(segment.filters)"
                                            :key="filter.field"
                                            class="p-4 border rounded-lg"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-semibold">{{ filter.field }}</p>
                                                    <p class="text-sm text-muted-foreground">
                                                        {{ renderFilterValue(filter.value) }}
                                                    </p>
                                                </div>
                                                <Badge variant="outline">
                                                    {{ typeof filter.value === 'object' ? 'Complejo' : 'Simple' }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </ScrollArea>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No hay filtros definidos para este segmento
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
                                Roles que utilizan este segmento para delimitar el alcance de datos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="segment.roles?.length" class="space-y-3">
                                <div
                                    v-for="role in segment.roles"
                                    :key="role.id"
                                    class="flex items-center justify-between p-4 border rounded-lg"
                                >
                                    <div>
                                        <h4 class="font-semibold">{{ role.display_name }}</h4>
                                        <p class="text-sm text-muted-foreground">{{ role.name }}</p>
                                    </div>
                                    <Link :href="`/admin/roles/${role.id}`">
                                        <Button size="sm" variant="ghost">
                                            Ver Rol
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No hay roles asociados a este segmento
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Tab Detalles -->
                <TabsContent value="details">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Segmento</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">ID del Segmento</p>
                                    <p class="text-sm font-mono">{{ segment.id }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tipo de Modelo</p>
                                    <p class="text-sm font-mono">{{ segment.model_type }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Creado por</p>
                                    <p class="text-sm">{{ segment.created_by?.name || 'Sistema' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Tipo</p>
                                    <Badge :variant="segment.is_dynamic ? 'default' : 'secondary'">
                                        {{ segment.is_dynamic ? 'Dinámico' : 'Estático' }}
                                    </Badge>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Creado</p>
                                    <p class="text-sm">{{ formatDateTime(segment.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Última Actualización</p>
                                    <p class="text-sm">{{ formatDateTime(segment.updated_at) }}</p>
                                </div>
                            </div>
                            <div v-if="segment.description">
                                <p class="text-sm font-medium text-muted-foreground mb-2">Descripción</p>
                                <p class="text-sm">{{ segment.description }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>