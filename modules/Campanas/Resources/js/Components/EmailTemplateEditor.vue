<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import { useCKEditorConfig } from '../composables/useCKEditorConfig';
import {
    Eye, Variable,
} from 'lucide-vue-next';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { useTemplateVariables } from '../composables/useTemplateVariables';

// Importar estilos de CKEditor
import 'ckeditor5/ckeditor5.css';

interface Props {
    modelValue: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Escribe el contenido del email...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:variables': [value: string[]];
}>();

const {
    availableVariables,
    variableCategories,
    extractUsedVariables,
    replaceWithExamples,
    estimateLength
} = useTemplateVariables();

// Estado del componente
const previewMode = ref(false);
const previewContent = ref('');
const usedVariables = ref<string[]>([]);
const editorData = ref(props.modelValue);
const editorInstance = ref<any>(null);

// Configuración de CKEditor
const { editor, config } = useCKEditorConfig();

// Handler cuando el editor está listo
const onEditorReady = (editorObj: any) => {
    editorInstance.value = editorObj;

    // Configurar el contenido inicial si existe
    if (props.modelValue) {
        editorObj.setData(props.modelValue);
    }
};

// Handler cuando el contenido cambia
const onEditorChange = (html: string) => {
    editorData.value = html;
    emit('update:modelValue', html);
    updateUsedVariables(html);
};

// Actualizar variables usadas
const updateUsedVariables = (content: string) => {
    const variables = extractUsedVariables(content);
    usedVariables.value = variables;
    emit('update:variables', variables);
};

// Insertar variable en el editor
const insertVariable = (variable: string) => {
    if (editorInstance.value) {
        // Obtener el modelo del editor
        const model = editorInstance.value.model;
        const selection = model.document.selection;

        // Insertar la variable en la posición del cursor
        model.change((writer: any) => {
            const insertPosition = selection.getFirstPosition();
            writer.insertText(variable, insertPosition);
        });

        // Hacer foco en el editor
        editorInstance.value.editing.view.focus();
    }
};

// Toggle preview
const togglePreview = () => {
    if (!previewMode.value) {
        const content = editorInstance.value?.getData() || '';
        previewContent.value = replaceWithExamples(content);
    }
    previewMode.value = !previewMode.value;
};

// Estadísticas del contenido
const contentStats = computed(() => {
    const content = editorInstance.value?.getData() || '';
    const estimation = estimateLength(content);

    return {
        variables: usedVariables.value.length,
        charactersMin: estimation.min,
        charactersMax: estimation.max,
        charactersAvg: estimation.avg,
    };
});

// Watch para actualizar el editor cuando cambie el modelValue externamente
watch(() => props.modelValue, (value) => {
    if (editorInstance.value && editorInstance.value.getData() !== value) {
        editorInstance.value.setData(value || '');
    }
});

// Limpiar al desmontar
onBeforeUnmount(() => {
    if (editorInstance.value) {
        editorInstance.value.destroy();
        editorInstance.value = null;
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Editor principal -->
        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <CardTitle>Contenido del Email</CardTitle>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="togglePreview"
                    >
                        <Eye class="w-4 h-4 mr-2" />
                        {{ previewMode ? 'Editar' : 'Vista previa' }}
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <Tabs :modelValue="previewMode ? 'preview' : 'editor'" class="w-full">
                    <TabsList class="grid w-full grid-cols-2">
                        <TabsTrigger value="editor" @click="previewMode = false">Editor</TabsTrigger>
                        <TabsTrigger value="preview" @click="previewMode = true">Vista Previa</TabsTrigger>
                    </TabsList>

                    <!-- Editor -->
                    <TabsContent value="editor" class="mt-0">
                        <div class="ckeditor-wrapper">
                            <ckeditor
                                :model-value="editorData"
                                :editor="editor"
                                :config="config"
                                @ready="onEditorReady"
                                @input="onEditorChange"
                            />
                        </div>
                    </TabsContent>

                    <!-- Vista previa -->
                    <TabsContent value="preview" class="mt-0">
                        <div class="p-4 border min-h-[300px]">
                            <div class="prose prose-sm max-w-none" v-html="previewContent"></div>
                        </div>
                    </TabsContent>
                </Tabs>
            </CardContent>
        </Card>

        <!-- Panel de variables -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Variables Disponibles</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <p class="text-sm text-muted-foreground mb-4">
                        Haz clic en una variable para insertarla en el editor
                    </p>
                    <div v-for="(category, key) in variableCategories" :key="key">
                        <h4 class="text-sm font-medium mb-2">{{ category.label }}</h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="variable in category.variables"
                                :key="variable.key"
                                variant="outline"
                                class="cursor-pointer hover:bg-accent"
                                @click="insertVariable(variable.key)"
                                :title="variable.label + ' - Ejemplo: ' + variable.example"
                            >
                                <Variable class="w-3 h-3 mr-1" />
                                {{ variable.key }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Estadísticas -->
        <Card>
            <CardContent class="pt-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-muted-foreground">Variables usadas:</span>
                        <span class="ml-2 font-medium">{{ contentStats.variables }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Caracteres (mín):</span>
                        <span class="ml-2 font-medium">{{ contentStats.charactersMin }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Caracteres (máx):</span>
                        <span class="ml-2 font-medium">{{ contentStats.charactersMax }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Promedio:</span>
                        <span class="ml-2 font-medium">{{ contentStats.charactersAvg }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
/* Estilos personalizados para CKEditor */
.ckeditor-wrapper {
    border: 1px solid hsl(var(--border));
    border-radius: 0.375rem;
    overflow: hidden;
}

.ckeditor-wrapper :deep(.ck-editor__main) {
    min-height: 300px;
}

.ckeditor-wrapper :deep(.ck-content) {
    min-height: 300px;
    padding: 1rem;
}

.ckeditor-wrapper :deep(.ck-toolbar) {
    border: none;
    border-bottom: 1px solid hsl(var(--border));
    background-color: hsl(var(--muted) / 0.3);
}

.prose :deep(p) {
    margin-bottom: 0.5rem;
}

.prose :deep(ul),
.prose :deep(ol) {
    margin: 0.5rem 0;
}

.prose :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
}

.prose :deep(table) {
    width: 100%;
    border-collapse: collapse;
}

.prose :deep(table td),
.prose :deep(table th) {
    border: 1px solid hsl(var(--border));
    padding: 0.5rem;
}
</style>
