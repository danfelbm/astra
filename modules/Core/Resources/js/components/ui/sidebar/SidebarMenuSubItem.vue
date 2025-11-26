<script setup lang="ts">
import { cn } from '@modules/Core/Resources/js/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <li :class="cn('min-w-0 overflow-hidden', props.class)">
        <slot />
    </li>
</template>
