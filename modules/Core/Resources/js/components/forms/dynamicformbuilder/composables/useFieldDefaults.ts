import type { FormField } from "@modules/Core/Resources/js/types/forms";

/**
 * Composable que provee valores por defecto para cada tipo de campo
 */
export function useFieldDefaults() {
    const getDefaultField = (type: FormField['type'] = 'text'): Partial<FormField> => {
        const baseField: Partial<FormField> = {
            id: `field_${Date.now()}`,
            type,
            title: '',
            description: '',
            required: false,
            options: [],
            editable: false,
            conditionalConfig: {
                enabled: false,
                showWhen: 'all',
                conditions: [],
            },
        };

        // Configuraciones específicas por tipo
        const typeSpecificDefaults: Record<string, Partial<FormField>> = {
            'perfil_candidatura': {
                perfilCandidaturaConfig: {
                    cargo_id: undefined,
                    periodo_electoral_id: undefined,
                    territorio_id: undefined,
                    departamento_id: undefined,
                    municipio_id: undefined,
                    localidad_id: undefined,
                    territorios_ids: [],
                    departamentos_ids: [],
                    municipios_ids: [],
                    localidades_ids: [],
                    multiple: false,
                    mostrarVotoBlanco: true,
                },
            },
            'convocatoria': {
                convocatoriaConfig: {
                    convocatoria_id: undefined,
                    multiple: false,
                    mostrarVotoBlanco: true,
                    ordenCandidatos: 'aleatorio',
                    vistaPreferida: 'lista',
                },
            },
            'file': {
                fileConfig: {
                    multiple: false,
                    maxFiles: 5,
                    maxFileSize: 10, // MB
                    accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
                },
            },
            'datepicker': {
                datepickerConfig: {
                    minDate: undefined,
                    maxDate: undefined,
                    format: 'DD/MM/YYYY',
                    allowPastDates: true,
                    allowFutureDates: true,
                },
            },
            'disclaimer': {
                disclaimerConfig: {
                    disclaimerText: '',
                    modalTitle: 'Términos y Condiciones',
                    acceptButtonText: 'Acepto',
                    declineButtonText: 'No acepto',
                },
            },
            'repeater': {
                repeaterConfig: {
                    minItems: 0,
                    maxItems: 10,
                    itemName: 'Elemento',
                    addButtonText: 'Agregar elemento',
                    removeButtonText: 'Eliminar',
                    fields: [],
                },
            },
            'number': {
                numberConfig: {
                    min: undefined,
                    max: undefined,
                    step: 1,
                    decimals: 0,
                },
            },
        };

        return {
            ...baseField,
            ...typeSpecificDefaults[type] || {},
        };
    };

    const resetFieldToDefaults = (field: Partial<FormField>, type: FormField['type']) => {
        const defaults = getDefaultField(type);
        return {
            ...defaults,
            id: field.id || defaults.id,
            type,
        };
    };

    return {
        getDefaultField,
        resetFieldToDefaults,
    };
}