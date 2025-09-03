<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { watch, onBeforeUnmount } from 'vue';
import { Bold, Italic, List, ListOrdered } from 'lucide-vue-next';
import { Button } from '@modules/Core/Resources/js/components/ui/button';

interface Props {
    modelValue: string;
    placeholder?: string;
    rows?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '',
    rows: 4
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

// Configuración del editor con funciones mínimas
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            // Desactivar funciones no necesarias para mantenerlo liviano
            blockquote: false,
            codeBlock: false,
            code: false,
            dropcursor: false,
            gapcursor: false,
            heading: false,
            horizontalRule: false,
            strike: false,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'editor-content',
            'data-placeholder': props.placeholder,
        },
    },
    onUpdate: ({ editor }) => {
        // Emitir el contenido HTML cuando cambie
        emit('update:modelValue', editor.getHTML());
    },
});

// Actualizar contenido cuando cambie el modelValue externo
watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value, false);
    }
});

// Limpiar editor al desmontar
onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div class="w-full">
        <!-- Toolbar minimalista -->
        <div v-if="editor" class="flex items-center gap-1 border border-b-0 rounded-t-md bg-muted/30 p-2">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-muted': editor.isActive('bold') }"
                class="h-8 w-8 p-0"
                :title="'Negrita'"
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
                :title="'Cursiva'"
            >
                <Italic class="h-4 w-4" />
            </Button>
            
            <div class="mx-1 h-6 w-px bg-border" />
            
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-muted': editor.isActive('bulletList') }"
                class="h-8 w-8 p-0"
                :title="'Lista con viñetas'"
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
                :title="'Lista numerada'"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>
        </div>
        
        <!-- Editor de contenido -->
        <div class="border rounded-b-md bg-background">
            <EditorContent 
                :editor="editor"
                :placeholder="placeholder"
                class="rich-text-editor"
            />
        </div>
    </div>
</template>

<style scoped>
/* Estilos para el editor */
:deep(.ProseMirror) {
    min-height: 6rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

:deep(.ProseMirror:focus) {
    outline: none;
    box-shadow: 0 0 0 3px hsl(var(--ring) / 0.2);
    border-radius: 0.375rem;
}

:deep(.ProseMirror p) {
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror p:last-child) {
    margin-bottom: 0;
}

:deep(.ProseMirror ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
}

:deep(.ProseMirror li) {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

:deep(.ProseMirror strong) {
    font-weight: bold;
}

:deep(.ProseMirror em) {
    font-style: italic;
}

/* Placeholder */
:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    color: hsl(var(--muted-foreground));
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
</style>