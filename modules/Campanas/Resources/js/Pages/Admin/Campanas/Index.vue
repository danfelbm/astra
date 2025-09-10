<script setup lang="ts">
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Progress } from "@modules/Core/Resources/js/components/ui/progress";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, Play, Pause, BarChart3, Eye, Edit, Mail, MessageSquare, Users, Clock, Send } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

interface Campana {
    id: number;
    nombre: string;
    descripcion?: string;
    tipo: 'email' | 'whatsapp' | 'ambos';
    estado: 'borrador' | 'programada' | 'enviando' | 'completada' | 'pausada' | 'cancelada';
    segment?: { id: number; name: string; users_count: number };
    metricas?: any;
    progreso?: {
        porcentaje: number;
        enviados: number;
        pendientes: number;
        fallidos: number;
        total: number;
    };
    fecha_programada?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    created_at: string;
}

interface Props {
    campanas: {
        data: Campana[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: any;
    canCreate?: boolean;
    canEdit?: boolean;
    canSend?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/campanas' },
];

const searchQuery = ref(props.filters?.search || '');
const selectedEstado = ref(props.filters?.estado || 'all');

const applyFilters = () => {
    router.get('/admin/campanas', {
        search: searchQuery.value,
        estado: selectedEstado.value !== 'all' ? selectedEstado.value : undefined,
    }, { preserveState: true, preserveScroll: true });
};

const getEstadoBadge = (estado: string) => {
    const badges: any = {
        'borrador': { variant: 'secondary', icon: Edit },
        'programada': { variant: 'outline', icon: Clock },
        'enviando': { variant: 'default', icon: Send },
        'completada': { variant: 'default', icon: BarChart3 },
        'pausada': { variant: 'warning', icon: Pause },
        'cancelada': { variant: 'destructive', icon: null }
    };
    return badges[estado] || badges['borrador'];
};

const getTipoIcon = (tipo: string) => {
    return tipo === 'email' ? Mail : tipo === 'whatsapp' ? MessageSquare : Send;
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Campañas" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Campañas</h1>
                    <p class="text-muted-foreground mt-1">
                        Gestiona y monitorea tus campañas de email y WhatsApp
                    </p>
                </div>
                <Link v-if="canCreate" href="/admin/campanas/create">
                    <Button>
                        <Plus class="w-4 h-4 mr-2" />
                        Nueva Campaña
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Buscar campañas..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                        <Select v-model="selectedEstado" @update:modelValue="applyFilters">
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="borrador">Borrador</SelectItem>
                                <SelectItem value="programada">Programada</SelectItem>
                                <SelectItem value="enviando">Enviando</SelectItem>
                                <SelectItem value="completada">Completada</SelectItem>
                                <SelectItem value="pausada">Pausada</SelectItem>
                                <SelectItem value="cancelada">Cancelada</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Segmento</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Progreso</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="campana in campanas.data" :key="campana.id">
                                <TableCell class="font-medium">
                                    <div>
                                        <div class="font-medium">{{ campana.nombre }}</div>
                                        <div v-if="campana.descripcion" class="text-sm text-muted-foreground">
                                            {{ campana.descripcion }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <component :is="getTipoIcon(campana.tipo)" class="w-4 h-4" />
                                        <span class="capitalize">{{ campana.tipo }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="campana.segment" class="flex items-center gap-1">
                                        <Users class="w-4 h-4 text-muted-foreground" />
                                        <span class="text-sm">{{ campana.segment.name }}</span>
                                        <Badge variant="secondary" class="ml-1">
                                            {{ campana.segment.users_count }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getEstadoBadge(campana.estado).variant">
                                        <component 
                                            v-if="getEstadoBadge(campana.estado).icon" 
                                            :is="getEstadoBadge(campana.estado).icon" 
                                            class="w-3 h-3 mr-1" 
                                        />
                                        {{ campana.estado }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div v-if="campana.progreso" class="space-y-1">
                                        <Progress 
                                            :value="campana.progreso.porcentaje || 0" 
                                            class="w-full h-2"
                                        />
                                        <div class="flex justify-between text-xs text-muted-foreground">
                                            <span>{{ campana.progreso.enviados || 0 }} / {{ campana.progreso.total || 0 }}</span>
                                            <span>{{ campana.progreso.porcentaje || 0 }}%</span>
                                        </div>
                                    </div>
                                    <div v-else-if="campana.metricas" class="space-y-1">
                                        <Progress 
                                            :value="((campana.metricas.total_enviados || 0) / (campana.metricas.total_destinatarios || 1) * 100)" 
                                            class="w-full h-2"
                                        />
                                        <div class="flex justify-between text-xs text-muted-foreground">
                                            <span>{{ campana.metricas.total_enviados || 0 }} / {{ campana.metricas.total_destinatarios || 0 }}</span>
                                            <span>{{ ((campana.metricas.total_enviados || 0) / (campana.metricas.total_destinatarios || 1) * 100).toFixed(0) }}%</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">-</span>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm">
                                        {{ new Date(campana.created_at).toLocaleDateString('es-ES') }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Link :href="`/admin/campanas/${campana.id}`">
                                            <Button variant="ghost" size="sm">
                                                <Eye class="w-4 h-4" />
                                            </Button>
                                        </Link>
                                        <Link v-if="canEdit && campana.estado === 'borrador'" :href="`/admin/campanas/${campana.id}/edit`">
                                            <Button variant="ghost" size="sm">
                                                <Edit class="w-4 h-4" />
                                            </Button>
                                        </Link>
                                        <Button v-if="canSend && campana.estado === 'borrador'" variant="ghost" size="sm">
                                            <Play class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="campanas.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8">
                                    <div class="text-muted-foreground">
                                        <Send class="w-12 h-12 mx-auto mb-3 opacity-50" />
                                        <p>No se encontraron campañas</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div v-if="campanas.last_page > 1" class="flex justify-center">
                <nav class="flex gap-1">
                    <Link
                        v-for="link in campanas.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-md',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-background hover:bg-muted',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>