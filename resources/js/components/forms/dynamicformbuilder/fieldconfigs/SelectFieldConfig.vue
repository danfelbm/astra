<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    modelValue: string[];
    disabled?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: string[]): void;
    (e: 'add-option'): void;
    (e: 'remove-option', index: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const updateOption = (index: number, value: string) => {
    const updatedOptions = [...(props.modelValue || [])];
    updatedOptions[index] = value;
    emit('update:modelValue', updatedOptions);
};
</script>

<template>
    <div class="space-y-4">
        <div class="p-4 bg-muted/50 dark:bg-muted/20 rounded-lg space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="font-medium flex items-center gap-2">
                    <Badge variant="secondary" class="text-xs">Opciones de Selección</Badge>
                </h4>
                <Button 
                    type="button" 
                    variant="outline" 
                    size="sm" 
                    @click="emit('add-option')"
                    :disabled="disabled"
                >
                    <Plus class="mr-2 h-3 w-3" />
                    Agregar Opción
                </Button>
            </div>
            
            <div class="space-y-2">
                <div
                    v-for="(option, index) in modelValue"
                    :key="index"
                    class="flex items-center space-x-2"
                >
                    <Input
                        :model-value="option"
                        @update:model-value="(value) => updateOption(index, value)"
                        :placeholder="`Opción ${index + 1}`"
                        class="flex-1"
                        :disabled="disabled"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="emit('remove-option', index)"
                        :disabled="disabled"
                        class="h-9 w-9 p-0 text-destructive hover:text-destructive"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
                
                <div v-if="!modelValue || modelValue.length === 0" class="text-center py-4">
                    <p class="text-sm text-muted-foreground">
                        No hay opciones agregadas. Haz clic en "Agregar Opción" para comenzar.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para el config de select */
</style>