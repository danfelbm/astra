<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { 
    Bold, Italic, Strikethrough, Code, 
    Variable, Eye, Smartphone, Info 
} from 'lucide-vue-next';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@modules/Core/Resources/js/components/ui/card';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { Textarea } from '@modules/Core/Resources/js/components/ui/textarea';
import { Alert, AlertDescription } from '@modules/Core/Resources/js/components/ui/alert';
import { useTemplateVariables } from '../composables/useTemplateVariables';

interface Props {
    modelValue: string;
    usaFormato?: boolean;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    usaFormato: true,
    placeholder: 'Escribe tu mensaje de WhatsApp...'
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:usaFormato': [value: boolean];
    'update:variables': [value: string[]];
}>();

const { 
    variableCategories,
    extractUsedVariables,
    replaceWithExamples,
    estimateLength
} = useTemplateVariables();

const contenido = ref(props.modelValue);
const previewMode = ref(false);
const textareaRef = ref<any>(null); // Referencia al componente Textarea Vue
const usaFormatoLocal = ref(props.usaFormato);

// Aplicar formato WhatsApp
const applyFormat = (format: string) => {
    if (!textareaRef.value) return;
    
    // Acceder al elemento textarea HTML real dentro del componente Vue
    const textareaElement = textareaRef.value.$el as HTMLTextAreaElement;
    if (!textareaElement) return;
    
    const start = textareaElement.selectionStart ?? 0;
    const end = textareaElement.selectionEnd ?? 0;
    const text = contenido.value || '';
    
    const selectedText = text.substring(start, end);
    if (!selectedText) return;
    
    let formattedText = '';
    switch (format) {
        case 'bold':
            formattedText = `*${selectedText}*`;
            break;
        case 'italic':
            formattedText = `_${selectedText}_`;
            break;
        case 'strikethrough':
            formattedText = `~${selectedText}~`;
            break;
        case 'monospace':
            formattedText = `\`\`\`${selectedText}\`\`\``;
            break;
    }
    
    const before = text.substring(0, start);
    const after = text.substring(end);
    
    contenido.value = before + formattedText + after;
    
    // Reposicionar cursor
    setTimeout(() => {
        if (textareaElement) {
            textareaElement.selectionStart = start;
            textareaElement.selectionEnd = start + formattedText.length;
            textareaElement.focus();
        }
    }, 0);
};

// Insertar variable
const insertVariable = (variable: string) => {
    if (!textareaRef.value) return;
    
    // Acceder al elemento textarea HTML real dentro del componente Vue
    const textareaElement = textareaRef.value.$el as HTMLTextAreaElement;
    if (!textareaElement) return;
    
    const start = textareaElement.selectionStart ?? 0;
    const text = contenido.value || '';
    
    const before = text.substring(0, start);
    const after = text.substring(start);
    
    contenido.value = before + variable + after;
    
    setTimeout(() => {
        if (textareaElement) {
            const newPos = start + variable.length;
            textareaElement.selectionStart = newPos;
            textareaElement.selectionEnd = newPos;
            textareaElement.focus();
        }
    }, 0);
};

