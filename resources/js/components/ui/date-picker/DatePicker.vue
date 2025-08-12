<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    modelValue?: Date | string | null;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
}

interface Emits {
    (e: 'update:modelValue', value: Date | null): void;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Seleccionar fecha',
});

const emit = defineEmits<Emits>();

const selectedDate = computed({
    get: () => {
        if (!props.modelValue) return null;
        if (props.modelValue instanceof Date) return props.modelValue;
        return new Date(props.modelValue);
    },
    set: (value: Date | null) => {
        emit('update:modelValue', value);
    },
});

const displayValue = computed(() => {
    if (!selectedDate.value) return props.placeholder;
    return format(selectedDate.value, 'PPP', { locale: es });
});
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="cn(
                    'w-full justify-start text-left font-normal',
                    !selectedDate && 'text-muted-foreground',
                    props.class,
                )"
                :disabled="disabled"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ displayValue }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar 
                v-model="selectedDate" 
                initial-focus
                :locale="es"
            />
        </PopoverContent>
    </Popover>
</template>