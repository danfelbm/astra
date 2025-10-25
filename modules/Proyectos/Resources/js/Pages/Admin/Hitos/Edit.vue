<script setup lang="ts">
import AdminLayout from "@modules/Core/Resources/js/layouts/AdminLayout.vue";
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { Textarea } from "@modules/Core/Resources/js/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import CamposPersonalizadosForm from "@modules/Proyectos/Resources/js/components/CamposPersonalizadosForm.vue";
import EtiquetaSelector from "@modules/Proyectos/Resources/js/components/EtiquetaSelector.vue";
import AddUsersModal from "@modules/Core/Resources/js/components/modals/AddUsersModal.vue";
import { ArrowLeft, Save, UserPlus, X, Tag } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';
import type { Hito } from '@modules/Proyectos/Resources/js/types/hitos';
import type { CategoriaEtiqueta } from "@modules/Proyectos/Resources/js/types/etiquetas";
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Proyecto {
    id: number;
    nombre: string;
}

interface CampoPersonalizado {
    id: number;
    nombre: string;
    tipo: string;
    es_requerido: boolean;
    opciones?: any[];
}

interface HitoDisponible {
    id: number;
    nombre: string;
    ruta_completa: string;
    nivel: number;
}

interface HitoWithResponsable extends Hito {
    responsable?: User;
}

interface Props {
    hito: HitoWithResponsable;
    proyecto: Proyecto;
    responsables: User[];
    hitosDisponibles?: HitoDisponible[];
    camposPersonalizados?: CampoPersonalizado[];
    valoresCamposPersonalizados?: Record<number, any>;
    categorias?: CategoriaEtiqueta[];
    estados: { value: string; label: string }[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Proyectos', href: '/admin/proyectos' },
    { title: props.proyecto.nombre, href: `/admin/proyectos/${props.proyecto.id}` },
    { title: 'Hitos', href: `/admin/proyectos/${props.proyecto.id}/hitos` },
    { title: 'Editar Hito', href: '#' },
];

const form = useForm({
    nombre: props.hito.nombre || '',
    descripcion: props.hito.descripcion || '',
    fecha_inicio: props.hito.fecha_inicio || '',
    fecha_fin: props.hito.fecha_fin || '',
    estado: props.hito.estado || 'pendiente',
    responsable_id: props.hito.responsable_id || null as number | null,
    parent_id: props.hito.parent_id || null as number | null,
    campos_personalizados: props.valoresCamposPersonalizados || {} as Record<number, any>,
    etiquetas: props.hito.etiquetas?.map((e: any) => e.id) ?? [] as number[],
    orden: props.hito.orden || 0,
});

const showResponsableModal = ref(false);
const { route } = window as any;
const responsableSeleccionado = ref<User | null>(null);
const usuariosCache = ref<Map<number, User>>(new Map());

props.responsables.forEach(u => usuariosCache.value.set(u.id, u));

if (props.hito.responsable) {
    responsableSeleccionado.value = props.hito.responsable;
    usuariosCache.value.set(props.hito.responsable.id, props.hito.responsable);
} else if (form.responsable_id) {
    const found = props.responsables.find(u => u.id === form.responsable_id);
    if (found) {
        responsableSeleccionado.value = found;
    }
}

const handleResponsableSelect = (data: { userIds: number[]; extraData: Record<string, any> }) => {
    if (data.userIds.length > 0) {
        const userId = data.userIds[0];
        form.responsable_id = userId;

        if (usuariosCache.value.has(userId)) {
            responsableSeleccionado.value = usuariosCache.value.get(userId) || null;
        } else {
            responsableSeleccionado.value = {
                id: userId,
                name: `Usuario seleccionado (ID: ${userId})`,
                email: 'Se actualizará al guardar'
            } as User;
            usuariosCache.value.set(userId, responsableSeleccionado.value);
        }
    }
};

const submit = () => {
    form.put(`/admin/proyectos/${props.proyecto.id}/hitos/${props.hito.id}`, {
        onSuccess: () => {
            toast.success('Hito actualizado exitosamente');
        },
        onError: () => {
            toast.error('Error al actualizar el hito');
        },
    });
};
</script>

<template>
    <Head :title="`Editar Hito - ${hito.nombre}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Editar Hito</h1>
                <Link :href="`/admin/proyectos/${proyecto.id}/hitos`">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Volver
                    </Button>
                </Link>
            </div>

