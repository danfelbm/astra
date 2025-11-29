<script setup lang="ts">
/**
 * Modal de vista previa para imágenes adjuntas en comentarios.
 * Permite ver la imagen en tamaño completo con opciones de descarga.
 */
import { computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@modules/Core/Resources/js/components/ui/dialog';
import { Button } from '@modules/Core/Resources/js/components/ui/button';
import { X, Download, ExternalLink } from 'lucide-vue-next';
import type { ArchivoAdjunto } from '../types/comentarios';

interface Props {
    open: boolean;
    archivo: ArchivoAdjunto | null;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Control del modal
const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Cerrar modal
const cerrar = () => {
    isOpen.value = false;
};

// Abrir imagen en nueva pestaña
const abrirEnNuevaPestana = () => {
    if (props.archivo?.url) {
        window.open(props.archivo.url, '_blank');
    }
};

// Descargar imagen
const descargar = () => {
    if (props.archivo?.url) {
        const link = document.createElement('a');
        link.href = props.archivo.url;
        link.download = props.archivo.nombre;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-4xl max-h-[90vh] p-0 overflow-hidden">
            <DialogHeader class="p-4 pb-0">
                <div class="flex items-center justify-between">
                    <DialogTitle class="text-sm font-medium truncate pr-4">
                        {{ archivo?.nombre || 'Imagen' }}
                    </DialogTitle>
                    <div class="flex items-center gap-1">
                        <!-- Abrir en nueva pestaña -->
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="abrirEnNuevaPestana"
                            title="Abrir en nueva pestaña"
                        >
                            <ExternalLink class="h-4 w-4" />
                        </Button>
                        <!-- Descargar -->
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="descargar"
                            title="Descargar"
                        >
                            <Download class="h-4 w-4" />
                        </Button>
                        <!-- Cerrar -->
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="cerrar"
                            title="Cerrar"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </DialogHeader>

            <!-- Contenedor de imagen -->
            <div class="flex items-center justify-center p-4 bg-muted/30 min-h-[300px] max-h-[calc(90vh-80px)] overflow-auto">
                <img
                    v-if="archivo?.url"
                    :src="archivo.url"
                    :alt="archivo.nombre"
                    class="max-w-full max-h-full object-contain rounded"
                    @error="($event.target as HTMLImageElement).src = '/images/placeholder.png'"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
