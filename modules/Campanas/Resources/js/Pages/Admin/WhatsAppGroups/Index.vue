<script setup lang="ts">
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent } from "@modules/Core/Resources/js/components/ui/card";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@modules/Core/Resources/js/components/ui/dialog";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Alert, AlertDescription } from "@modules/Core/Resources/js/components/ui/alert";
import { Avatar, AvatarFallback, AvatarImage } from "@modules/Core/Resources/js/components/ui/avatar";
import Pagination from "@modules/Core/Resources/js/components/ui/pagination/Pagination.vue";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, router, useForm } from '@inertiajs/vue3';
import { RefreshCw, Plus, Search, Users, MessageSquare, Eye, Trash2, Loader2, Info } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { debounce } from 'lodash-es';

interface WhatsAppGroup {
    id: number;
    group_jid: string;
    nombre: string;
    descripcion?: string;
    tipo: 'grupo' | 'comunidad';
    avatar_url?: string;
    participantes_count: number;
    is_announce: boolean;
    is_restrict: boolean;
    synced_at?: string;
}

interface Props {
    grupos: {
        data: WhatsAppGroup[];
        links: any;
        meta?: any;
    };
    filters: {
        search?: string;
        tipo?: string;
    };
    tiposOptions: { value: string; label: string }[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/envio-campanas' },
    { title: 'Grupos de WhatsApp', href: '#' },
];

// Estado local para filtros
const search = ref(props.filters.search || '');
const tipo = ref(props.filters.tipo || 'all');

// Estado para sincronización
const isSyncing = ref(false);

// Estado para modal de añadir por JID
const showAddModal = ref(false);
const groupJidInput = ref('');
const isPreviewingJid = ref(false);
const jidPreview = ref<any>(null);
const jidError = ref('');
const isAddingGroup = ref(false);

// Aplicar filtros con debounce
const applyFilters = debounce(() => {
    router.get('/admin/whatsapp-groups', {
        search: search.value || undefined,
        tipo: tipo.value && tipo.value !== 'all' ? tipo.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([search, tipo], () => {
    applyFilters();
});

// Sincronizar todos los grupos
const syncGroups = () => {
    if (isSyncing.value) return;

    if (!confirm('¿Sincronizar todos los grupos desde WhatsApp?\n\nEsta operación puede tardar hasta 5 minutos.')) {
        return;
    }

    isSyncing.value = true;

    router.post('/admin/whatsapp-groups/sync', {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Grupos sincronizados');
        },
        onError: () => {
            toast.error('Error al sincronizar grupos');
        },
        onFinish: () => {
            isSyncing.value = false;
        },
    });
};

// Abrir modal para añadir grupo por JID
const openAddModal = () => {
    groupJidInput.value = '';
    jidPreview.value = null;
    jidError.value = '';
    showAddModal.value = true;
};

// Previsualizar grupo por JID
const previewJid = async () => {
    if (!groupJidInput.value.trim()) {
        jidError.value = 'Ingresa un Group ID';
        return;
    }

    isPreviewingJid.value = true;
    jidError.value = '';
    jidPreview.value = null;

    try {
        const response = await axios.post('/admin/whatsapp-groups/preview-jid', {
            group_jid: groupJidInput.value.trim(),
        });

        if (response.data.success) {
            jidPreview.value = response.data.preview;
        } else {
            jidError.value = response.data.message || 'Grupo no encontrado';
        }
    } catch (error: any) {
        jidError.value = error.response?.data?.message || 'Error al buscar el grupo';
    } finally {
        isPreviewingJid.value = false;
    }
};

// Añadir grupo desde preview
const addGroupFromPreview = () => {
    if (!jidPreview.value) return;

    isAddingGroup.value = true;

    router.post('/admin/whatsapp-groups/add-by-jid', {
        group_jid: groupJidInput.value.trim(),
    }, {
        onSuccess: () => {
            showAddModal.value = false;
            toast.success('Grupo añadido correctamente');
        },
        onError: () => {
            toast.error('Error al añadir el grupo');
        },
        onFinish: () => {
            isAddingGroup.value = false;
        },
    });
};

// Ver detalles de un grupo
const viewGroup = (grupo: WhatsAppGroup) => {
    router.visit(`/admin/whatsapp-groups/${grupo.id}`);
};

// Eliminar grupo
const deleteGroup = (grupo: WhatsAppGroup) => {
    if (!confirm(`¿Eliminar el grupo "${grupo.nombre}" de la base de datos?\n\nEsto no afecta al grupo en WhatsApp.`)) {
        return;
    }

    router.delete(`/admin/whatsapp-groups/${grupo.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Grupo eliminado');
        },
        onError: () => {
            toast.error('Error al eliminar el grupo');
        },
    });
};

// Formatear JID para mostrar
const formatJid = (jid: string) => {
    return jid.replace('@g.us', '');
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Grupos de WhatsApp" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold">Grupos de WhatsApp</h1>
                    <p class="text-muted-foreground mt-1">
                        Gestiona los grupos sincronizados desde Evolution API
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="syncGroups" :disabled="isSyncing">
                        <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': isSyncing }" />
                        {{ isSyncing ? 'Sincronizando...' : 'Sincronizar Grupos' }}
                    </Button>
                    <Button @click="openAddModal">
                        <Plus class="w-4 h-4 mr-2" />
                        Añadir Grupo
                    </Button>
                </div>
            </div>

            <!-- Filtros -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Buscar grupos..."
                                    class="pl-10"
                                />
                            </div>
                        </div>
                        <div class="w-full sm:w-48">
                            <Select v-model="tipo">
                                <SelectTrigger>
                                    <SelectValue placeholder="Filtrar por tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in tiposOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de grupos -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12"></TableHead>
                                <TableHead>Nombre del Grupo</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-center">Participantes</TableHead>
                                <TableHead>Group ID</TableHead>
                                <TableHead class="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="grupo in grupos.data"
                                :key="grupo.id"
                                class="cursor-pointer hover:bg-muted/50"
                                @click="viewGroup(grupo)"
                            >
                                <TableCell @click.stop>
                                    <Avatar class="h-10 w-10">
                                        <AvatarImage :src="grupo.avatar_url || undefined" />
                                        <AvatarFallback>
                                            <MessageSquare class="h-5 w-5" />
                                        </AvatarFallback>
                                    </Avatar>
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <div class="font-medium">{{ grupo.nombre }}</div>
                                        <div v-if="grupo.descripcion" class="text-sm text-muted-foreground truncate max-w-xs">
                                            {{ grupo.descripcion }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="grupo.tipo === 'comunidad' ? 'default' : 'secondary'">
                                        {{ grupo.tipo === 'comunidad' ? 'COMUNIDAD' : 'GRUPO' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        {{ grupo.participantes_count.toLocaleString() }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <code class="text-xs bg-muted px-2 py-1 rounded">
                                        {{ formatJid(grupo.group_jid) }}@g.us
                                    </code>
                                </TableCell>
                                <TableCell class="text-right" @click.stop>
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" @click="viewGroup(grupo)">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="deleteGroup(grupo)">
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="grupos.data.length === 0">
                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                    <MessageSquare class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p>No hay grupos sincronizados</p>
                                    <Button variant="link" @click="syncGroups" class="mt-2">
                                        Sincronizar ahora
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <Pagination v-if="grupos.links" :links="grupos.links" />
        </div>

        <!-- Modal: Añadir Grupo por ID -->
        <Dialog v-model:open="showAddModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Añadir Grupo por ID</DialogTitle>
                    <DialogDescription>
                        Ingresa el ID del grupo de WhatsApp (debe terminar en @g.us)
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <Label for="group-jid">Group ID (Remote JID): *</Label>
                        <Input
                            id="group-jid"
                            v-model="groupJidInput"
                            placeholder="Ej: 120363295648424210@g.us"
                            @keyup.enter="previewJid"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Ingresa el ID del grupo de WhatsApp (debe terminar en @g.us)
                        </p>
                    </div>

                    <!-- Error -->
                    <Alert v-if="jidError" variant="destructive">
                        <AlertDescription>{{ jidError }}</AlertDescription>
                    </Alert>

                    <!-- Preview del grupo -->
                    <div v-if="jidPreview" class="border rounded-lg p-4 bg-muted/50">
                        <div class="flex items-start gap-3">
                            <Avatar class="h-12 w-12">
                                <AvatarImage :src="jidPreview.pictureUrl || undefined" />
                                <AvatarFallback>
                                    <MessageSquare class="h-6 w-6" />
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium">{{ jidPreview.subject }}</h4>
                                <p v-if="jidPreview.desc" class="text-sm text-muted-foreground truncate">
                                    {{ jidPreview.desc }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <Badge variant="secondary">
                                        <Users class="h-3 w-3 mr-1" />
                                        {{ jidPreview.size }} participantes
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="showAddModal = false">
                        Cancelar
                    </Button>
                    <Button
                        v-if="!jidPreview"
                        @click="previewJid"
                        :disabled="isPreviewingJid || !groupJidInput.trim()"
                    >
                        <Loader2 v-if="isPreviewingJid" class="w-4 h-4 mr-2 animate-spin" />
                        Previsualizar
                    </Button>
                    <Button
                        v-else
                        @click="addGroupFromPreview"
                        :disabled="isAddingGroup"
                    >
                        <Loader2 v-if="isAddingGroup" class="w-4 h-4 mr-2 animate-spin" />
                        Añadir Grupo
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