            <Card class="mb-4">
                <CardHeader>
                    <CardTitle>{{ hito.nombre }}</CardTitle>
                    <CardDescription>
                        Editando hito del proyecto: {{ proyecto.nombre }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-muted-foreground">Progreso</p>
                            <p class="font-semibold">{{ hito.porcentaje_completado || 0 }}%</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Entregables</p>
                            <p class="font-semibold">{{ hito.entregables?.length || 0 }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Creado</p>
                            <p class="font-semibold">{{ format(parseISO(hito.created_at), 'dd/MM/yyyy', { locale: es }) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Última actualización</p>
                            <p class="font-semibold">{{ format(parseISO(hito.updated_at), 'dd/MM/yyyy', { locale: es }) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Información del Hito</CardTitle>
                    <CardDescription>
                        Modifica los detalles del hito
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="nombre" required>Nombre del Hito</Label>
                            <Input id="nombre" v-model="form.nombre" type="text" placeholder="Ej: Pre-producción" :disabled="form.processing" />
                            <span v-if="form.errors.nombre" class="text-sm text-red-500">{{ form.errors.nombre }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="descripcion">Descripción</Label>
                            <Textarea id="descripcion" v-model="form.descripcion" placeholder="Describe el objetivo y alcance del hito..." :disabled="form.processing" rows="3" />
                            <span v-if="form.errors.descripcion" class="text-sm text-red-500">{{ form.errors.descripcion }}</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="fecha_inicio">Fecha de Inicio</Label>
                                <Input id="fecha_inicio" v-model="form.fecha_inicio" type="date" :disabled="form.processing" />
                                <span v-if="form.errors.fecha_inicio" class="text-sm text-red-500">{{ form.errors.fecha_inicio }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="fecha_fin">Fecha de Fin</Label>
                                <Input id="fecha_fin" v-model="form.fecha_fin" type="date" :disabled="form.processing" />
                                <span v-if="form.errors.fecha_fin" class="text-sm text-red-500">{{ form.errors.fecha_fin }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="estado">Estado</Label>
                                <Select v-model="form.estado">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="estado in estados" :key="estado.value" :value="estado.value">
                                            {{ estado.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.estado" class="text-sm text-red-500">{{ form.errors.estado }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="responsable_id">Responsable</Label>
                                <div class="space-y-2">
                                    <div v-if="responsableSeleccionado" class="p-3 bg-muted rounded-lg flex items-center justify-between">
                                        <div>
                                            <p class="font-medium">{{ responsableSeleccionado.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ responsableSeleccionado.email }}</p>
                                        </div>
                                        <Button type="button" variant="ghost" size="sm" @click="form.responsable_id = null; responsableSeleccionado = null">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <Button type="button" variant="outline" @click="showResponsableModal = true" class="w-full">
                                        <UserPlus class="h-4 w-4 mr-2" />
                                        {{ responsableSeleccionado ? 'Cambiar Responsable' : 'Seleccionar Responsable' }}
                                    </Button>
                                </div>
                                <span v-if="form.errors.responsable_id" class="text-sm text-red-500">{{ form.errors.responsable_id }}</span>
                            </div>
                        </div>

                        <div v-if="hitosDisponibles && hitosDisponibles.length > 0" class="space-y-2">
                            <Label for="parent_id">Hito Padre (opcional)</Label>
                            <Select v-model="form.parent_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Sin padre (raíz)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Sin padre (raíz)</SelectItem>
                                    <SelectItem
                                        v-for="hitoOpt in hitosDisponibles"
                                        :key="hitoOpt.id"
                                        :value="hitoOpt.id"
                                        :disabled="hitoOpt.id === hito.id"
                                    >
                                        {{ '—'.repeat(hitoOpt.nivel) }} {{ hitoOpt.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-sm text-muted-foreground">Selecciona un hito padre para crear una jerarquía</p>
                            <span v-if="form.errors.parent_id" class="text-sm text-red-500">{{ form.errors.parent_id }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="orden">Orden de visualización</Label>
                            <Input id="orden" v-model.number="form.orden" type="number" min="0" :disabled="form.processing" />
                            <span v-if="form.errors.orden" class="text-sm text-red-500">{{ form.errors.orden }}</span>
                            <p class="text-sm text-muted-foreground">
                                Define el orden en que aparece este hito en la lista
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-4">
                            <Link :href="`/admin/proyectos/${proyecto.id}/hitos`">
                                <Button type="button" variant="outline">Cancelar</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="h-4 w-4 mr-2" />
                                Guardar Cambios
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <CamposPersonalizadosForm
                v-if="camposPersonalizados && camposPersonalizados.length > 0"
                :campos="camposPersonalizados"
                :valores="form.campos_personalizados"
                :errors="form.errors"
                @update="form.campos_personalizados = $event"
            />

            <Card v-if="categorias && categorias.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Tag class="h-5 w-5" />
                        Etiquetas
                    </CardTitle>
                    <CardDescription>
                        Asigna etiquetas para categorizar y organizar este hito
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <EtiquetaSelector
                        v-model="form.etiquetas"
                        :categorias="categorias"
                        :max-etiquetas="10"
                        placeholder="Seleccionar etiquetas para el hito..."
                        description="Puedes asignar hasta 10 etiquetas"
                    />
                </CardContent>
            </Card>
        </div>

        <AddUsersModal
            v-model="showResponsableModal"
            title="Seleccionar Responsable del Hito"
            description="Selecciona el usuario que será responsable de este hito"
            :search-endpoint="route('admin.proyectos.search-users')"
            :excluded-ids="form.responsable_id ? [form.responsable_id] : []"
            :max-selection="1"
            submit-button-text="Seleccionar Responsable"
            search-placeholder="Buscar por nombre, email, documento o teléfono..."
            @submit="handleResponsableSelect"
        />
    </AdminLayout>
</template>
