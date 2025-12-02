<script setup lang="ts">
/**
 * ResponsiveTabs - Tabs que en móvil se convierten en Select dropdown
 *
 * En desktop (sm: y superiores) muestra TabsList con TabsTriggers.
 * En móvil muestra un Select dropdown sofisticado.
 */
import { computed } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@modules/Core/Resources/js/components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@modules/Core/Resources/js/components/ui/select';
import { cn } from '@modules/Core/Resources/js/lib/utils';

// Tipo para definir un tab
export interface TabItem {
    value: string;
    label: string;
    icon?: any; // Componente de icono
    badge?: string | number;
    disabled?: boolean;
}

interface Props {
    modelValue: string;
    tabs: TabItem[];
    class?: string;
    listClass?: string;
    selectClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    listClass: '',
    selectClass: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

// Tab activo actual
const activeTab = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value)
});

// Obtener el tab actual para mostrar su label en el Select
const currentTab = computed(() =>
    props.tabs.find(t => t.value === activeTab.value)
);
</script>

<template>
    <Tabs v-model="activeTab" :class="cn('flex flex-col', props.class)">
        <!-- Select para móvil -->
        <div class="sm:hidden mb-3">
            <Select v-model="activeTab">
                <SelectTrigger :class="cn('w-full', props.selectClass)">
                    <SelectValue>
                        <div class="flex items-center gap-2">
                            <component
                                v-if="currentTab?.icon"
                                :is="currentTab.icon"
                                class="h-4 w-4"
                            />
                            <span>{{ currentTab?.label }}</span>
                            <span
                                v-if="currentTab?.badge"
                                class="ml-auto bg-muted text-muted-foreground rounded-full px-2 py-0.5 text-xs"
                            >
                                {{ currentTab.badge }}
                            </span>
                        </div>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="tab in tabs"
                        :key="tab.value"
                        :value="tab.value"
                        :disabled="tab.disabled"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                v-if="tab.icon"
                                :is="tab.icon"
                                class="h-4 w-4"
                            />
                            <span>{{ tab.label }}</span>
                            <span
                                v-if="tab.badge"
                                class="ml-auto bg-secondary text-secondary-foreground rounded-full px-2 py-0.5 text-xs"
                            >
                                {{ tab.badge }}
                            </span>
                        </div>
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- TabsList para desktop -->
        <TabsList :class="cn('hidden sm:inline-flex justify-start flex-wrap', props.listClass)">
            <TabsTrigger
                v-for="tab in tabs"
                :key="tab.value"
                :value="tab.value"
                :disabled="tab.disabled"
            >
                <component
                    v-if="tab.icon"
                    :is="tab.icon"
                    class="h-4 w-4 mr-1.5"
                />
                {{ tab.label }}
                <span
                    v-if="tab.badge"
                    class="ml-1.5 bg-secondary text-secondary-foreground rounded-full h-5 px-1.5 text-xs inline-flex items-center justify-center"
                >
                    {{ tab.badge }}
                </span>
            </TabsTrigger>
        </TabsList>

        <!-- Slot para el contenido de los tabs -->
        <slot />
    </Tabs>
</template>
