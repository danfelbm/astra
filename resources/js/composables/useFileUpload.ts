import { ref } from 'vue';
import axios from 'axios';

interface UploadedFile {
    id: string;
    name: string;
    size: number;
    path: string;
    url: string;
    mime_type: string;
    uploaded_at: string;
}

interface UploadProgress {
    [key: string]: number;
}

interface UploadOptions {
    module: 'votaciones' | 'convocatorias' | 'postulaciones' | 'candidaturas';
    fieldId: string;
    onProgress?: (fileName: string, progress: number) => void;
    onSuccess?: (files: UploadedFile[]) => void;
    onError?: (error: any) => void;
}

export function useFileUpload() {
    const isUploading = ref(false);
    const uploadProgress = ref<UploadProgress>({});
    const uploadErrors = ref<Map<string, string>>(new Map());
    
    /**
     * Sube uno o más archivos al servidor
     */
    const uploadFiles = async (files: File[], options: UploadOptions): Promise<UploadedFile[]> => {
        isUploading.value = true;
        uploadErrors.value.clear();
        
        const formData = new FormData();
        
        // Agregar archivos al FormData
        files.forEach(file => {
            formData.append('files[]', file);
        });
        
        // Agregar metadata
        formData.append('module', options.module);
        formData.append('field_id', options.fieldId);
        
        try {
            const response = await axios.post('/api/files/upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        
                        // Actualizar progreso para cada archivo
                        files.forEach(file => {
                            uploadProgress.value[file.name] = percentCompleted;
                            if (options.onProgress) {
                                options.onProgress(file.name, percentCompleted);
                            }
                        });
                    }
                },
            });
            
            if (response.data.success) {
                if (options.onSuccess) {
                    options.onSuccess(response.data.files);
                }
                return response.data.files;
            } else {
                throw new Error(response.data.message || 'Error al subir archivos');
            }
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || error.message || 'Error desconocido al subir archivos';
            
            files.forEach(file => {
                uploadErrors.value.set(file.name, errorMessage);
            });
            
            if (options.onError) {
                options.onError(error);
            }
            
            throw error;
        } finally {
            isUploading.value = false;
            // Limpiar progreso después de un tiempo
            setTimeout(() => {
                uploadProgress.value = {};
            }, 2000);
        }
    };
    
    /**
     * Elimina un archivo del servidor
     */
    const deleteFile = async (path: string): Promise<boolean> => {
        try {
            const response = await axios.delete('/api/files/delete', {
                data: { path }
            });
            
            return response.data.success;
        } catch (error) {
            console.error('Error al eliminar archivo:', error);
            return false;
        }
    };
    
    /**
     * Descarga un archivo del servidor
     */
    const downloadFile = async (path: string, fileName?: string): Promise<void> => {
        try {
            const response = await axios.get('/api/files/download', {
                params: { path },
                responseType: 'blob',
            });
            
            // Crear un enlace temporal para descargar el archivo
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', fileName || path.split('/').pop() || 'archivo');
            document.body.appendChild(link);
            link.click();
            
            // Limpiar
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Error al descargar archivo:', error);
            throw error;
        }
    };
    
    /**
     * Obtiene información de un archivo
     */
    const getFileInfo = async (path: string): Promise<any> => {
        try {
            const response = await axios.get('/api/files/info', {
                params: { path }
            });
            
            if (response.data.success) {
                return response.data.file;
            }
            
            return null;
        } catch (error) {
            console.error('Error al obtener información del archivo:', error);
            return null;
        }
    };
    
    /**
     * Valida el tipo de archivo
     */
    const validateFileType = (file: File, acceptedTypes: string): boolean => {
        const acceptedExtensions = acceptedTypes.split(',').map(ext => ext.trim().toLowerCase());
        const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
        return acceptedExtensions.some(ext => ext === fileExtension);
    };
    
    /**
     * Valida el tamaño del archivo
     */
    const validateFileSize = (file: File, maxSizeMB: number): boolean => {
        const maxSizeInBytes = maxSizeMB * 1024 * 1024;
        return file.size <= maxSizeInBytes;
    };
    
    /**
     * Formatea el tamaño del archivo
     */
    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };
    
    return {
        isUploading,
        uploadProgress,
        uploadErrors,
        uploadFiles,
        deleteFile,
        downloadFile,
        getFileInfo,
        validateFileType,
        validateFileSize,
        formatFileSize,
    };
}