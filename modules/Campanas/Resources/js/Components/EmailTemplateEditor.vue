<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { watch, onBeforeUnmount, ref, computed } from 'vue';
import { 
    Bold, Italic, List, ListOrdered, Link2, Image as ImageIcon, 
    Type, Palette, Code, Eye, Variable, Undo, Redo,
    AlignLeft, AlignCenter, AlignRight, AlignJustify
} from 'lucide-vue-next';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@modules/Core/Resources/js/components/ui/dropdown-menu';
import { useTemplateVariables } from '../composables/useTemplateVariables';

interface Props {
    modelValue: string;
    asunto?: string;
    placeholder?: string;
    variables?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    asunto: '',
    placeholder: 'Escribe el contenido del email...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:asunto': [value: string];
    'update:variables': [value: string[]];
}>();

const { 
    availableVariables, 
    variableCategories,
    extractUsedVariables,
    replaceWithExamples,
    estimateLength
} = useTemplateVariables();

const previewMode = ref(false);
const previewContent = ref('');
const asuntoLocal = ref(props.asunto);
const usedVariables = ref<string[]>([]);

// Configuración del editor
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none min-h-[300px] p-4 focus:outline-none',
            'data-placeholder': props.placeholder,
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        emit('update:modelValue', html);
        updateUsedVariables(html);
    },
});

// Actualizar variables usadas
const updateUsedVariables = (content: string) => {
    const allContent = `${asuntoLocal.value} ${content}`;
    const variables = extractUsedVariables(allContent);
    usedVariables.value = variables;
    emit('update:variables', variables);
};

// Insertar variable
const insertVariable = (variable: string) => {
    if (editor.value) {
        editor.value.chain().focus().insertContent(variable).run();
    }
};

// Insertar enlace (simplificado sin extensión Link)
const insertLink = () => {
    const url = window.prompt('URL del enlace:');
    if (url && editor.value) {
        const selectedText = editor.value.state.doc.textBetween(
            editor.value.state.selection.from,
            editor.value.state.selection.to,
            ''
        );
        const linkHtml = `<a href="${url}" class="text-primary underline">${selectedText || url}</a>`;
        editor.value.chain().focus().insertContent(linkHtml).run();
    }
};

// Insertar imagen (simplificado sin extensión Image)
const insertImage = () => {
    const url = window.prompt('URL de la imagen:');
    if (url && editor.value) {
        const imgHtml = `<img src="${url}" alt="Imagen" style="max-width: 100%;" />`;
        editor.value.chain().focus().insertContent(imgHtml).run();
    }
};

// Cambiar color de texto (simplificado sin extensión Color)
const setTextColor = (color: string) => {
    if (editor.value) {
        // Sin la extensión Color, simplemente envolvemos el texto seleccionado en un span
        const selectedText = editor.value.state.doc.textBetween(
            editor.value.state.selection.from,
            editor.value.state.selection.to,
            ''
        );
        if (selectedText) {
            const colorHtml = `<span style="color: ${color}">${selectedText}</span>`;
            editor.value.chain().focus().insertContent(colorHtml).run();
        }
    }
};

// Preview del template
const togglePreview = () => {
    if (!previewMode.value) {
        const content = editor.value?.getHTML() || '';
        previewContent.value = replaceWithExamples(content);
    }
    previewMode.value = !previewMode.value;
};

// Estadísticas del contenido
const contentStats = computed(() => {
    const content = editor.value?.getHTML() || '';
    const estimation = estimateLength(content);
    
    return {
        variables: usedVariables.value.length,
        charactersMin: estimation.min,
        charactersMax: estimation.max,
        charactersAvg: estimation.avg,
    };
});

// Watch para actualizar el editor cuando cambie el modelValue
watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value, false);
    }
});

// Watch para el asunto
watch(asuntoLocal, (value) => {
    emit('update:asunto', value);
    updateUsedVariables(editor.value?.getHTML() || '');
});

// Limpiar al desmontar
onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Campo de asunto -->
        <div>
            <label class="block text-sm font-medium mb-2">Asunto del email</label>
            <input
                v-model="asuntoLocal"
                type="text"
                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Asunto del email..."
            />
        </div>

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
                        <!-- Toolbar -->
                        <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b bg-muted/30 p-2">
                            <!-- Formato básico -->
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editor.chain().focus().toggleBold().run()"
                                    :class="{ 'bg-muted': editor.isActive('bold') }"
                                    class="h-8 w-8 p-0"
                                >
                                    <Bold class="h-4 w-4" />
                                </Button>
                                
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editor.chain().focus().toggleItalic().run()"
                                    :class="{ 'bg-muted': editor.isActive('italic') }"
                                    class="h-8 w-8 p-0"
                                >
                                    <Italic class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <div class="mx-1 h-6 w-px bg-border" />
                            
                            <!-- Listas -->
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editor.chain().focus().toggleBulletList().run()"
                                    :class="{ 'bg-muted': editor.isActive('bulletList') }"
                                    class="h-8 w-8 p-0"
                                >
                                    <List class="h-4 w-4" />
                                </Button>
                                
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editor.chain().focus().toggleOrderedList().run()"
                                    :class="{ 'bg-muted': editor.isActive('orderedList') }"
                                    class="h-8 w-8 p-0"
                                >
                                    <ListOrdered class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <div class="mx-1 h-6 w-px bg-border" />
                            
                            <!-- Enlaces e imágenes -->
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="insertLink"
                                    class="h-8 w-8 p-0"
                                >
                                    <Link2 class="h-4 w-4" />
                                </Button>
                                
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="insertImage"
                                    class="h-8 w-8 p-0"
                                >
                                    <ImageIcon class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <div class="mx-1 h-6 w-px bg-border" />
                            
                            <!-- Color -->
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <Palette class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuItem @click="setTextColor('#000000')">Negro</DropdownMenuItem>
                                    <DropdownMenuItem @click="setTextColor('#EF4444')">Rojo</DropdownMenuItem>
                                    <DropdownMenuItem @click="setTextColor('#3B82F6')">Azul</DropdownMenuItem>
                                    <DropdownMenuItem @click="setTextColor('#10B981')">Verde</DropdownMenuItem>
                                    <DropdownMenuItem @click="setTextColor('#F59E0B')">Naranja</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            
                            <div class="mx-1 h-6 w-px bg-border" />
                            
                            <!-- Deshacer/Rehacer -->
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editor.chain().focus().undo().run()"
                                    :disabled="!editor.can().undo()"
                                    class="h-8 w-8 p-0"
                                >
                                    <Undo class="h-4 w-4" />
                                </Button>
                                
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="editor.chain().focus().redo().run()"
                                    :disabled="!editor.can().redo()"
                                    class="h-8 w-8 p-0"
                                >
                                    <Redo class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        
                        <!-- Editor content -->
                        <EditorContent :editor="editor" class="min-h-[300px] border-x border-b" />
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
                    <div v-for="(category, key) in variableCategories" :key="key">
                        <h4 class="text-sm font-medium mb-2">{{ category.label }}</h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="variable in category.variables"
                                :key="variable.key"
                                variant="outline"
                                class="cursor-pointer hover:bg-accent"
                                @click="insertVariable(variable.key)"
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
.editor-content {
    min-height: 300px;
}

.prose :deep(p) {
    margin-bottom: 0.5rem;
}

.prose :deep(ul),
.prose :deep(ol) {
    margin: 0.5rem 0;
}
</style>