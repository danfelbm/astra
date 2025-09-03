import { ref, reactive } from 'vue';
import type { FormField } from "@modules/Core/Resources/js/types/forms";

export function useFormBuilder(initialFields: FormField[] = []) {
    const fields = ref<FormField[]>([...initialFields]);
    const showFieldForm = ref(false);
    const editingFieldIndex = ref<number | null>(null);
    
    const newField = reactive<FormField>({
        id: '',
        type: 'text',
        title: '',
        description: '',
        required: false,
        options: [],
        editable: false,
        perfilCandidaturaConfig: undefined, // Solo se inicializa si es necesario
        convocatoriaConfig: {
            convocatoria_id: undefined,
            multiple: false,
            mostrarVotoBlanco: true,
            ordenCandidatos: 'aleatorio',
            vistaPreferida: 'lista',
        },
        fileConfig: {
            multiple: false,
            maxFiles: 5,
            maxFileSize: 10, // 10MB por defecto
            accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
        },
        datepickerConfig: {
            minDate: undefined,
            maxDate: undefined,
            format: 'DD/MM/YYYY',
            allowPastDates: true,
            allowFutureDates: true,
        },
        disclaimerConfig: {
            disclaimerText: '',
            modalTitle: 'Términos y Condiciones',
            acceptButtonText: 'Acepto',
            declineButtonText: 'No acepto',
        },
        repeaterConfig: {
            minItems: 0,
            maxItems: 10,
            itemName: 'Elemento',
            addButtonText: 'Agregar elemento',
            removeButtonText: 'Eliminar',
            fields: [],
        },
        numberConfig: {
            min: undefined,
            max: undefined,
            step: 1,
            decimals: 0,
        },
        conditionalConfig: {
            enabled: false,
            showWhen: 'all',
            conditions: [],
        },
    });

    // Reset form field to initial state
    const resetFieldForm = () => {
        newField.id = '';
        newField.type = 'text';
        newField.title = '';
        newField.description = '';
        newField.required = false;
        newField.options = [];
        newField.editable = false;
        newField.perfilCandidaturaConfig = undefined; // Se inicializa solo cuando se necesita
        newField.convocatoriaConfig = {
            convocatoria_id: undefined,
            multiple: false,
            mostrarVotoBlanco: true,
            ordenCandidatos: 'aleatorio',
            vistaPreferida: 'lista',
        };
        newField.fileConfig = {
            multiple: false,
            maxFiles: 5,
            maxFileSize: 10,
            accept: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
        };
        newField.datepickerConfig = {
            minDate: undefined,
            maxDate: undefined,
            format: 'DD/MM/YYYY',
            allowPastDates: true,
            allowFutureDates: true,
        };
        newField.disclaimerConfig = {
            disclaimerText: '',
            modalTitle: 'Términos y Condiciones',
            acceptButtonText: 'Acepto',
            declineButtonText: 'No acepto',
        };
        newField.repeaterConfig = {
            minItems: 0,
            maxItems: 10,
            itemName: 'Elemento',
            addButtonText: 'Agregar elemento',
            removeButtonText: 'Eliminar',
            fields: [],
        };
        newField.numberConfig = {
            min: undefined,
            max: undefined,
            step: 1,
            decimals: 0,
        };
        newField.conditionalConfig = {
            enabled: false,
            showWhen: 'all',
            conditions: [],
        };
        showFieldForm.value = false;
        editingFieldIndex.value = null;
    };

    // Add or update field
    const addField = () => {
        const fieldToAdd: FormField = {
            id: newField.id || `field_${Date.now()}`,
            type: newField.type,
            title: newField.title,
            description: newField.description,
            required: newField.required,
            editable: newField.editable,
            options: newField.options?.filter(opt => opt.trim() !== '') || [],
        };

        // Agregar configuración de perfil_candidatura si es necesario
        if (newField.type === 'perfil_candidatura' && newField.perfilCandidaturaConfig) {
            fieldToAdd.perfilCandidaturaConfig = {
                ...newField.perfilCandidaturaConfig,
                // Convertir strings a números si es necesario (y manejar "all")
                cargo_id: newField.perfilCandidaturaConfig.cargo_id && newField.perfilCandidaturaConfig.cargo_id !== 'all' ? 
                    Number(newField.perfilCandidaturaConfig.cargo_id) : undefined,
                periodo_electoral_id: newField.perfilCandidaturaConfig.periodo_electoral_id && newField.perfilCandidaturaConfig.periodo_electoral_id !== 'all' ? 
                    Number(newField.perfilCandidaturaConfig.periodo_electoral_id) : undefined,
                // Mantener arrays de IDs geográficos
                territorios_ids: newField.perfilCandidaturaConfig.territorios_ids || [],
                departamentos_ids: newField.perfilCandidaturaConfig.departamentos_ids || [],
                municipios_ids: newField.perfilCandidaturaConfig.municipios_ids || [],
                localidades_ids: newField.perfilCandidaturaConfig.localidades_ids || [],
            };
        }

        // Agregar configuración de convocatoria si es necesario
        if (newField.type === 'convocatoria' && newField.convocatoriaConfig) {
            fieldToAdd.convocatoriaConfig = {
                ...newField.convocatoriaConfig,
                // Convertir string a número si es necesario
                convocatoria_id: newField.convocatoriaConfig.convocatoria_id ? 
                    Number(newField.convocatoriaConfig.convocatoria_id) : undefined,
                // Asegurar que los nuevos campos se incluyan
                ordenCandidatos: newField.convocatoriaConfig.ordenCandidatos || 'aleatorio',
                vistaPreferida: newField.convocatoriaConfig.vistaPreferida || 'lista',
            };
        }

        // Agregar configuración de archivo si es necesario
        if (newField.type === 'file' && newField.fileConfig) {
            fieldToAdd.fileConfig = {
                ...newField.fileConfig,
            };
        }

        // Agregar configuración de datepicker si es necesario
        if (newField.type === 'datepicker' && newField.datepickerConfig) {
            fieldToAdd.datepickerConfig = {
                ...newField.datepickerConfig,
            };
        }

        // Agregar configuración de disclaimer si es necesario
        if (newField.type === 'disclaimer' && newField.disclaimerConfig) {
            fieldToAdd.disclaimerConfig = {
                ...newField.disclaimerConfig,
            };
        }

        // Agregar configuración de repeater si es necesario
        if (newField.type === 'repeater' && newField.repeaterConfig) {
            fieldToAdd.repeaterConfig = {
                ...newField.repeaterConfig,
                fields: [...newField.repeaterConfig.fields], // Clonar el array de campos
            };
        }

        // Agregar configuración de number si es necesario
        if (newField.type === 'number' && newField.numberConfig) {
            fieldToAdd.numberConfig = {
                ...newField.numberConfig,
            };
        }

        // Agregar configuración condicional si está habilitada
        if (newField.conditionalConfig && newField.conditionalConfig.enabled) {
            fieldToAdd.conditionalConfig = {
                enabled: newField.conditionalConfig.enabled,
                showWhen: newField.conditionalConfig.showWhen,
                conditions: [...newField.conditionalConfig.conditions],
            };
        }

        // Debug log
        console.log('Adding field:', {
            required: newField.required,
            editable: newField.editable,
            fieldToAdd: fieldToAdd
        });

        if (editingFieldIndex.value !== null) {
            fields.value[editingFieldIndex.value] = fieldToAdd;
            editingFieldIndex.value = null;
        } else {
            fields.value.push(fieldToAdd);
        }

        resetFieldForm();
    };

    // Edit existing field
    const editField = (index: number) => {
        const field = fields.value[index];
        
        // Debug log
        console.log('Editing field:', {
            originalField: field,
            required: field.required,
            editable: field.editable
        });
        
        newField.id = field.id;
        newField.type = field.type;
        newField.title = field.title;
        newField.description = field.description || '';
        newField.required = field.required ?? false;
        newField.options = [...(field.options || [])];
        newField.editable = field.editable ?? false;
        
        // Cargar configuración de perfil_candidatura si existe
        if (field.type === 'perfil_candidatura') {
            newField.perfilCandidaturaConfig = {
                cargo_id: field.perfilCandidaturaConfig?.cargo_id || undefined,
                periodo_electoral_id: field.perfilCandidaturaConfig?.periodo_electoral_id || undefined,
                territorio_id: field.perfilCandidaturaConfig?.territorio_id || undefined,
                departamento_id: field.perfilCandidaturaConfig?.departamento_id || undefined,
                municipio_id: field.perfilCandidaturaConfig?.municipio_id || undefined,
                localidad_id: field.perfilCandidaturaConfig?.localidad_id || undefined,
                territorios_ids: field.perfilCandidaturaConfig?.territorios_ids || [],
                departamentos_ids: field.perfilCandidaturaConfig?.departamentos_ids || [],
                municipios_ids: field.perfilCandidaturaConfig?.municipios_ids || [],
                localidades_ids: field.perfilCandidaturaConfig?.localidades_ids || [],
                multiple: field.perfilCandidaturaConfig?.multiple || false,
                mostrarVotoBlanco: field.perfilCandidaturaConfig?.mostrarVotoBlanco ?? true,
            };
        }

        // Cargar configuración de convocatoria si existe
        if (field.type === 'convocatoria') {
            newField.convocatoriaConfig = {
                convocatoria_id: field.convocatoriaConfig?.convocatoria_id || undefined,
                multiple: field.convocatoriaConfig?.multiple || false,
                mostrarVotoBlanco: field.convocatoriaConfig?.mostrarVotoBlanco ?? true,
                ordenCandidatos: field.convocatoriaConfig?.ordenCandidatos || 'aleatorio',
                vistaPreferida: field.convocatoriaConfig?.vistaPreferida || 'lista',
            };
        }

        // Cargar configuración de archivo si existe
        if (field.type === 'file') {
            newField.fileConfig = {
                multiple: field.fileConfig?.multiple || false,
                maxFiles: field.fileConfig?.maxFiles || 5,
                maxFileSize: field.fileConfig?.maxFileSize || 10,
                accept: field.fileConfig?.accept || '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif',
            };
        }

        // Cargar configuración de datepicker si existe
        if (field.type === 'datepicker') {
            newField.datepickerConfig = {
                minDate: field.datepickerConfig?.minDate || undefined,
                maxDate: field.datepickerConfig?.maxDate || undefined,
                format: field.datepickerConfig?.format || 'DD/MM/YYYY',
                allowPastDates: field.datepickerConfig?.allowPastDates ?? true,
                allowFutureDates: field.datepickerConfig?.allowFutureDates ?? true,
            };
        }

        // Cargar configuración de disclaimer si existe
        if (field.type === 'disclaimer') {
            newField.disclaimerConfig = {
                disclaimerText: field.disclaimerConfig?.disclaimerText || '',
                modalTitle: field.disclaimerConfig?.modalTitle || 'Términos y Condiciones',
                acceptButtonText: field.disclaimerConfig?.acceptButtonText || 'Acepto',
                declineButtonText: field.disclaimerConfig?.declineButtonText || 'No acepto',
            };
        }

        // Cargar configuración de repeater si existe
        if (field.type === 'repeater') {
            newField.repeaterConfig = {
                minItems: field.repeaterConfig?.minItems || 0,
                maxItems: field.repeaterConfig?.maxItems || 10,
                itemName: field.repeaterConfig?.itemName || 'Elemento',
                addButtonText: field.repeaterConfig?.addButtonText || 'Agregar elemento',
                removeButtonText: field.repeaterConfig?.removeButtonText || 'Eliminar',
                fields: field.repeaterConfig?.fields ? [...field.repeaterConfig.fields] : [],
            };
        }

        // Cargar configuración de number si existe
        if (field.type === 'number') {
            newField.numberConfig = {
                min: field.numberConfig?.min || undefined,
                max: field.numberConfig?.max || undefined,
                step: field.numberConfig?.step || 1,
                decimals: field.numberConfig?.decimals || 0,
            };
        }

        // Cargar configuración condicional si existe
        if (field.conditionalConfig) {
            newField.conditionalConfig = {
                enabled: field.conditionalConfig.enabled || false,
                showWhen: field.conditionalConfig.showWhen || 'all',
                conditions: field.conditionalConfig.conditions ? [...field.conditionalConfig.conditions] : [],
            };
        }
        
        // Debug log after assignment
        console.log('After assignment:', {
            newFieldRequired: newField.required,
            newFieldEditable: newField.editable
        });
        
        editingFieldIndex.value = index;
        showFieldForm.value = true;
    };

    // Remove field
    const removeField = (index: number) => {
        fields.value.splice(index, 1);
    };

    // Add option to select/radio/checkbox fields
    const addOption = () => {
        if (!newField.options) newField.options = [];
        newField.options.push('');
    };

    // Remove option
    const removeOption = (index: number) => {
        newField.options?.splice(index, 1);
    };

    // Initialize fields from external source
    const setFields = (newFields: FormField[]) => {
        fields.value = [...newFields];
    };

    // Check if form can be saved (has at least one field)
    const canSave = () => {
        return fields.value.length > 0;
    };

    return {
        // State
        fields,
        newField,
        showFieldForm,
        editingFieldIndex,
        
        // Actions
        addField,
        editField,
        removeField,
        resetFieldForm,
        addOption,
        removeOption,
        setFields,
        canSave,
    };
}