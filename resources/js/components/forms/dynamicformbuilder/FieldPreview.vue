<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Eye, Edit, Trash2, Copy, GitBranch } from 'lucide-vue-next';
import { computed } from 'vue';
import type { FormField } from '@/types/forms';
import { FIELD_TYPES } from '@/types/forms';

interface Props {
    field: FormField;
    index: number;
    disabled?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
    canDuplicate?: boolean;
}

interface Emits {
    (e: 'edit', index: number): void;
    (e: 'delete', index: number): void;
    (e: 'duplicate', field: FormField): void;
    (e: 'preview', field: FormField): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    canEdit: true,
    canDelete: true,
    canDuplicate: true,
});

const emit = defineEmits<Emits>();

// Computed para obtener info del tipo de campo
const fieldTypeInfo = computed(() => {
    return FIELD_TYPES.find(type => type.value === props.field.type) || FIELD_TYPES[0];
});

// Computed para generar resumen del campo
const fieldSummary = computed(() => {
    const field = props.field;
    const parts: string[] = [];
    
    // Información básica
    if (field.required) parts.push('Requerido');
    if (field.editable) parts.push('Editable');
    
    // Información específica por tipo
    switch (field.type) {
        case 'select':
        case 'radio':
        case 'checkbox':
            if (field.options?.length) {
                parts.push(`${field.options.length} opciones`);
            }
            break;
            
        case 'file':
            if (field.fileConfig) {
                const config = field.fileConfig;
                if (config.multiple) {
                    parts.push(`Hasta ${config.maxFiles} archivos`);
                } else {
                    parts.push('Un archivo');
                }
                parts.push(`Máx ${config.maxFileSize}MB`);
            }
            break;
            
        case 'number':
            if (field.numberConfig) {
                const config = field.numberConfig;
                if (config.min !== undefined || config.max !== undefined) {
                    parts.push(`Rango: ${config.min ?? '...'} - ${config.max ?? '...'}`);
                }
                if (config.decimals > 0) {
                    parts.push(`${config.decimals} decimales`);
                }
            }
            break;
            
        case 'datepicker':
            if (field.datepickerConfig) {
                const config = field.datepickerConfig;
                parts.push(`Formato: ${config.format}`);
                if (!config.allowPastDates && config.allowFutureDates) {
                    parts.push('Solo futuras');
                } else if (config.allowPastDates && !config.allowFutureDates) {
                    parts.push('Solo pasadas');
                }
            }
            break;
            
        case 'convocatoria':
            if (field.convocatoriaConfig) {
                const config = field.convocatoriaConfig;
                if (config.multiple) parts.push('Voto múltiple');
                if (config.mostrarVotoBlanco) parts.push('Con voto blanco');
            }
            break;
            
        case 'perfil_candidatura':
            if (field.perfilCandidaturaConfig) {
                const config = field.perfilCandidaturaConfig;
                if (config.multiple) parts.push('Selección múltiple');
                if (config.mostrarVotoBlanco) parts.push('Con voto blanco');
            }
            break;
            
        case 'repeater':
            if (field.repeaterConfig) {
                const config = field.repeaterConfig;
                parts.push(`${config.minItems}-${config.maxItems} elementos`);
                if (config.fields?.length) {
                    parts.push(`${config.fields.length} subcampos`);
                }
            }
            break;
            
        case 'disclaimer':
            if (field.disclaimerConfig?.disclaimerText) {
                const textLength = field.disclaimerConfig.disclaimerText.length;
                parts.push(`${textLength} caracteres`);
            }
            break;
    }
    
    // Configuración condicional
    if (field.conditionalConfig?.enabled && field.conditionalConfig.conditions?.length > 0) {
        parts.push(`${field.conditionalConfig.conditions.length} condiciones`);
    }
    
    return parts.join(' • ');
});

// Computed para el color del badge según el tipo
const badgeVariant = computed(() => {
    const variants: Record<string, any> = {
        'text': 'default',
        'textarea': 'default',
        'select': 'secondary',
        'radio': 'secondary',
        'checkbox': 'secondary',
        'file': 'outline',
        'datepicker': 'outline',
        'number': 'outline',
        'disclaimer': 'destructive',
        'repeater': 'default',
        'convocatoria': 'default',
        'perfil_candidatura': 'default',
    };
    return variants[props.field.type] || 'default';
});
</script>

<template>
    <Card class="group hover:shadow-md transition-shadow duration-200" :id="`field-preview-${index}`">
        <CardContent class="p-4">
            <div class="flex items-start justify-between gap-3">
                <!-- Contenido principal -->
                <div class="flex-1 min-w-0">
                    <!-- Header del campo -->
                    <div class="flex items-start gap-2 mb-2">
                        <component :is="fieldTypeInfo.icon" class="h-4 w-4 mt-1 text-muted-foreground flex-shrink-0" />
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-sm font-medium truncate">
                                    {{ field.title || 'Campo sin título' }}
                                </h4>
                                <Badge :variant="badgeVariant" class="text-xs">
                                    {{ fieldTypeInfo.label }}
                                </Badge>
                            </div>
                            
                            <!-- Descripción si existe -->
                            <p v-if="field.description" class="text-xs text-muted-foreground mb-2 line-clamp-2">
                                {{ field.description }}
                            </p>
                            
                            <!-- Resumen de configuración -->
                            <p v-if="fieldSummary" class="text-xs text-muted-foreground">
                                {{ fieldSummary }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Indicadores adicionales -->
                    <div class="flex items-center gap-1 mt-2">
                        <Badge v-if="field.conditionalConfig?.enabled" variant="outline" class="text-xs">
                            <GitBranch class="mr-1 h-3 w-3" />
                            Condicional
                        </Badge>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="flex items-center gap-1">
                    <!-- Previsualizar -->
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="emit('preview', field)"
                        :disabled="disabled"
                        class="h-8 w-8 p-0"
                        title="Previsualizar campo"
                    >
                        <Eye class="h-3 w-3" />
                    </Button>
                    
                    <!-- Editar -->
                    <Button
                        type="button"
                        v-if="canEdit"
                        variant="ghost"
                        size="sm"
                        @click="emit('edit', index)"
                        :disabled="disabled"
                        class="h-8 w-8 p-0"
                        title="Editar campo"
                    >
                        <Edit class="h-3 w-3" />
                    </Button>
                    
                    <!-- Duplicar -->
                    <Button
                        type="button"
                        v-if="canDuplicate"
                        variant="ghost"
                        size="sm"
                        @click="emit('duplicate', field)"
                        :disabled="disabled"
                        class="h-8 w-8 p-0"
                        title="Duplicar campo"
                    >
                        <Copy class="h-3 w-3" />
                    </Button>
                    
                    <!-- Eliminar -->
                    <Button
                        type="button"
                        v-if="canDelete"
                        variant="ghost"
                        size="sm"
                        @click="emit('delete', index)"
                        :disabled="disabled"
                        class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                        title="Eliminar campo"
                    >
                        <Trash2 class="h-3 w-3" />
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
/* Estilos para truncar texto en múltiples líneas */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>