<script setup lang="ts">
import type { CollapsibleRootEmits, CollapsibleRootProps } from 'radix-vue';
import { CollapsibleRoot, useForwardPropsEmits } from 'radix-vue';

const props = defineProps<CollapsibleRootProps>();
const emits = defineEmits<CollapsibleRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <CollapsibleRoot v-slot="{ open }" v-bind="forwarded" class="min-w-0 overflow-hidden w-full">
        <slot :open="open" />
    </CollapsibleRoot>
</template>
