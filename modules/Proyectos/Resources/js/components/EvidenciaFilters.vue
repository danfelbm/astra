<script setup lang="ts">
import { computed, watch } from 'vue';
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
    hitos?: any[];
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
    hito_id: number | null;
    entregable_id: number | null;
}>({ required: true });

// Función para filtrar evidencias excluyendo un filtro específico
// Esto permite calcular opciones disponibles sin crear búsquedas imposibles
const filtrarEvidencias = (excluir: string | null = null) => {
    let result = props.evidencias;

    if (excluir !== 'contrato' && filtros.value.contrato_id) {
        result = result.filter(e => e.contrato_id === filtros.value.contrato_id);
    }

    if (excluir !== 'tipo' && filtros.value.tipo) {
        result = result.filter(e => e.tipo_evidencia === filtros.value.tipo);
    }

    if (excluir !== 'estado' && filtros.value.estado) {
        result = result.filter(e => e.estado === filtros.value.estado);
    }

    if (excluir !== 'usuario' && filtros.value.usuario_id) {
        result = result.filter(e => e.usuario?.id === filtros.value.usuario_id);
    }

    if (excluir !== 'hito' && filtros.value.hito_id) {
        result = result.filter(e => {
            if (!e.entregables || e.entregables.length === 0) return false;
            return e.entregables.some((ent: any) => ent.hito_id === filtros.value.hito_id);
        });
    }

    if (excluir !== 'entregable' && filtros.value.entregable_id) {
        result = result.filter(e => {
            if (!e.entregables || e.entregables.length === 0) return false;
            return e.entregables.some((ent: any) => ent.id === filtros.value.entregable_id);
        });
    }

    // Filtro por fechas (siempre aplicar, no excluir)
    if (filtros.value.fecha_inicio || filtros.value.fecha_fin) {
        result = result.filter(e => {
            const fecha = new Date(e.created_at);
            if (filtros.value.fecha_inicio) {
                const fechaInicio = new Date(filtros.value.fecha_inicio);
                if (fecha < fechaInicio) return false;
            }
            if (filtros.value.fecha_fin) {
                const fechaFin = new Date(filtros.value.fecha_fin);
                fechaFin.setHours(23, 59, 59, 999);
                if (fecha > fechaFin) return false;
            }
            return true;
        });
    }

    return result;
};

// Contratos disponibles basados en evidencias filtradas (excluyendo filtro de contrato)
const contratosDisponibles = computed(() => {
    const evidenciasFiltradas = filtrarEvidencias('contrato');
    const contratoIds = new Set(evidenciasFiltradas.map(e => e.contrato_id));
    return props.contratos.filter(c => contratoIds.has(c.id));
});

// Tipos disponibles basados en evidencias filtradas (excluyendo filtro de tipo)
const tiposDisponibles = computed(() => {
    const evidenciasFiltradas = filtrarEvidencias('tipo');
    const tipos = new Set(evidenciasFiltradas.map(e => e.tipo_evidencia).filter(Boolean));
    return Array.from(tipos).map(tipo => ({ value: tipo, label: tipo }));
});

// Estados disponibles basados en evidencias filtradas (excluyendo filtro de estado)
const estadosDisponibles = computed(() => {
    const evidenciasFiltradas = filtrarEvidencias('estado');
    const estados = new Set(evidenciasFiltradas.map(e => e.estado).filter(Boolean));
    return Array.from(estados).map(estado => ({
        value: estado,
        label: estado.charAt(0).toUpperCase() + estado.slice(1)
    }));
});

// Usuarios disponibles basados en evidencias filtradas (excluyendo filtro de usuario)
const usuariosDisponibles = computed(() => {
    const evidenciasFiltradas = filtrarEvidencias('usuario');
    const usuariosMap = new Map();
    evidenciasFiltradas.forEach(e => {
        if (e.usuario?.id) {
            usuariosMap.set(e.usuario.id, e.usuario);
        }
    });
    return Array.from(usuariosMap.values()).map(usuario => ({
        value: usuario.id,
        label: usuario.name
    }));
});

// Hitos disponibles basados en evidencias filtradas (excluyendo filtro de hito)
const hitosDisponibles = computed(() => {
    const evidenciasFiltradas = filtrarEvidencias('hito');
    const hitosConEvidencias = new Set<number>();

    // Obtener IDs de hitos que tienen evidencias en el resultado filtrado
    evidenciasFiltradas.forEach(e => {
        if (e.entregables && e.entregables.length > 0) {
            e.entregables.forEach((ent: any) => {
                if (ent?.hito_id) {
                    hitosConEvidencias.add(ent.hito_id);
                }
            });
        }
    });

    // Si hay hitos como prop, filtrar solo los que tienen evidencias
    if (props.hitos && props.hitos.length > 0) {
        return props.hitos
            .filter(hito => hitosConEvidencias.has(hito.id))
            .map(hito => ({
                value: hito.id,
                label: hito.nombre
            }));
    }

    // Si no, extraer hitos desde las evidencias filtradas
    const hitosMap = new Map();
    evidenciasFiltradas.forEach(e => {
        if (e.entregables && e.entregables.length > 0) {
            e.entregables.forEach((ent: any) => {
                if (ent?.hito?.id) {
                    hitosMap.set(ent.hito.id, ent.hito);
                }
            });
        }
    });
    return Array.from(hitosMap.values()).map(hito => ({
        value: hito.id,
        label: hito.nombre
    }));
});

