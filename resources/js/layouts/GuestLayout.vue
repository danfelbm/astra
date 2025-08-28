<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import 'vue-sonner/style.css';

interface Props {
    title?: string;
    description?: string;
}

withDefaults(defineProps<Props>(), {
    title: '',
    description: '',
});
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Navbar público -->
        <header class="border-b border-border bg-card">
            <div class="container mx-auto px-4">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center space-x-4">
                        <AppLogo class="h-8 w-auto" />
                        <div class="hidden sm:block">
                            <h1 class="text-lg font-semibold text-foreground">
                                Sistema de Votaciones
                            </h1>
                        </div>
                    </div>

                    <!-- Navegación pública -->
                    <nav class="hidden md:flex items-center space-x-6">
                        <Link 
                            :href="route('registro.confirmacion.index')"
                            class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Confirmar Registro
                        </Link>
                        <Link 
                            :href="route('verificar-token.index')"
                            class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Verificar Token
                        </Link>
                        <Link 
                            :href="route('postulaciones.publicas')"
                            class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Postulaciones
                        </Link>
                        <Link 
                            :href="route('frontend.asambleas.consulta-participantes')"
                            class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Consultar Participantes
                        </Link>
                    </nav>

                    <!-- Botón de login -->
                    <div class="flex items-center space-x-4">
                        <Link 
                            :href="route('login')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 transition-colors"
                        >
                            Iniciar Sesión
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1">
            <div class="container mx-auto px-4 py-8">
                <!-- Header de página si se proporciona título -->
                <div v-if="title || description" class="mb-8">
                    <h2 v-if="title" class="text-2xl font-bold text-foreground mb-2">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                
                <!-- Contenido de la página -->
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-border bg-muted/50">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-sm text-muted-foreground mb-4 md:mb-0">
                        © {{ new Date().getFullYear() }} Sistema de Votaciones. Todos los derechos reservados.
                    </div>
                    <nav class="flex items-center space-x-4">
                        <a 
                            href="#"
                            class="text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Ayuda
                        </a>
                        <a 
                            href="#"
                            class="text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Contacto
                        </a>
                        <a 
                            href="#"
                            class="text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Términos
                        </a>
                    </nav>
                </div>
            </div>
        </footer>

        <!-- Notificaciones toast -->
        <Toaster />
    </div>
</template>