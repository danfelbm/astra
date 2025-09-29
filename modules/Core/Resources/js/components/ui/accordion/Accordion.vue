<script setup lang="ts">
import { AccordionRoot, type AccordionRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@modules/Core/Resources/js/lib/utils';

const props = defineProps<AccordionRootProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
  <AccordionRoot v-bind="props" :class="cn('w-full', props.class)">
    <slot />
  </AccordionRoot>
</template>