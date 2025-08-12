<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LATIN_AMERICA_TIMEZONES } from '@/types/forms';

interface Props {
    modelValue: string;
    disabled?: boolean;
    label?: string;
    placeholder?: string;
    description?: string;
    required?: boolean;
    error?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    label: 'Zona Horaria',
    placeholder: 'Seleccionar zona horaria',
    description: 'Las fechas de apertura y cierre se aplicarán según esta zona horaria',
    required: false,
});

const emit = defineEmits<Emits>();

const handleValueChange = (value: string) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <div>
        <Label>
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </Label>
        <Select 
            :model-value="modelValue" 
            @update:model-value="handleValueChange"
            :disabled="disabled"
        >
            <SelectTrigger>
                <SelectValue :placeholder="placeholder" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="timezone in LATIN_AMERICA_TIMEZONES"
                    :key="timezone.value"
                    :value="timezone.value"
                >
                    {{ timezone.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <p v-if="error" class="text-sm text-destructive mt-1">
            {{ error }}
        </p>
        <p v-else-if="description" class="text-xs text-muted-foreground mt-1">
            {{ description }}
        </p>
    </div>
</template>