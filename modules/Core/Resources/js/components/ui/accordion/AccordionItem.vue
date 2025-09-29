<script setup lang="ts">
import { AccordionItem, type AccordionItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@modules/Core/Resources/js/lib/utils';

const props = defineProps<AccordionItemProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
  <AccordionItem v-bind="props" :class="cn('border-b', props.class)">
    <slot />
  </AccordionItem>
</template>