<script setup lang="ts">
import { cn } from '@modules/Core/Resources/js/lib/utils';
import type { HTMLAttributes } from 'vue';

interface SkeletonProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<SkeletonProps>();
</script>

<template>
    <div :class="cn('animate-pulse rounded-md bg-muted', props.class)" />
</template>
