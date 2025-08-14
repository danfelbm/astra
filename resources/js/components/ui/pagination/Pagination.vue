<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
}

const props = defineProps<Props>();

// Función para traducir labels de paginación
const translatePaginationLabel = (label: string): string => {
    const translations: Record<string, string> = {
        'pagination.previous': '&laquo; Anterior',
        'pagination.next': 'Siguiente &raquo;',
        '&laquo; Previous': '&laquo; Anterior',
        'Next &raquo;': 'Siguiente &raquo;',
        'Previous': 'Anterior',
        'Next': 'Siguiente',
        '...': '...'
    };
    
    return translations[label] || label;
};

// Navegar a una página
const navigateToPage = (url: string | null) => {
    if (url) {
        router.get(url);
    }
};
</script>

<template>
    <div v-if="links && links.length > 3" class="mt-6 flex justify-center">
        <div class="flex items-center gap-2">
            <template v-for="link in links" :key="link.label">
                <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    @click="navigateToPage(link.url)"
                    v-html="translatePaginationLabel(link.label)"
                />
                <span
                    v-else
                    class="px-3 py-1 text-sm text-muted-foreground cursor-not-allowed"
                    v-html="translatePaginationLabel(link.label)"
                />
            </template>
        </div>
    </div>
</template>