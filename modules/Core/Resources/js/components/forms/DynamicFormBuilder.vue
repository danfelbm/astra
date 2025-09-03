<script setup lang="ts">
import { watch, nextTick, ref } from 'vue';
import { useFormBuilder } from "@modules/Core/Resources/js/composables/useFormBuilder";

// Importar componentes modulares
import FieldForm from './dynamicformbuilder/FieldForm.vue';
import FieldList from './dynamicformbuilder/FieldList.vue';

import type { FormField } from "@modules/Core/Resources/js/types/forms";

interface Props {
    modelValue: FormField[];
    disabled?: boolean;
    title?: string;
    description?: string;
    showEditableOption?: boolean; // Mostrar opción "Campo editable" (solo para candidaturas)
    // Props para perfil_candidatura en votaciones (deprecated)
    showPerfilCandidaturaConfig?: boolean; // Mostrar configuración avanzada para perfil_candidatura
    cargos?: Array<{ id: number; nombre: string; ruta_jerarquica?: string }>;
    periodosElectorales?: Array<{ id: number; nombre: string; fecha_inicio: string; fecha_fin: string }>;
    // Props para campo convocatoria en votaciones
    showConvocatoriaConfig?: boolean; // Mostrar configuración para campo convocatoria
    convocatorias?: Array<{ 
        id: number; 
        nombre: string; 
        cargo?: { nombre: string; ruta_jerarquica?: string };
        periodo_electoral?: { nombre: string };
        estado_temporal?: string;
    }>;
    context?: 'convocatoria' | 'votacion' | 'candidatura'; // Contexto del formulario
}

interface Emits {
    (e: 'update:modelValue', value: FormField[]): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    title: 'Constructor de Formulario',
    description: 'Agrega los campos que aparecerán en el formulario',
    showEditableOption: false,
    showPerfilCandidaturaConfig: false,
    cargos: () => [],
    periodosElectorales: () => [],
    showConvocatoriaConfig: false,
    convocatorias: () => [],
    context: 'convocatoria',
});

const emit = defineEmits<Emits>();

// Estado local simple sin useFormBuilder problemático
const fields = ref<FormField[]>([...props.modelValue]);
const showFieldForm = ref(false);
const editingFieldIndex = ref<number | null>(null);

// Variables de estado para el formulario
const currentFormMode = ref<'create' | 'edit'>('create');
const editingField = ref<FormField | undefined>(undefined);

// Métodos simples para manejo de campos
const removeField = (index: number) => {
    fields.value.splice(index, 1);
    emitFieldsChange();
};

const canSave = () => {
    return fields.value.length > 0;
};

// Flag para prevenir loops infinitos
const isUpdatingFromProps = ref(false);

// Watchers SIN loop infinito
watch(() => props.modelValue, (newFields) => {
    if (!isUpdatingFromProps.value) {
        isUpdatingFromProps.value = true;
        fields.value = [...newFields];
        nextTick(() => {
            isUpdatingFromProps.value = false;
        });
    }
}, { immediate: true });

// Solo emitir cambios cuando NO vengan de props
const emitFieldsChange = () => {
    if (!isUpdatingFromProps.value) {
        emit('update:modelValue', [...fields.value]);
    }
};

// Métodos para manejar acciones del FieldList
const handleAddField = (event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('handleAddField called');
    currentFormMode.value = 'create';
    editingField.value = undefined;
    editingFieldIndex.value = null;
    showFieldForm.value = true;
    scrollToForm(true);
};

const handleEditField = (index: number, event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('handleEditField called with index:', index);
    const field = fields.value[index];
    if (!field) return;
    
    editingField.value = { ...field };
    currentFormMode.value = 'edit';
    editingFieldIndex.value = index;
    showFieldForm.value = true;
    scrollToForm(false, index);
};

const handleDeleteField = async (index: number, event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const field = fields.value[index];
    if (!field) return;
    
    // Modal de confirmación usando confirm nativo por ahora
    // TODO: Implementar modal personalizado con Shadcn
    const confirmed = confirm(
        `¿Estás seguro de eliminar el campo "${field.title || 'Sin título'}"?\n\nEsta acción no se puede deshacer.`
    );
    
    if (confirmed) {
        removeField(index);
        console.log('Field deleted:', field.title);
        
        // Si estamos editando este campo, cerrar el formulario
        if (editingFieldIndex.value === index) {
            handleCancelForm();
        }
        // Si estamos editando un campo que está después del eliminado, ajustar el índice
        else if (editingFieldIndex.value !== null && editingFieldIndex.value > index) {
            editingFieldIndex.value = editingFieldIndex.value - 1;
        }
    }
};

