<script setup lang="ts">
/**
 * Componente para mostrar archivos adjuntos de un comentario.
 * - Imágenes: click abre en modal
 * - Otros archivos: click abre en nueva pestaña
 */
import { ref } from 'vue';
import { Badge } from '@modules/Core/Resources/js/components/ui/badge';
import { FileText, Image, FileSpreadsheet, Download } from 'lucide-vue-next';
import ImagePreviewModal from './ImagePreviewModal.vue';
import type { ArchivoAdjunto } from '../types/comentarios';

interface Props {
    archivos: ArchivoAdjunto[];
}

defineProps<Props>();

// Estado del modal de imagen
const showImageModal = ref(false);
const archivoSeleccionado = ref<ArchivoAdjunto | null>(null);

// Abrir imagen en modal
const abrirImagen = (archivo: ArchivoAdjunto) => {
    archivoSeleccionado.value = archivo;
    showImageModal.value = true;
};

// Abrir archivo en nueva pestaña
const abrirArchivo = (archivo: ArchivoAdjunto) => {
    window.open(archivo.url, '_blank');
};

// Handler de click según tipo
const handleClick = (archivo: ArchivoAdjunto) => {
    if (archivo.es_imagen) {
        abrirImagen(archivo);
    } else {
        abrirArchivo(archivo);
    }
};

// Obtener icono según extensión
const getIcono = (extension: string) => {
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        return Image;
    }
    if (['xls', 'xlsx'].includes(extension)) {
        return FileSpreadsheet;
    }
    return FileText;
};

// Obtener color del badge según extensión
const getBadgeClass = (extension: string): string => {
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
    }
    if (['pdf'].includes(extension)) {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    }
    if (['doc', 'docx'].includes(extension)) {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    }
    if (['xls', 'xlsx'].includes(extension)) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
};

// Descargar archivo
const descargar = (archivo: ArchivoAdjunto, event: Event) => {
    event.stopPropagation();
    const link = document.createElement('a');
    link.href = archivo.url;
    link.download = archivo.nombre;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <div v-if="archivos && archivos.length > 0" class="mt-2">
        <!-- Grid de archivos -->
        <div class="flex flex-wrap gap-2">
            <div
                v-for="(archivo, index) in archivos"
                :key="index"
                class="group relative cursor-pointer"
                @click="handleClick(archivo)"
            >
                <!-- Thumbnail para imágenes -->
                <template v-if="archivo.es_imagen">
                    <div class="relative w-20 h-20 rounded-md overflow-hidden border bg-muted/30 hover:ring-2 hover:ring-primary transition-all">
                        <img
                            :src="archivo.url"
                            :alt="archivo.nombre"
                            class="w-full h-full object-cover"
                            @error="($event.target as HTMLImageElement).style.display = 'none'"
                        />
                        <!-- Overlay con icono de descarga -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <Download
                                class="h-5 w-5 text-white hover:scale-110 transition-transform"
                                @click="descargar(archivo, $event)"
                            />
                        </div>
                    </div>
                </template>

                <!-- Card para otros archivos -->
                <template v-else>
                    <div class="flex items-center gap-2 px-3 py-2 rounded-md border bg-muted/30 hover:bg-muted/50 transition-colors max-w-[200px]">
                        <component
                            :is="getIcono(archivo.extension)"
                            class="h-5 w-5 flex-shrink-0 text-muted-foreground"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate">{{ archivo.nombre }}</p>
                            <Badge :class="getBadgeClass(archivo.extension)" class="text-[10px] px-1">
                                {{ archivo.extension.toUpperCase() }}
                            </Badge>
                        </div>
                        <!-- Botón descargar -->
                        <Download
                            class="h-4 w-4 text-muted-foreground hover:text-foreground transition-colors flex-shrink-0"
                            @click="descargar(archivo, $event)"
                        />
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal de imagen -->
        <ImagePreviewModal
            v-model:open="showImageModal"
            :archivo="archivoSeleccionado"
        />
    </div>
</template>
