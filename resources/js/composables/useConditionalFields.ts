import { computed, ref, watch, type Ref } from 'vue';
import type { FormField, ConditionalRule, ConditionalOperator } from '@/types/forms';

/**
 * Composable para manejar la lógica de campos condicionales
 */
export function useConditionalFields(
    fields: Ref<FormField[]>,
    formData: Ref<Record<string, any>>
) {
    // Cache de evaluaciones para optimizar rendimiento
    const evaluationCache = ref<Map<string, boolean>>(new Map());
    
    // Limpiar cache cuando cambian los datos del formulario
    watch(formData, () => {
        evaluationCache.value.clear();
    }, { deep: true });

    /**
     * Evalúa una condición individual
     */
    const evaluateCondition = (
        sourceValue: any,
        operator: ConditionalOperator,
        targetValue?: string | string[]
    ): boolean => {
        // Convertir valores a string para comparación consistente
        const normalizeValue = (val: any): string => {
            if (val === null || val === undefined) return '';
            if (typeof val === 'boolean') return val ? 'true' : 'false';
            return String(val);
        };

        switch (operator) {
            case 'equals':
                // Para checkbox arrays, verificar si contiene exactamente los mismos valores
                if (Array.isArray(sourceValue) && Array.isArray(targetValue)) {
                    return sourceValue.length === targetValue.length &&
                           sourceValue.every(v => targetValue.includes(v));
                }
                return normalizeValue(sourceValue) === normalizeValue(targetValue);
            
            case 'not_equals':
                if (Array.isArray(sourceValue) && Array.isArray(targetValue)) {
                    return sourceValue.length !== targetValue.length ||
                           !sourceValue.every(v => targetValue.includes(v));
                }
                return normalizeValue(sourceValue) !== normalizeValue(targetValue);
            
            case 'contains':
                // Para checkbox arrays, verificar si contiene el valor
                if (Array.isArray(sourceValue)) {
                    if (Array.isArray(targetValue)) {
                        return targetValue.some(v => sourceValue.includes(v));
                    }
                    return sourceValue.includes(targetValue);
                }
                // Para strings, verificar si contiene el texto
                return normalizeValue(sourceValue).includes(normalizeValue(targetValue));
            
            case 'not_contains':
                if (Array.isArray(sourceValue)) {
                    if (Array.isArray(targetValue)) {
                        return !targetValue.some(v => sourceValue.includes(v));
                    }
                    return !sourceValue.includes(targetValue);
                }
                return !normalizeValue(sourceValue).includes(normalizeValue(targetValue));
            
            case 'is_empty':
                if (Array.isArray(sourceValue)) {
                    return sourceValue.length === 0;
                }
                return !sourceValue || normalizeValue(sourceValue) === '';
            
            case 'is_not_empty':
                if (Array.isArray(sourceValue)) {
                    return sourceValue.length > 0;
                }
                return !!sourceValue && normalizeValue(sourceValue) !== '';
            
            default:
                console.warn(`Operador condicional no soportado: ${operator}`);
                return true;
        }
    };

    /**
     * Evalúa si un campo debe mostrarse basándose en sus condiciones
     */
    const shouldShowField = (field: FormField): boolean => {
        // Si no tiene configuración condicional o no está habilitada, siempre mostrar
        if (!field.conditionalConfig?.enabled) {
            return true;
        }

        // Verificar si ya está en cache
        const cacheKey = field.id;
        if (evaluationCache.value.has(cacheKey)) {
            return evaluationCache.value.get(cacheKey)!;
        }

        const { showWhen, conditions } = field.conditionalConfig;
        
        // Si no hay condiciones, mostrar el campo
        if (!conditions || conditions.length === 0) {
            return true;
        }

        // Evaluar cada condición
        const results = conditions.map((condition: ConditionalRule) => {
            const sourceValue = formData.value[condition.fieldId];
            return evaluateCondition(sourceValue, condition.operator, condition.value);
        });

        // Aplicar lógica AND/OR
        const result = showWhen === 'all' 
            ? results.every(r => r)  // Todas las condiciones deben cumplirse
            : results.some(r => r);   // Al menos una condición debe cumplirse

        // Guardar en cache
        evaluationCache.value.set(cacheKey, result);
        
        return result;
    };

    /**
     * Obtiene solo los campos que deben ser visibles según las condiciones
     */
    const visibleFields = computed(() => {
        return fields.value.filter(field => shouldShowField(field));
    });

    /**
     * Obtiene los campos que dependen de un campo específico
     */
    const getDependentFields = (fieldId: string): FormField[] => {
        return fields.value.filter(field => {
            if (!field.conditionalConfig?.enabled) return false;
            
            return field.conditionalConfig.conditions.some(
                condition => condition.fieldId === fieldId
            );
        });
    };

    /**
     * Valida que no haya referencias circulares en las condiciones
     */
    const validateConditionalRules = (field: FormField): string[] => {
        const errors: string[] = [];
        
        if (!field.conditionalConfig?.enabled) return errors;

        // Verificar auto-referencia
        const hasSelfReference = field.conditionalConfig.conditions.some(
            condition => condition.fieldId === field.id
        );
        
        if (hasSelfReference) {
            errors.push(`El campo "${field.title}" no puede depender de sí mismo`);
        }

        // Verificar referencias a campos inexistentes
        field.conditionalConfig.conditions.forEach(condition => {
            const sourceField = fields.value.find(f => f.id === condition.fieldId);
            if (!sourceField) {
                errors.push(`El campo "${field.title}" hace referencia a un campo inexistente: ${condition.fieldId}`);
            } else {
                // Verificar que el campo fuente sea de tipo válido para condiciones
                const validTypes = ['select', 'radio', 'checkbox'];
                if (!validTypes.includes(sourceField.type)) {
                    errors.push(`El campo "${field.title}" depende de "${sourceField.title}" que no es de tipo select, radio o checkbox`);
                }
            }
        });

        // Detectar referencias circulares más complejas
        const visited = new Set<string>();
        const checkCircular = (currentFieldId: string, path: string[] = []): boolean => {
            if (path.includes(currentFieldId)) {
                errors.push(`Referencia circular detectada: ${[...path, currentFieldId].join(' → ')}`);
                return true;
            }

            if (visited.has(currentFieldId)) return false;
            visited.add(currentFieldId);

            const currentField = fields.value.find(f => f.id === currentFieldId);
            if (!currentField?.conditionalConfig?.enabled) return false;

            for (const condition of currentField.conditionalConfig.conditions) {
                checkCircular(condition.fieldId, [...path, currentFieldId]);
            }

            return false;
        };

        checkCircular(field.id);

        return errors;
    };

    /**
     * Obtiene los campos disponibles para usar como fuente de condiciones
     */
    const getAvailableSourceFields = (excludeFieldId?: string): FormField[] => {
        return fields.value.filter(field => {
            // Excluir el campo actual
            if (field.id === excludeFieldId) return false;
            
            // Solo campos de tipo select, radio o checkbox pueden ser fuente
            return ['select', 'radio', 'checkbox'].includes(field.type);
        });
    };

    /**
     * Obtiene las opciones disponibles para un campo fuente
     */
    const getFieldOptions = (fieldId: string): string[] => {
        const field = fields.value.find(f => f.id === fieldId);
        if (!field) return [];
        
        // Para campos con opciones definidas
        if (field.options && field.options.length > 0) {
            return field.options;
        }
        
        // Para otros tipos de campos, retornar opciones básicas
        if (field.type === 'checkbox') {
            return ['checked', 'unchecked'];
        }
        
        return [];
    };

    /**
     * Limpia valores de campos ocultos en el formulario
     */
    const clearHiddenFieldValues = () => {
        fields.value.forEach(field => {
            if (!shouldShowField(field)) {
                // Limpiar valor del campo oculto
                if (field.type === 'checkbox') {
                    formData.value[field.id] = [];
                } else if (field.type === 'repeater') {
                    formData.value[field.id] = [];
                } else if (field.type === 'disclaimer') {
                    formData.value[field.id] = null;
                } else {
                    formData.value[field.id] = '';
                }
            }
        });
    };

    return {
        evaluateCondition,
        shouldShowField,
        visibleFields,
        getDependentFields,
        validateConditionalRules,
        getAvailableSourceFields,
        getFieldOptions,
        clearHiddenFieldValues,
        evaluationCache: computed(() => evaluationCache.value),
    };
}