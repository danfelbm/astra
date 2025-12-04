<script setup lang="ts">
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import { Badge } from "@modules/Core/Resources/js/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@modules/Core/Resources/js/components/ui/avatar";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@modules/Core/Resources/js/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@modules/Core/Resources/js/components/ui/dialog";
import { type BreadcrumbItemType } from '@/types';
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, RefreshCw, Users, MessageSquare, Calendar, Shield, Volume2, Lock, Loader2, User, Crown, UserCheck } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';

interface WhatsAppGroup {
    id: number;
    group_jid: string;
    nombre: string;
    descripcion?: string;
    tipo: 'grupo' | 'comunidad';
    avatar_url?: string;
    participantes_count: number;
    owner_jid?: string;
    is_announce: boolean;
    is_restrict: boolean;
    synced_at?: string;
    created_at: string;
    updated_at: string;
    metadata?: {
        creation?: number;
        subjectTime?: number;
        descId?: string;
    };
}

interface Participant {
    id: string;
    admin?: 'superadmin' | 'admin' | null;
}

interface Props {
    grupo: WhatsAppGroup;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Campañas', href: '/admin/envio-campanas' },
    { title: 'Grupos de WhatsApp', href: '/admin/whatsapp-groups' },
    { title: props.grupo.nombre, href: '#' },
];

// Estado
const isRefreshing = ref(false);
const isLoadingParticipants = ref(false);
const showParticipantsModal = ref(false);
const participants = ref<Participant[]>([]);

// Actualizar datos del grupo
const refreshGroup = () => {
    isRefreshing.value = true;

    router.post(`/admin/whatsapp-groups/${props.grupo.id}/refresh`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Datos del grupo actualizados');
        },
        onError: () => {
            toast.error('Error al actualizar');
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

// Obtener participantes
const loadParticipants = async () => {
    isLoadingParticipants.value = true;
    participants.value = [];
    showParticipantsModal.value = true;

    try {
        const response = await axios.get(`/admin/whatsapp-groups/${props.grupo.id}/participants`);

        if (response.data.success) {
            participants.value = response.data.participantes || [];
        } else {
            toast.error(response.data.message || 'Error al obtener participantes');
        }
    } catch (error: any) {
        toast.error('Error al obtener participantes');
    } finally {
        isLoadingParticipants.value = false;
    }
};

// Formatear JID de participante
const formatParticipantJid = (jid: string) => {
    return jid.replace('@s.whatsapp.net', '').replace('@lid', '');
};

// Formatear fecha Unix timestamp
const formatTimestamp = (timestamp: number | undefined) => {
    if (!timestamp) return 'N/A';
    return new Date(timestamp * 1000).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Volver a la lista
const goBack = () => {
    router.visit('/admin/whatsapp-groups');
};
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="grupo.nombre" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" @click="goBack">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold">{{ grupo.nombre }}</h1>
                        <Badge :variant="grupo.tipo === 'comunidad' ? 'default' : 'secondary'" class="mt-1">
                            {{ grupo.tipo === 'comunidad' ? 'COMUNIDAD' : 'GRUPO' }}
                        </Badge>
                    </div>
                </div>
                <Button variant="outline" @click="goBack">
                    Volver a grupos
                </Button>
            </div>

            <!-- Información del grupo -->
            <Card>
                <CardHeader>
                    <CardTitle>Información del Grupo</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Avatar y nombre -->
                        <div class="flex items-start gap-4">
                            <div class="space-y-1 text-sm text-muted-foreground w-24">Avatar</div>
                            <Avatar class="h-24 w-24">
                                <AvatarImage :src="grupo.avatar_url || undefined" />
                                <AvatarFallback class="text-2xl">
                                    <MessageSquare class="h-10 w-10" />
                                </AvatarFallback>
                            </Avatar>
                        </div>

                        <!-- Detalles -->
                        <div class="space-y-4">
                            <div class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Nombre</div>
                                <div class="font-medium">{{ grupo.nombre }}</div>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Tipo</div>
                                <Badge :variant="grupo.tipo === 'comunidad' ? 'default' : 'secondary'">
                                    {{ grupo.tipo === 'comunidad' ? 'COMUNIDAD' : 'GRUPO' }}
                                </Badge>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Group ID</div>
                                <code class="text-xs bg-muted px-2 py-1 rounded">{{ grupo.group_jid }}</code>
                            </div>

                            <div v-if="grupo.descripcion" class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Descripción</div>
                                <div class="text-sm whitespace-pre-wrap">{{ grupo.descripcion }}</div>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Participantes</div>
                                <div class="font-medium">{{ grupo.participantes_count.toLocaleString() }}</div>
                            </div>

                            <div v-if="grupo.owner_jid" class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Creador</div>
                                <code class="text-xs bg-muted px-2 py-1 rounded">{{ grupo.owner_jid }}</code>
                            </div>

                            <div v-if="grupo.metadata?.creation" class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Fecha de creación</div>
                                <div class="text-sm">{{ formatTimestamp(grupo.metadata.creation) }}</div>
                            </div>

                            <div class="flex gap-4">
                                <div class="text-sm text-muted-foreground w-24">Restricciones</div>
                                <div class="flex gap-2">
                                    <Badge v-if="!grupo.is_restrict && !grupo.is_announce" variant="outline" class="text-green-600">
                                        <Lock class="h-3 w-3 mr-1" />
                                        Abierto
                                    </Badge>
                                    <Badge v-if="grupo.is_restrict" variant="outline" class="text-yellow-600">
                                        <Lock class="h-3 w-3 mr-1" />
                                        Restringido
                                    </Badge>
                                    <Badge v-if="grupo.is_announce" variant="outline" class="text-orange-600">
                                        <Volume2 class="h-3 w-3 mr-1" />
                                        Solo anuncios
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex gap-2 mt-6 pt-6 border-t">
                        <Button variant="outline" @click="refreshGroup" :disabled="isRefreshing">
                            <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
                            Actualizar Datos del Grupo
                        </Button>
                        <Button variant="outline" @click="loadParticipants">
                            <Users class="h-4 w-4 mr-2" />
                            Obtener Participantes
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modal: Participantes -->
        <Dialog v-model:open="showParticipantsModal">
            <DialogContent class="sm:max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
                <DialogHeader>
                    <DialogTitle>Participantes del Grupo</DialogTitle>
                    <DialogDescription>
                        {{ grupo.nombre }} - {{ participants.length }} participantes
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 overflow-auto">
                    <div v-if="isLoadingParticipants" class="flex items-center justify-center py-8">
                        <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>

                    <Table v-else-if="participants.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Número</TableHead>
                                <TableHead>Rol</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(participant, index) in participants" :key="index">
                                <TableCell>
                                    <code class="text-xs">{{ formatParticipantJid(participant.id) }}</code>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="participant.admin === 'superadmin'" variant="default">
                                        <Crown class="h-3 w-3 mr-1" />
                                        Super Admin
                                    </Badge>
                                    <Badge v-else-if="participant.admin === 'admin'" variant="secondary">
                                        <Shield class="h-3 w-3 mr-1" />
                                        Admin
                                    </Badge>
                                    <span v-else class="text-muted-foreground text-sm">
                                        <User class="h-3 w-3 inline mr-1" />
                                        Miembro
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-else class="text-center py-8 text-muted-foreground">
                        No se pudieron obtener los participantes
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
