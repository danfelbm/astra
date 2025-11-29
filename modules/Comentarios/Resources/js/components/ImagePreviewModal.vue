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
import { Download, ExternalLink } from 'lucide-vue-next';
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
            <DialogHeader class="p-4 pb-2 pr-10">
                <DialogTitle class="text-sm font-medium truncate">
                    {{ archivo?.nombre || 'Imagen' }}
                </DialogTitle>
                <!-- Botones de acción debajo del título -->
                <div class="flex items-center gap-2 mt-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="abrirEnNuevaPestana"
                    >
                        <ExternalLink class="h-4 w-4 mr-1" />
                        Abrir
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="descargar"
                    >
                        <Download class="h-4 w-4 mr-1" />
                        Descargar
                    </Button>
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
