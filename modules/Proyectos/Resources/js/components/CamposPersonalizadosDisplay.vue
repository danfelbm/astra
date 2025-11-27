<script setup lang="ts">
/**
 * Componente reutilizable para mostrar campos personalizados en vistas de detalle (Show)
 * Soporta dos formatos de datos:
 * 1. Array de valores con campo_personalizado embebido (formato de relación)
 * 2. Array de campos + objeto de valores separado
 */
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@modules/Core/Resources/js/components/ui/card";
import { Button } from "@modules/Core/Resources/js/components/ui/button";
import { FileText, Eye, Download } from 'lucide-vue-next';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: Array<{ value: string; label: string }>;
    es_requerido?: boolean;
    descripcion?: string;
}

interface ValorCampoPersonalizado {
    id?: number;
    campo_personalizado_id?: number;
    campo_personalizado?: CampoPersonalizado;
    valor: any;
}

interface Props {
    // Formato 1: Array de valores con campo embebido
    valores?: ValorCampoPersonalizado[];
    // Formato 2: Campos y valores separados
    campos?: CampoPersonalizado[];
    valoresCampos?: Record<number | string, any>;
    // Configuración
    titulo?: string;
    descripcion?: string;
    showCard?: boolean;
    columns?: 1 | 2;
}

const props = withDefaults(defineProps<Props>(), {
    titulo: 'Campos Personalizados',
    showCard: true,
    columns: 1
});

// Normalizar los datos a un formato común
const camposNormalizados = computed(() => {
    const items: Array<{
        id: number;
        nombre: string;
        tipo: string;
        opciones?: Array<{ value: string; label: string }>;
        descripcion?: string;
        valor: any;
    }> = [];

    // Formato 1: Valores con campo embebido
    if (props.valores && props.valores.length > 0) {
        props.valores.forEach(v => {
            if (v.campo_personalizado) {
                items.push({
                    id: v.campo_personalizado.id,
                    nombre: v.campo_personalizado.nombre,
                    tipo: v.campo_personalizado.tipo,
                    opciones: v.campo_personalizado.opciones,
                    descripcion: v.campo_personalizado.descripcion,
                    valor: v.valor
                });
            }
        });
    }
    // Formato 2: Campos y valores separados
    else if (props.campos && props.campos.length > 0 && props.valoresCampos) {
        props.campos.forEach(campo => {
            const valor = props.valoresCampos?.[campo.id];
            if (valor !== undefined && valor !== null && valor !== '') {
                items.push({
                    id: campo.id,
                    nombre: campo.nombre,
                    tipo: campo.tipo,
                    opciones: campo.opciones,
                    descripcion: campo.descripcion,
                    valor: valor
                });
            }
        });
    }

    return items;
});

// Verificar si hay campos para mostrar
const tieneCampos = computed(() => camposNormalizados.value.length > 0);

// Formatear valor según el tipo de campo
const formatearValor = (campo: typeof camposNormalizados.value[0]): string => {
    if (campo.valor === null || campo.valor === undefined || campo.valor === '') {
        return '-';
    }

    switch (campo.tipo) {
        case 'date':
            try {
                return format(parseISO(campo.valor), 'dd MMM yyyy', { locale: es });
            } catch {
                return campo.valor;
            }
        case 'checkbox':
            return campo.valor === '1' || campo.valor === true || campo.valor === 1 ? 'Sí' : 'No';
        case 'select':
        case 'radio':
            if (campo.opciones) {
                const opcion = campo.opciones.find(o => o.value === campo.valor);
                return opcion?.label || campo.valor;
            }
            return campo.valor;
        case 'number':
            // Formatear números con separador de miles
            const num = parseFloat(campo.valor);
            if (!isNaN(num)) {
                return num.toLocaleString('es-CO');
            }
            return campo.valor;
        default:
            return campo.valor;
    }
};

// Ver archivo
const verArchivo = (valor: string) => {
    window.open(`/storage/campos-personalizados/${valor}`, '_blank');
};

// Descargar archivo
const descargarArchivo = (valor: string) => {
    const link = document.createElement('a');
    link.href = `/storage/campos-personalizados/${valor}`;
    link.download = valor;
    link.click();
};
</script>

<template>
    <template v-if="tieneCampos">
        <!-- Con Card wrapper -->
        <Card v-if="showCard">
            <CardHeader>
                <CardTitle>{{ titulo }}</CardTitle>
                <CardDescription v-if="descripcion">{{ descripcion }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div :class="columns === 2 ? 'grid gap-4 md:grid-cols-2' : 'space-y-4'">
                    <div
                        v-for="campo in camposNormalizados"
                        :key="campo.id"
                        class="py-2 border-b last:border-b-0"
                    >
                        <h4 class="text-sm font-medium text-muted-foreground mb-1">
                            {{ campo.nombre }}
                        </h4>

                        <!-- Campo de tipo archivo -->
                        <div v-if="campo.tipo === 'file' && campo.valor" class="flex items-center gap-2 flex-wrap">
                            <FileText class="h-4 w-4 text-gray-400 flex-shrink-0" />
                            <span class="text-sm truncate max-w-[200px]">{{ campo.valor }}</span>
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="verArchivo(campo.valor)"
                                >
                                    <Eye class="h-4 w-4 mr-1" />
                                    Ver
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="descargarArchivo(campo.valor)"
                                >
                                    <Download class="h-4 w-4 mr-1" />
                                    Descargar
                                </Button>
                            </div>
                        </div>

                        <!-- Otros tipos de campo -->
                        <p v-else class="text-sm">
                            {{ formatearValor(campo) }}
                        </p>

                        <!-- Descripción del campo (opcional) -->
                        <p v-if="campo.descripcion" class="text-xs text-muted-foreground mt-1">
                            {{ campo.descripcion }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Sin Card wrapper (para embeber en otros lugares) -->
        <div v-else :class="columns === 2 ? 'grid gap-4 md:grid-cols-2' : 'space-y-4'">
            <div
                v-for="campo in camposNormalizados"
                :key="campo.id"
                class="py-2"
            >
                <h4 class="text-sm font-medium text-muted-foreground mb-1">
                    {{ campo.nombre }}
                </h4>

                <!-- Campo de tipo archivo -->
                <div v-if="campo.tipo === 'file' && campo.valor" class="flex items-center gap-2 flex-wrap">
                    <FileText class="h-4 w-4 text-gray-400 flex-shrink-0" />
                    <span class="text-sm truncate max-w-[200px]">{{ campo.valor }}</span>
                    <div class="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="verArchivo(campo.valor)"
                        >
                            <Eye class="h-4 w-4 mr-1" />
                            Ver
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="descargarArchivo(campo.valor)"
                        >
                            <Download class="h-4 w-4 mr-1" />
                            Descargar
                        </Button>
                    </div>
                </div>

                <!-- Otros tipos de campo -->
                <p v-else class="text-sm">
                    {{ formatearValor(campo) }}
                </p>
            </div>
        </div>
    </template>
</template>
