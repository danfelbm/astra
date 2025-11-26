<script setup lang="ts">
import { cn } from '@modules/Core/Resources/js/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <li data-sidebar="menu-item" :class="cn('group/menu-item relative overflow-hidden', props.class)">
        <slot />
    </li>
</template>
