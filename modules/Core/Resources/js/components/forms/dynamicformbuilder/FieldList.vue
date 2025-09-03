<script setup lang="ts">
import { Badge } from "../../ui/badge";
import { Button } from "../../ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../../ui/card";
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import FieldPreview from './FieldPreview.vue';
import type { FormField } from "@modules/Core/Resources/js/types/forms";

interface Props {
    fields: FormField[];
    disabled?: boolean;
    canAddFields?: boolean;
    canEditFields?: boolean;
    canDeleteFields?: boolean;
    title?: string;
    description?: string;
}

interface Emits {
    (e: 'add-field'): void;
    (e: 'edit-field', index: number): void;
    (e: 'delete-field', index: number): void;
    (e: 'duplicate-field', field: FormField): void;
    (e: 'preview-field', field: FormField): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    canAddFields: true,
    canEditFields: true,
    canDeleteFields: true,
    title: 'Campos del Formulario',
    description: 'Gestiona los campos que aparecerán en el formulario',
});

const emit = defineEmits<Emits>();

// Computed para estadísticas
const fieldStats = computed(() => {
    const total = props.fields.length;
    const required = props.fields.filter(f => f.required).length;
    const conditional = props.fields.filter(f => f.conditionalConfig?.enabled).length;
    
    return {
        total,
        required,
        conditional,
        optional: total - required
    };
});

// Manejo de duplicación de campos
const handleDuplicate = (field: FormField) => {
    emit('duplicate-field', field);
};

// Manejo de eliminación con confirmación (opcional - se puede mover a componente padre)
const handleDelete = (index: number) => {
    // Por ahora emite directamente, pero podría incluir confirmación
    emit('delete-field', index);
};

// Drag & drop deshabilitado temporalmente por problemas de estabilidad
</script>

<template>
    <div class="space-y-4">
        <!-- Header con estadísticas -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="text-lg">{{ title }}</CardTitle>
                        <p class="text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    
                    <Button
                        type="button"
                        v-if="canAddFields"
                        @click="emit('add-field')"
                        :disabled="disabled"
                        class="gap-2"
                    >
                        <Plus class="h-4 w-4" />
                        Añadir Campo
                    </Button>
                </div>
                
                <!-- Estadísticas -->
                <div class="flex items-center gap-4 mt-3">
                    <Badge variant="outline">
                        Total: {{ fieldStats.total }}
                    </Badge>
                    <Badge variant="default" v-if="fieldStats.required > 0">
                        Requeridos: {{ fieldStats.required }}
                    </Badge>
                    <Badge variant="secondary" v-if="fieldStats.conditional > 0">
                        Condicionales: {{ fieldStats.conditional }}
                    </Badge>
                </div>
            </CardHeader>
        </Card>
        
        <!-- Lista de campos -->
        <div class="space-y-3">
            <!-- Estado vacío -->
            <div v-if="fields.length === 0" class="text-center py-12">
                <div class="mx-auto w-16 h-16 bg-muted/50 rounded-full flex items-center justify-center mb-4">
                    <Plus class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-medium text-muted-foreground mb-2">
                    No hay campos agregados
                </h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Comienza agregando el primer campo a tu formulario
                </p>
                <Button
                    type="button"
                    v-if="canAddFields"
                    @click="emit('add-field')"
                    :disabled="disabled"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Añadir Primer Campo
                </Button>
            </div>
            
            <!-- Campos existentes -->
            <div
                v-for="(field, index) in fields"
                :key="field.id"
                class="relative"
            >
                <!-- Componente de vista previa del campo -->
                <FieldPreview
                    :field="field"
                    :index="index"
                    :disabled="disabled"
                    :can-edit="canEditFields"
                    :can-delete="canDeleteFields"
                    :can-duplicate="canAddFields"
                    @edit="emit('edit-field', index)"
                    @delete="handleDelete"
                    @duplicate="handleDuplicate"
                    @preview="emit('preview-field', $event)"
                />
            </div>
        </div>
        
        <!-- Footer con acciones adicionales -->
        <div v-if="fields.length > 0" class="flex items-center justify-between pt-4 border-t">
            <div class="text-sm text-muted-foreground">
                {{ fieldStats.total }} campo{{ fieldStats.total === 1 ? '' : 's' }} en total
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Botón adicional para añadir campo al final -->
                <Button
                    type="button"
                    v-if="canAddFields"
                    variant="outline"
                    size="sm"
                    @click="emit('add-field')"
                    :disabled="disabled"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Añadir Campo
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos para la lista de campos */
</style>