// Preview con formato WhatsApp
const previewContent = computed(() => {
    let content = replaceWithExamples(contenido.value);
    
    if (usaFormatoLocal.value) {
        // Aplicar formato WhatsApp
        content = content
            .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
            .replace(/_(.*?)_/g, '<em>$1</em>')
            .replace(/~(.*?)~/g, '<del>$1</del>')
            .replace(/```(.*?)```/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }
    
    return content;
});

// Estadísticas
const stats = computed(() => {
    const estimation = estimateLength(contenido.value);
    const variables = extractUsedVariables(contenido.value);
    
    return {
        charactersMin: estimation.min,
        charactersMax: estimation.max,
        charactersAvg: estimation.avg,
        variables: variables.length,
        // WhatsApp tiene límite de 4096 caracteres
        warningMax: estimation.max > 4096,
        warningAvg: estimation.avg > 4096,
    };
});

// Watch para emitir cambios
watch(contenido, (value) => {
    emit('update:modelValue', value);
    emit('update:variables', extractUsedVariables(value));
});

watch(usaFormatoLocal, (value) => {
    emit('update:usaFormato', value);
});

// Watch para prop changes
watch(() => props.modelValue, (value) => {
    if (value !== contenido.value) {
        contenido.value = value;
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Editor principal -->
        <Card>
            <CardHeader>
                <div class="flex justify-between items-center">
                    <CardTitle>Mensaje de WhatsApp</CardTitle>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                v-model="usaFormatoLocal"
                                class="rounded"
                            />
                            <span>Usar formato WhatsApp</span>
                        </label>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click.prevent.stop="previewMode = !previewMode"
                        >
                            <Eye class="w-4 h-4 mr-2" />
                            {{ previewMode ? 'Editar' : 'Vista previa' }}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <!-- Toolbar de formato -->
                    <div v-if="usaFormatoLocal && !previewMode" class="flex items-center gap-2 p-2 border rounded-md bg-muted/30">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click.prevent.stop="applyFormat('bold')"
                            class="h-8 px-2"
                        >
                            <Bold class="h-4 w-4 mr-1" />
                            *texto*
                        </Button>
                        
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click.prevent.stop="applyFormat('italic')"
                            class="h-8 px-2"
                        >
                            <Italic class="h-4 w-4 mr-1" />
                            _texto_
                        </Button>
                        
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click.prevent.stop="applyFormat('strikethrough')"
                            class="h-8 px-2"
                        >
                            <Strikethrough class="h-4 w-4 mr-1" />
                            ~texto~
                        </Button>
                        
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click.prevent.stop="applyFormat('monospace')"
                            class="h-8 px-2"
                        >
                            <Code class="h-4 w-4 mr-1" />
                            ```texto```
                        </Button>
                    </div>
                    
                    <!-- Editor o Preview -->
                    <div v-if="!previewMode">
                        <Textarea
                            ref="textareaRef"
                            v-model="contenido"
                            :placeholder="placeholder"
                            rows="10"
                            class="w-full font-mono"
                        />
                    </div>
                    
                    <div v-else class="min-h-[240px] p-4 border rounded-md bg-gray-50">
                        <!-- Simulación de WhatsApp -->
                        <div class="max-w-sm mx-auto">
                            <div class="bg-white rounded-lg shadow p-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <Smartphone class="w-4 h-4 text-green-600" />
                                    <span class="text-sm font-medium">Vista previa WhatsApp</span>
                                </div>
                                <div 
                                    class="text-sm whitespace-pre-wrap" 
                                    v-html="previewContent"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Panel de variables -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Variables Disponibles</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div v-for="(category, key) in variableCategories" :key="key">
                        <h4 class="text-sm font-medium mb-2">{{ category.label }}</h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="variable in category.variables"
                                :key="variable.key"
                                variant="outline"
                                class="cursor-pointer hover:bg-accent"
                                @click.prevent.stop="insertVariable(variable.key)"
                            >
                                <Variable class="w-3 h-3 mr-1" />
                                {{ variable.key }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Estadísticas y advertencias -->
        <div class="space-y-2">
            <Alert v-if="stats.warningAvg" variant="warning">
                <Info class="h-4 w-4" />
                <AlertDescription>
                    El mensaje podría exceder el límite de 4096 caracteres de WhatsApp
                    (promedio estimado: {{ stats.charactersAvg }} caracteres)
                </AlertDescription>
            </Alert>
            
            <Card>
                <CardContent class="pt-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-muted-foreground">Variables:</span>
                            <span class="ml-2 font-medium">{{ stats.variables }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Mín:</span>
                            <span class="ml-2 font-medium">{{ stats.charactersMin }} car.</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Máx:</span>
                            <span 
                                class="ml-2 font-medium"
                                :class="{ 'text-red-600': stats.warningMax }"
                            >
                                {{ stats.charactersMax }} car.
                            </span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Promedio:</span>
                            <span 
                                class="ml-2 font-medium"
                                :class="{ 'text-yellow-600': stats.warningAvg }"
                            >
                                {{ stats.charactersAvg }} car.
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>