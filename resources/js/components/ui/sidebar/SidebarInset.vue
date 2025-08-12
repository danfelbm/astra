<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <main
        :class="
            cn(
                'relative flex min-h-svh flex-1 flex-col bg-background',
                'sidebar-inset-fix',
                'peer-data-[variant=inset]:min-h-[calc(100svh-1rem)]',
                props.class,
            )
        "
    >
        <slot />
    </main>
</template>
