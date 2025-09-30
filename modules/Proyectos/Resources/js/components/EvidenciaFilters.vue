<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent } from "@modules/Core/Resources/js/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@modules/Core/Resources/js/components/ui/select";
import { Input } from "@modules/Core/Resources/js/components/ui/input";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { Label } from "@modules/Core/Resources/js/components/ui/label";
import { X } from 'lucide-vue-next';

// Props
interface Props {
    contratos: any[];
    evidencias: any[];
    entregables?: any[];
}

const props = defineProps<Props>();

// Model para los filtros (v-model desde el parent)
const filtros = defineModel<{
    contrato_id: number | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    tipo: string | null;
    estado: string | null;
    usuario_id: number | null;
    entregable_id: number | null;
}>({ required: true });

// Opciones únicas extraídas de las evidencias
const tiposUnicos = computed(() => {
    const tipos = new Set(props.evidencias.map(e => e.tipo_evidencia).filter(Boolean));
    return Array.from(tipos).map(tipo => ({ value: tipo, label: tipo }));
});

const estadosUnicos = computed(() => {
    const estados = new Set(props.evidencias.map(e => e.estado).filter(Boolean));
    return Array.from(estados).map(estado => ({
        value: estado,
        label: estado.charAt(0).toUpperCase() + estado.slice(1)
    }));
});

const usuariosUnicos = computed(() => {
    const usuariosMap = new Map();
    props.evidencias.forEach(e => {
        if (e.usuario?.id) {
            usuariosMap.set(e.usuario.id, e.usuario);
        }
    });
    return Array.from(usuariosMap.values()).map(usuario => ({
        value: usuario.id,
        label: usuario.name
    }));
});

const entregablesUnicos = computed(() => {
    if (!props.entregables || props.entregables.length === 0) {
        // Extraer entregables de las evidencias si no se pasaron como prop
        const entregablesMap = new Map();
        props.evidencias.forEach(e => {
            if (e.entregables && e.entregables.length > 0) {
                e.entregables.forEach(ent => {
                    if (ent?.id) {
                        entregablesMap.set(ent.id, ent);
                    }
                });
            }
        });
        return Array.from(entregablesMap.values()).map(entregable => ({
            value: entregable.id,
            label: entregable.nombre
        }));
    }
    return props.entregables.map(entregable => ({
        value: entregable.id,
        label: entregable.nombre
    }));
});

// Función para limpiar todos los filtros
const limpiarFiltros = () => {
    filtros.value = {
        contrato_id: null,
        fecha_inicio: null,
        fecha_fin: null,
        tipo: null,
        estado: null,
        usuario_id: null,
        entregable_id: null
    };
};

// Verificar si hay filtros activos
const hayFiltrosActivos = computed(() => {
    return Object.values(filtros.value).some(v => v !== null && v !== '');
});
</script>

<template>
    <Card>
        <CardContent class="p-4">
            <div class="space-y-4">
                <!-- Fila de filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <!-- Filtro por Contrato -->
                    <div class="space-y-2">
                        <Label for="filter-contrato" class="text-xs">Contrato</Label>
                        <Select v-model="filtros.contrato_id" id="filter-contrato">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los contratos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los contratos</SelectItem>
                                <SelectItem
                                    v-for="contrato in contratos"
                                    :key="contrato.id"
                                    :value="contrato.id"
                                >
                                    {{ contrato.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtro por Fecha Inicio -->
                    <div class="space-y-2">
                        <Label for="filter-fecha-inicio" class="text-xs">Fecha Desde</Label>
                        <Input
                            v-model="filtros.fecha_inicio"
                            type="date"
                            id="filter-fecha-inicio"
                            class="text-sm"
                        />
                    </div>

                    <!-- Filtro por Fecha Fin -->
                    <div class="space-y-2">
                        <Label for="filter-fecha-fin" class="text-xs">Fecha Hasta</Label>
                        <Input
                            v-model="filtros.fecha_fin"
                            type="date"
                            id="filter-fecha-fin"
                            class="text-sm"
                        />
                    </div>

                    <!-- Filtro por Tipo -->
                    <div class="space-y-2">
                        <Label for="filter-tipo" class="text-xs">Tipo</Label>
                        <Select v-model="filtros.tipo" id="filter-tipo">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los tipos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los tipos</SelectItem>
                                <SelectItem
                                    v-for="tipo in tiposUnicos"
                                    :key="tipo.value"
                                    :value="tipo.value"
                                >
                                    {{ tipo.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtro por Estado -->
                    <div class="space-y-2">
                        <Label for="filter-estado" class="text-xs">Estado</Label>
                        <Select v-model="filtros.estado" id="filter-estado">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los estados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los estados</SelectItem>
                                <SelectItem
                                    v-for="estado in estadosUnicos"
                                    :key="estado.value"
                                    :value="estado.value"
                                >
                                    {{ estado.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Filtro por Usuario y Entregable -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="space-y-2 md:col-span-2">
                        <Label for="filter-usuario" class="text-xs">Usuario</Label>
                        <Select v-model="filtros.usuario_id" id="filter-usuario">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los usuarios" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los usuarios</SelectItem>
                                <SelectItem
                                    v-for="usuario in usuariosUnicos"
                                    :key="usuario.value"
                                    :value="usuario.value"
                                >
                                    {{ usuario.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div v-if="entregablesUnicos.length > 0" class="space-y-2 md:col-span-2">
                        <Label for="filter-entregable" class="text-xs">Entregable</Label>
                        <Select v-model="filtros.entregable_id" id="filter-entregable">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los entregables" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los entregables</SelectItem>
                                <SelectItem
                                    v-for="entregable in entregablesUnicos"
                                    :key="entregable.value"
                                    :value="entregable.value"
                                >
                                    {{ entregable.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Botón de limpiar -->
                    <div class="flex items-end" :class="entregablesUnicos.length > 0 ? 'md:col-span-1' : 'md:col-span-3'">
                        <Button
                            v-if="hayFiltrosActivos"
                            variant="outline"
                            size="sm"
                            @click="limpiarFiltros"
                            class="w-full md:w-auto"
                        >
                            <X class="h-4 w-4 mr-2" />
                            Limpiar Filtros
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>