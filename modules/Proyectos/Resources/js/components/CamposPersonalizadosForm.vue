<script setup lang="ts">
/**
 * Componente reutilizable para formularios de campos personalizados
 * Soporta:
 * - Renderizado simple (todos los campos juntos)
 * - Agrupación por categoría (para mostrar campos en diferentes secciones)
 * - v-model para sincronizar valores
 */
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import CampoPersonalizadoInput from "./CampoPersonalizadoInput.vue";
import { ref, watch, onMounted, computed } from 'vue';

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: any[];
    es_requerido: boolean;
    placeholder?: string;
    descripcion?: string;
    categoria?: string; // Para agrupación opcional
}

interface Props {
    campos: CampoPersonalizado[];
    modelValue?: Record<number | string, any>;
    // Compatibilidad con API anterior
    valores?: Record<number | string, any>;
    errors?: Record<string, string>;
    // Configuración de visualización
    showCard?: boolean;
    titulo?: string;
    disabled?: boolean;
    // Agrupación: si se provee, solo muestra campos de ese grupo
    grupo?: string;
    // Función personalizada para determinar grupo (por defecto usa slug)
    grupoFn?: (campo: CampoPersonalizado) => string;
}

const props = withDefaults(defineProps<Props>(), {
    showCard: true,
    titulo: 'Campos Personalizados',
    disabled: false
});

const emit = defineEmits<{
    'update:modelValue': [valores: Record<number | string, any>];
    // Compatibilidad con API anterior
    'update': [valores: Record<number | string, any>];
}>();

// Estado local de valores
const localValores = ref<Record<number | string, any>>({});

// Valores iniciales (soporta ambas APIs)
const valoresIniciales = computed(() => props.modelValue || props.valores || {});

// Inicializar valores
onMounted(() => {
    initializeValues();
});

// Función para inicializar valores
const initializeValues = () => {
    const inicial = { ...valoresIniciales.value };
    // Asegurar que todos los campos tengan un valor inicial
    props.campos.forEach(campo => {
        if (!(campo.id in inicial)) {
            inicial[campo.id] = campo.tipo === 'checkbox' ? false : '';
        }
    });
    localValores.value = inicial;
};

// Actualizar cuando cambien los props
watch(() => valoresIniciales.value, (newVal) => {
    if (newVal && Object.keys(newVal).length > 0) {
        const merged = { ...localValores.value };
        Object.keys(newVal).forEach(key => {
            merged[key] = newVal[key];
        });
        localValores.value = merged;
    }
}, { deep: true });

// Re-inicializar cuando cambien los campos
watch(() => props.campos, () => {
    initializeValues();
}, { deep: true });

// Función por defecto para determinar el grupo de un campo
const defaultGrupoFn = (campo: CampoPersonalizado): string => {
    const slug = campo.slug.toLowerCase();
    if (slug.includes('financ') || slug.includes('monto') || slug.includes('precio') || slug.includes('presupuesto')) {
        return 'financiero';
    }
    if (slug.includes('info') || slug.includes('descrip') || slug.includes('nota')) {
        return 'informacion';
    }
    return 'otros';
};

// Campos filtrados según el grupo (si se especifica)
const camposFiltrados = computed(() => {
    if (!props.grupo) {
        return props.campos;
    }

    const grupoFn = props.grupoFn || defaultGrupoFn;
    return props.campos.filter(campo => grupoFn(campo) === props.grupo);
});

// Manejar cambio de valor
const handleValueChange = (campoId: number, valor: any) => {
    localValores.value[campoId] = valor;
    // Emitir en ambos formatos para compatibilidad
    emit('update:modelValue', { ...localValores.value });
    emit('update', { ...localValores.value });
};

// Obtener error para un campo
const getError = (campo: CampoPersonalizado): string | undefined => {
    if (!props.errors) return undefined;
    return props.errors[`campos_personalizados.${campo.id}`] ||
           props.errors[`campos_personalizados.${campo.slug}`] ||
           props.errors[campo.slug];
};

// Exponer método para obtener valores actuales (útil para validación externa)
defineExpose({
    getValores: () => ({ ...localValores.value }),
    setValor: (campoId: number, valor: any) => {
        localValores.value[campoId] = valor;
        emit('update:modelValue', { ...localValores.value });
        emit('update', { ...localValores.value });
    }
});
</script>

<template>
    <template v-if="camposFiltrados.length > 0">
        <!-- Con Card wrapper -->
        <Card v-if="showCard">
            <CardHeader>
                <CardTitle>{{ titulo }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-for="campo in camposFiltrados" :key="campo.id">
                    <CampoPersonalizadoInput
                        :campo="campo"
                        v-model="localValores[campo.id]"
                        :error="getError(campo)"
                        :disabled="disabled"
                        @update:model-value="handleValueChange(campo.id, $event)"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Sin Card wrapper (para embeber en otros lugares) -->
        <div v-else class="space-y-4">
            <div v-for="campo in camposFiltrados" :key="campo.id">
                <CampoPersonalizadoInput
                    :campo="campo"
                    v-model="localValores[campo.id]"
                    :error="getError(campo)"
                    :disabled="disabled"
                    @update:model-value="handleValueChange(campo.id, $event)"
                />
            </div>
        </div>
    </template>

    <!-- Mensaje cuando no hay campos -->
    <Card v-else-if="showCard && !grupo">
        <CardHeader>
            <CardTitle>{{ titulo }}</CardTitle>
        </CardHeader>
        <CardContent>
            <p class="text-gray-500 text-center py-4">
                No hay campos personalizados configurados
            </p>
        </CardContent>
    </Card>
</template>
