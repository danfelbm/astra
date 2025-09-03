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
    <div class="border-b border-sidebar-border/60 bg-muted/20 dark:bg-muted/10">
        <div class="mx-auto flex h-10 items-center justify-between px-4 md:max-w-7xl">
            <!-- Información de ubicación (izquierda) -->
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <MapPin class="h-4 w-4 text-muted-foreground/70" />
                <span v-if="hasLocation" class="hidden sm:inline">
                    {{ locationText }}
                </span>
                <span v-if="hasLocation" class="sm:hidden">
                    {{ municipio?.nombre || departamento?.nombre || 'Sin ubicación' }}
                </span>
                <span v-if="!hasLocation" class="text-muted-foreground/50">
                    Sin ubicación definida
                </span>
                <Link 
                    href="/settings/update-data" 
                    class="ml-1 text-xs text-primary hover:text-primary/80 hover:underline transition-colors"
                >
                    (actualizar estos datos)
                </Link>
            </div>
            
            <!-- Información de ayuda (derecha) -->
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <HelpCircle class="h-4 w-4 text-muted-foreground/70" />
                <span class="hidden md:inline">
                    pedir ayuda
                </span>
                <a 
                    href="mailto:soporte@colombiahumana.co"
                    class="font-medium text-foreground hover:text-primary transition-colors underline underline-offset-4 decoration-1 hover:decoration-primary"
                >
                    soporte@colombiahumana.co
                </a>
            </div>
        </div>
    </div>
</template>