import type { FormField } from '@/types/forms';

interface ValidationError {
    field: string;
    message: string;
}

/**
 * Composable para validaciones de campos del formulario dinámico
 */
export function useFieldValidation() {
    /**
     * Valida que un campo tenga la configuración mínima requerida
     */
    const validateField = (field: Partial<FormField>): ValidationError[] => {
        const errors: ValidationError[] = [];

        // Validación del título (requerido)
        if (!field.title?.trim()) {
            errors.push({
                field: 'title',
                message: 'El título del campo es requerido',
            });
        }

        // Validación de opciones para campos de selección
        if (['select', 'radio', 'checkbox'].includes(field.type || '')) {
            if (!field.options || field.options.length === 0) {
                errors.push({
                    field: 'options',
                    message: 'Los campos de selección deben tener al menos una opción',
                });
            } else {
                // Verificar que las opciones no estén vacías
                const emptyOptions = field.options.filter((opt, index) => !opt.trim());
                if (emptyOptions.length > 0) {
                    errors.push({
                        field: 'options',
                        message: 'Todas las opciones deben tener texto',
                    });
                }
            }
        }

        // Validación específica para perfil_candidatura
        if (field.type === 'perfil_candidatura' && field.perfilCandidaturaConfig) {
            const config = field.perfilCandidaturaConfig;
            if (!config.cargo_id && !config.periodo_electoral_id) {
                errors.push({
                    field: 'perfilCandidaturaConfig',
                    message: 'Debe seleccionar al menos un cargo o período electoral',
                });
            }
        }

        // Validación específica para convocatoria
        if (field.type === 'convocatoria' && field.convocatoriaConfig) {
            const config = field.convocatoriaConfig;
            if (!config.convocatoria_id) {
                errors.push({
                    field: 'convocatoriaConfig',
                    message: 'Debe seleccionar una convocatoria',
                });
            }
        }

        // Validación para archivo
        if (field.type === 'file' && field.fileConfig) {
            const config = field.fileConfig;
            if (config.maxFileSize && (config.maxFileSize < 1 || config.maxFileSize > 100)) {
                errors.push({
                    field: 'fileConfig',
                    message: 'El tamaño máximo debe estar entre 1 y 100 MB',
                });
            }
            if (config.maxFiles && (config.maxFiles < 1 || config.maxFiles > 20)) {
                errors.push({
                    field: 'fileConfig',
                    message: 'El número máximo de archivos debe estar entre 1 y 20',
                });
            }
        }

        // Validación para disclaimer
        if (field.type === 'disclaimer' && field.disclaimerConfig) {
            const config = field.disclaimerConfig;
            if (!config.disclaimerText?.trim()) {
                errors.push({
                    field: 'disclaimerConfig',
                    message: 'El texto del disclaimer es requerido',
                });
            }
        }

        // Validación para número
        if (field.type === 'number' && field.numberConfig) {
            const config = field.numberConfig;
            if (config.min !== undefined && config.max !== undefined && config.min > config.max) {
                errors.push({
                    field: 'numberConfig',
                    message: 'El valor mínimo no puede ser mayor al máximo',
                });
            }
            if (config.decimals !== undefined && (config.decimals < 0 || config.decimals > 10)) {
                errors.push({
                    field: 'numberConfig',
                    message: 'Los decimales deben estar entre 0 y 10',
                });
            }
        }

        // Validación para repeater
        if (field.type === 'repeater' && field.repeaterConfig) {
            const config = field.repeaterConfig;
            if (config.minItems !== undefined && config.maxItems !== undefined && config.minItems > config.maxItems) {
                errors.push({
                    field: 'repeaterConfig',
                    message: 'El mínimo de elementos no puede ser mayor al máximo',
                });
            }
        }

        return errors;
    };

    /**
     * Verifica si un campo es válido
     */
    const isFieldValid = (field: Partial<FormField>): boolean => {
        return validateField(field).length === 0;
    };

    /**
     * Obtiene el primer error de un campo
     */
    const getFirstError = (field: Partial<FormField>): string | null => {
        const errors = validateField(field);
        return errors.length > 0 ? errors[0].message : null;
    };

    return {
        validateField,
        isFieldValid,
        getFirstError,
    };
}