// Entregables disponibles (cascada: filtrados por hito Y por otros filtros)
const entregablesDisponibles = computed(() => {
    // Primero filtrar por otros filtros (excluyendo entregable)
    const evidenciasFiltradas = filtrarEvidencias('entregable');

    // Obtener IDs de entregables que tienen evidencias
    const entregablesConEvidencias = new Set<number>();
    evidenciasFiltradas.forEach(e => {
        if (e.entregables && e.entregables.length > 0) {
            e.entregables.forEach((ent: any) => {
                if (ent?.id) {
                    entregablesConEvidencias.add(ent.id);
                }
            });
        }
    });

    let entregablesList: any[] = [];

    if (props.entregables && props.entregables.length > 0) {
        entregablesList = props.entregables;
    } else {
        // Extraer entregables de las evidencias
        const entregablesMap = new Map();
        props.evidencias.forEach(e => {
            if (e.entregables && e.entregables.length > 0) {
                e.entregables.forEach((ent: any) => {
                    if (ent?.id) {
                        entregablesMap.set(ent.id, ent);
                    }
                });
            }
        });
        entregablesList = Array.from(entregablesMap.values());
    }

    // Filtrar por hito seleccionado (cascada obligatoria)
    if (filtros.value.hito_id) {
        entregablesList = entregablesList.filter(ent => ent.hito_id === filtros.value.hito_id);
    }

    // Filtrar solo entregables que tienen evidencias según los otros filtros
    entregablesList = entregablesList.filter(ent => entregablesConEvidencias.has(ent.id));

    return entregablesList.map(entregable => ({
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
        hito_id: null,
        entregable_id: null
    };
};

// Verificar si hay filtros activos
const hayFiltrosActivos = computed(() => {
    return Object.values(filtros.value).some(v => v !== null && v !== '');
});

// Limpiar entregable_id cuando cambie el hito
watch(() => filtros.value.hito_id, () => {
    filtros.value.entregable_id = null;
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
                                    v-for="contrato in contratosDisponibles"
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
                                    v-for="tipo in tiposDisponibles"
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
                                    v-for="estado in estadosDisponibles"
                                    :key="estado.value"
                                    :value="estado.value"
                                >
                                    {{ estado.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Filtro por Usuario, Hito y Entregable -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Filtro por Usuario -->
                    <div class="space-y-2">
                        <Label for="filter-usuario" class="text-xs">Usuario</Label>
                        <Select v-model="filtros.usuario_id" id="filter-usuario">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los usuarios" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los usuarios</SelectItem>
                                <SelectItem
                                    v-for="usuario in usuariosDisponibles"
                                    :key="usuario.value"
                                    :value="usuario.value"
                                >
                                    {{ usuario.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtro por Hito -->
                    <div v-if="hitosDisponibles.length > 0 || props.hitos?.length" class="space-y-2">
                        <Label for="filter-hito" class="text-xs">Hito</Label>
                        <Select v-model="filtros.hito_id" id="filter-hito">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los hitos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Todos los hitos</SelectItem>
                                <SelectItem
                                    v-for="hito in hitosDisponibles"
                                    :key="hito.value"
                                    :value="hito.value"
                                >
                                    {{ hito.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filtro por Entregable (cascada: requiere hito seleccionado) -->
                    <div v-if="hitosDisponibles.length > 0 || props.hitos?.length" class="space-y-2">
                        <Label for="filter-entregable" class="text-xs">Entregable</Label>
                        <Select
                            v-model="filtros.entregable_id"
                            id="filter-entregable"
                            :disabled="!filtros.hito_id"
                        >
                            <SelectTrigger :class="{ 'opacity-60': !filtros.hito_id }">
                                <SelectValue :placeholder="filtros.hito_id ? 'Todos los entregables' : 'Elige primero un hito'" />
                            </SelectTrigger>
                            <SelectContent v-if="filtros.hito_id">
                                <SelectItem :value="null">Todos los entregables</SelectItem>
                                <SelectItem
                                    v-for="entregable in entregablesDisponibles"
                                    :key="entregable.value"
                                    :value="entregable.value"
                                >
                                    {{ entregable.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Botón de limpiar -->
                    <div class="flex items-end lg:col-span-2">
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