<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from "@modules/Core/Resources/js/components/ui/card";
import CampoPersonalizadoInput from "./CampoPersonalizadoInput.vue";
import { ref, watch, onMounted } from 'vue';

interface CampoPersonalizado {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    opciones?: any[];
    es_requerido: boolean;
    placeholder?: string;
    descripcion?: string;
}

interface Props {
    campos: CampoPersonalizado[];
    valores?: Record<number, any>;
    errors?: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    update: [valores: Record<number, any>]
}>();

// Estado local de valores
const localValores = ref<Record<number, any>>({});

// Inicializar valores
onMounted(() => {
    if (props.valores) {
        localValores.value = { ...props.valores };
    } else {
        // Inicializar con valores vacíos
        props.campos.forEach(campo => {
            localValores.value[campo.id] = campo.tipo === 'checkbox' ? false : '';
        });
    }
});

// Actualizar cuando cambien los props
watch(() => props.valores, (newVal) => {
    if (newVal) {
        localValores.value = { ...newVal };
    }
});

// Manejar cambio de valor
const handleValueChange = (campoId: number, valor: any) => {
    localValores.value[campoId] = valor;
    emit('update', localValores.value);
};

// Obtener error para un campo
const getError = (campo: CampoPersonalizado): string | undefined => {
    if (!props.errors) return undefined;
    return props.errors[`campos_personalizados.${campo.id}`] || 
           props.errors[`campos_personalizados.${campo.slug}`];
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Campos Personalizados</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div v-for="campo in campos" :key="campo.id">
                <CampoPersonalizadoInput
                    :campo="campo"
                    :value="localValores[campo.id]"
                    :error="getError(campo)"
                    @update="handleValueChange(campo.id, $event)"
                />
            </div>
            <p v-if="campos.length === 0" class="text-gray-500 text-center py-4">
                No hay campos personalizados configurados
            </p>
        </CardContent>
    </Card>
</template>