const handleDuplicateField = (originalField: FormField, event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Crear una copia completa del campo con nuevo ID
    const duplicatedField: FormField = {
        ...originalField,
        id: `field_${Date.now()}`, // Nuevo ID único
        title: `${originalField.title} (Copia)`, // Indicar que es una copia
    };
    
    console.log('Duplicating field:', { original: originalField.title, duplicate: duplicatedField.title });
    
    // Agregar el campo duplicado a la lista
    fields.value.push(duplicatedField);
    emitFieldsChange();
};

const handlePreviewField = (field: FormField, event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    // Vista previa básica usando alert por ahora
    // TODO: Implementar modal personalizado con Shadcn
    
    const fieldTypeInfo = {
        'text': 'Campo de texto',
        'textarea': 'Área de texto',
        'select': 'Lista desplegable',
        'radio': 'Opción múltiple',
        'checkbox': 'Casillas de verificación',
        'file': 'Subida de archivo',
        'datepicker': 'Selector de fecha',
        'number': 'Campo numérico',
        'disclaimer': 'Términos y condiciones',
        'repeater': 'Campo repetible',
        'convocatoria': 'Selector de convocatoria',
        'perfil_candidatura': 'Perfil de candidatura'
    };
    
    let previewText = `📋 VISTA PREVIA DEL CAMPO\n\n`;
    previewText += `Título: ${field.title || 'Sin título'}\n`;
    previewText += `Tipo: ${fieldTypeInfo[field.type] || field.type}\n`;
    previewText += `Requerido: ${field.required ? 'Sí' : 'No'}\n`;
    
    if (field.description) {
        previewText += `Descripción: ${field.description}\n`;
    }
    
    if (field.options && field.options.length > 0) {
        previewText += `Opciones: ${field.options.join(', ')}\n`;
    }
    
    if (field.conditionalConfig?.enabled) {
        previewText += `Campo condicional: Sí (${field.conditionalConfig.conditions.length} condiciones)\n`;
    }
    
    alert(previewText);
};


// Métodos para manejar acciones del FieldForm
const handleSaveField = (field: FormField) => {
    console.log('Saving field:', { mode: currentFormMode.value, field, editingIndex: editingFieldIndex.value });
    
    if (currentFormMode.value === 'edit' && editingFieldIndex.value !== null) {
        // Editar campo existente
        fields.value[editingFieldIndex.value] = { ...field };
        console.log('Field edited successfully');
    } else {
        // Agregar nuevo campo
        fields.value.push({ ...field });
        console.log('Field added successfully');
    }
    emitFieldsChange();
    handleCancelForm();
};

const handleCancelForm = (event?: Event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('handleCancelForm called');
    showFieldForm.value = false;
    editingFieldIndex.value = null;
    editingField.value = undefined;
    currentFormMode.value = 'create';
};

// Función de scroll mejorada
const scrollToForm = async (isNewField: boolean = false, fieldIndex?: number) => {
    try {
        await nextTick();
        setTimeout(() => {
            let targetElement = null;
            if (isNewField) {
                targetElement = document.querySelector('[data-form-type="new-field"]');
            } else if (fieldIndex !== undefined) {
                targetElement = document.querySelector(`[data-form-type="edit-field"]`);
                // También podría usar un ID específico si se necesita más precisión
            }
            if (targetElement && typeof targetElement.scrollIntoView === 'function') {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 150);
    } catch (error) {
        console.warn('Error en auto-scroll:', error);
    }
};

// Exponer métodos si es necesario para componente padre
defineExpose({
    canSave,
    fields,
});
</script>

<template>
    <div class="space-y-6">

        <!-- Formulario de campo (nuevo o edición) -->
        <FieldForm
            v-if="showFieldForm"
            :mode="currentFormMode"
            :field="editingField"
            :disabled="disabled"
            :show-editable-option="showEditableOption"
            :show-perfil-candidatura-config="showPerfilCandidaturaConfig"
            :show-convocatoria-config="showConvocatoriaConfig"
            :cargos="cargos"
            :periodos-electorales="periodosElectorales"
            :convocatorias="convocatorias"
            :context="context"
            :available-fields="fields"
            @save="handleSaveField"
            @cancel="handleCancelForm"
        />
        
        <!-- Lista de campos -->
        <FieldList
            :fields="fields"
            :disabled="disabled"
            :can-add-fields="!disabled"
            :can-edit-fields="!disabled"
            :can-delete-fields="!disabled"
            :title="title"
            :description="description"
            @add-field="handleAddField"
            @edit-field="handleEditField"
            @delete-field="handleDeleteField"
            @duplicate-field="handleDuplicateField"
            @preview-field="handlePreviewField"
        />
    </div>
</template>

<style scoped>
/* Estilos específicos si son necesarios */
</style>