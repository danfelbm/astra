<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Target, Users } from 'lucide-vue-next';

interface Segment {
    id: number;
    name: string;
    description?: string;
    is_dynamic: boolean;
    user_count?: number;
}

interface Props {
    segments: Segment[];
    maxVisible?: number;
    size?: 'sm' | 'md' | 'lg';
    variant?: 'default' | 'outline' | 'ghost';
}

const props = withDefaults(defineProps<Props>(), {
    maxVisible: 2,
    size: 'sm',
    variant: 'outline',
});

const visibleSegments = computed(() => {
    return props.segments.slice(0, props.maxVisible);
});

const hiddenSegments = computed(() => {
    return props.segments.slice(props.maxVisible);
});

const hasHiddenSegments = computed(() => {
    return props.segments.length > props.maxVisible;
});

const badgeSize = computed(() => {
    const sizes = {
        sm: 'text-xs px-1.5 py-0.5',
        md: 'text-sm px-2 py-1',
        lg: 'text-base px-3 py-1.5',
    };
    return sizes[props.size];
});

const getSegmentVariant = (segment: Segment): string => {
    if (segment.is_dynamic) {
        return 'default';
    }
    return 'secondary';
};
</script>

<template>
    <div v-if="segments.length > 0" class="flex items-center gap-1 flex-wrap">
        <!-- Segmentos visibles -->
        <Badge
            v-for="segment in visibleSegments"
            :key="segment.id"
            :variant="getSegmentVariant(segment)"
            :class="badgeSize"
        >
            <Target class="mr-1 h-3 w-3" />
            {{ segment.name }}
        </Badge>
        
        <!-- Indicador de segmentos ocultos -->
        <Popover v-if="hasHiddenSegments">
            <PopoverTrigger asChild>
                <Badge 
                    variant="ghost" 
                    :class="[badgeSize, 'cursor-pointer hover:bg-muted']"
                >
                    +{{ hiddenSegments.length }} más
                </Badge>
            </PopoverTrigger>
            <PopoverContent class="w-80" align="start">
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <Target class="h-4 w-4 text-muted-foreground" />
                        <h4 class="font-semibold text-sm">Segmentos Adicionales</h4>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="segment in hiddenSegments"
                            :key="segment.id"
                            class="flex items-start justify-between p-2 rounded-lg border bg-card"
                        >
                            <div class="flex-1">
                                <p class="font-medium text-sm">{{ segment.name }}</p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ segment.description || 'Sin descripción' }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <Badge 
                                        :variant="getSegmentVariant(segment)" 
                                        class="text-xs px-1.5 py-0"
                                    >
                                        {{ segment.is_dynamic ? 'Dinámico' : 'Estático' }}
                                    </Badge>
                                    <span v-if="segment.user_count" class="text-xs text-muted-foreground flex items-center gap-1">
                                        <Users class="h-3 w-3" />
                                        {{ segment.user_count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    </div>
    
    <!-- Estado vacío -->
    <div v-else class="flex items-center gap-1">
        <Badge variant="ghost" :class="badgeSize">
            <Target class="mr-1 h-3 w-3 opacity-50" />
            Sin segmentos
        </Badge>
    </div>
</template>