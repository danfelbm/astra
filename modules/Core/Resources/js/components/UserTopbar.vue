<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { MapPin, HelpCircle } from 'lucide-vue-next';

const page = usePage();

// Obtener información del usuario y sus relaciones geográficas
const user = computed(() => page.props.auth?.user);
const territorio = computed(() => user.value?.territorio);
const departamento = computed(() => user.value?.departamento);
const municipio = computed(() => user.value?.municipio);
const localidad = computed(() => user.value?.localidad);

// Construir texto de ubicación
const locationText = computed(() => {
    const parts = [];
    
    if (departamento.value?.nombre) {
        parts.push(departamento.value.nombre);
    }
    
    if (municipio.value?.nombre) {
        parts.push(municipio.value.nombre);
    }
    
    if (localidad.value?.nombre) {
        parts.push(localidad.value.nombre);
    }
    
    return parts.join(', ') || 'Ubicación no definida';
});

// Verificar si tiene ubicación completa
const hasLocation = computed(() => {
    return departamento.value?.nombre || municipio.value?.nombre || localidad.value?.nombre;
});
</script>

<template>
    <div class="border-b border-border bg-muted/20 dark:bg-muted/10">
        <div class="mx-auto flex h-10 items-center justify-between gap-2 px-2 sm:px-4 md:max-w-7xl">
            <!-- Información de ubicación (izquierda) -->
            <div class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm text-muted-foreground min-w-0 flex-1">
                <MapPin class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-muted-foreground/70 flex-shrink-0" />
                <span v-if="hasLocation" class="hidden md:inline truncate">
                    {{ locationText }}
                </span>
                <span v-if="hasLocation" class="md:hidden truncate">
                    {{ municipio?.nombre || departamento?.nombre || 'Sin ubicación' }}
                </span>
                <span v-if="!hasLocation" class="text-muted-foreground/50 truncate">
                    Sin ubicación
                </span>
                <Link
                    href="/settings/update-data"
                    class="ml-1 text-xs text-primary hover:text-primary/80 hover:underline transition-colors flex-shrink-0 hidden sm:inline"
                >
                    (actualizar)
                </Link>
            </div>

            <!-- Información de ayuda (derecha) -->
            <div class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm text-muted-foreground flex-shrink-0">
                <HelpCircle class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-muted-foreground/70" />
                <span class="hidden lg:inline">
                    pedir ayuda
                </span>
                <a
                    href="mailto:soporte@colombiahumana.co"
                    class="font-medium text-foreground hover:text-primary transition-colors underline underline-offset-4 decoration-1 hover:decoration-primary truncate max-w-[120px] sm:max-w-none"
                >
                    <span class="hidden sm:inline">soporte@colombiahumana.co</span>
                    <span class="sm:hidden">soporte</span>
                </a>
            </div>
        </div>
    </div>
</template>