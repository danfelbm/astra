<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ref, computed } from 'vue';
import { Upload, FileText, GitBranch, AlertCircle } from 'lucide-vue-next';

// Props del componente
interface Props {
    mode: 'general' | 'votacion' | 'asamblea';
    votacionId?: number;
    votacionTitulo?: string;
    asambleaId?: number;
    asambleaTitulo?: string;
    redirectOnSuccess?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    redirectOnSuccess: true,
});

// Eventos
const emit = defineEmits<{
    success: [importId: number];
    cancel: [];
}>();

// Estado del formulario
const activeTab = ref('config');
const csvFile = ref<File | null>(null);
const csvAnalysis = ref<{
    headers: string[];
    sample_data: string[][];
    available_fields: Record<string, string>;
    total_rows: number;
    file_size: number;
    is_large_file: boolean;
    estimated: boolean;
} | null>(null);

const form = useForm({
    name: '',
    csv_file: null as File | null,
    import_mode: 'both',
    field_mappings: {} as Record<string, string>,
    update_fields: [] as string[],
    votacion_id: props.votacionId || null,
    asamblea_id: props.asambleaId || null,
});

const isAnalyzing = ref(false);
const canProceedToMapping = computed(() => {
    return form.name && form.csv_file && form.import_mode && csvAnalysis.value;
});

// Opciones para el modo de importación
const importModeOptions = [
    { value: 'insert', label: 'Solo añadir usuarios nuevos' },
    { value: 'update', label: 'Solo actualizar usuarios existentes' },
    { value: 'both', label: 'Añadir nuevos y actualizar existentes' },
];

// Título dinámico basado en el modo
const wizardTitle = computed(() => {
    if (props.mode === 'votacion' && props.votacionTitulo) {
        return `Importar votantes para: ${props.votacionTitulo}`;
    }
    if (props.mode === 'asamblea' && props.asambleaTitulo) {
        return `Importar participantes para: ${props.asambleaTitulo}`;
    }
    return 'Nueva Importación CSV';
});

// Descripción dinámica
const wizardDescription = computed(() => {
    if (props.mode === 'votacion') {
        return 'Los usuarios importados serán asignados automáticamente a esta votación';
    }
    if (props.mode === 'asamblea') {
        return 'Los usuarios importados serán asignados automáticamente como participantes de esta asamblea';
    }
    return 'Importa usuarios desde un archivo CSV';
});

// Manejar subida de archivo
const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        csvFile.value = file;
        form.csv_file = file;
        csvAnalysis.value = null; // Reset analysis
    }
};

// Analizar archivo CSV
const analyzeCSV = async () => {
    if (!csvFile.value || !form.name.trim()) {
        return;
    }
    
    isAnalyzing.value = true;
    
    try {
        const formData = new FormData();
        formData.append('csv_file', csvFile.value);
        
        const response = await fetch('/admin/imports/analyze', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        const data = await response.json();
        
        if (response.ok) {
            csvAnalysis.value = data;
            
            // Auto-mapear campos comunes
            const autoMappings: Record<string, string> = {};
            data.headers.forEach((header: string) => {
                const normalizedHeader = header.toLowerCase().trim();
                
                // Mapeos automáticos comunes
                if (normalizedHeader.includes('nombre') || normalizedHeader === 'name') {
                    autoMappings[header] = 'name';
                } else if (normalizedHeader.includes('email') || normalizedHeader.includes('correo')) {
                    autoMappings[header] = 'email';
                } else if (normalizedHeader.includes('documento') || normalizedHeader.includes('cedula') || normalizedHeader.includes('identification')) {
                    autoMappings[header] = 'documento_identidad';
                } else if (normalizedHeader === 'tipo_documento' || normalizedHeader.includes('tipo') && normalizedHeader.includes('doc')) {
                    autoMappings[header] = 'tipo_documento';
                } else if (normalizedHeader.includes('telefono') || normalizedHeader.includes('phone')) {
                    autoMappings[header] = 'telefono';
                } else if (normalizedHeader.includes('direccion') || normalizedHeader.includes('address')) {
                    autoMappings[header] = 'direccion';
                } else if (normalizedHeader === 'territorio' || normalizedHeader === 'territorio_id') {
                    autoMappings[header] = 'territorio_id';
                } else if (normalizedHeader === 'departamento' || normalizedHeader === 'departamento_id') {
                    autoMappings[header] = 'departamento_id';
                } else if (normalizedHeader === 'municipio' || normalizedHeader === 'municipio_id') {
                    autoMappings[header] = 'municipio_id';
                } else if (normalizedHeader === 'localidad' || normalizedHeader === 'localidad_id') {
                    autoMappings[header] = 'localidad_id';
                } else if (normalizedHeader === 'cargo' || normalizedHeader === 'cargo_id') {
                    autoMappings[header] = 'cargo_id';
                } else if (normalizedHeader === 'rol' || normalizedHeader === 'role') {
                    autoMappings[header] = 'rol';
                } else if (normalizedHeader === 'activo' || normalizedHeader === 'active') {
                    autoMappings[header] = 'activo';
                } else if (normalizedHeader === 'es_miembro' || normalizedHeader === 'miembro') {
                    autoMappings[header] = 'es_miembro';
                }
            });
            
            form.field_mappings = autoMappings;
            
            // Auto-rellenar nombre si está en modo votación
            if (props.mode === 'votacion' && !form.name) {
                form.name = `Importación votantes - ${new Date().toLocaleDateString()}`;
            }
            
            // Pasar al tab de mapeo
            activeTab.value = 'mapping';
        } else {
            alert(data.error || 'Error al analizar archivo');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al analizar archivo');
    } finally {
        isAnalyzing.value = false;
    }
};

// Manejar cambio de mapeo
const updateFieldMapping = (csvField: string, modelField: any) => {
    const fieldValue = String(modelField);
    if (!fieldValue || fieldValue === 'none') {
        delete form.field_mappings[csvField];
    } else {
        form.field_mappings[csvField] = fieldValue;
    }
};

// Manejar cambio de campos a actualizar
const toggleUpdateField = (field: string, checked: boolean) => {
    if (checked) {
        if (!form.update_fields.includes(field)) {
            form.update_fields.push(field);
        }
    } else {
        const index = form.update_fields.indexOf(field);
        if (index > -1) {
            form.update_fields.splice(index, 1);
        }
    }
};

// Crear importación
const createImport = () => {
    // Determinar la ruta según el modo
    let url = '/admin/imports';
    if (props.mode === 'votacion') {
        url = `/admin/votaciones/${props.votacionId}/imports/store`;
    } else if (props.mode === 'asamblea') {
        url = `/admin/asambleas/${props.asambleaId}/imports/store`;
    }
    
    console.log('Creando importación:', {
        url,
        mode: props.mode,
        name: form.name,
        import_mode: form.import_mode,
        field_mappings: form.field_mappings,
        update_fields: form.update_fields,
        csv_file: form.csv_file,
        asamblea_id: props.asambleaId,
        votacion_id: props.votacionId
    });
    
    form.post(url, {
        preserveScroll: true,
        forceFormData: true, // Forzar uso de FormData para archivos
        onSuccess: (page: any) => {
            console.log('Respuesta exitosa:', page);
            // Extraer el ID de la importación de la respuesta
            const importId = page.props.import?.id || page.props.flash?.import_id;
            
            if (importId) {
                emit('success', importId);
                
                // Redirigir si está configurado
                if (props.redirectOnSuccess) {
                    router.get(`/admin/imports/${importId}`);
                }
            } else {
                console.error('No se recibió ID de importación en la respuesta');
            }
        },
        onError: (errors) => {
            console.error('Error al crear importación:', errors);
        },
        onFinish: () => {
            console.log('Petición finalizada');
        }
    });
};

// Verificar si se puede crear la importación
const canCreateImport = computed(() => {
    // Obtener todos los valores mapeados (los campos del modelo)
    const mappedFields = Object.values(form.field_mappings);
    
    // Verificar que al menos name y email estén mapeados
    const hasName = mappedFields.includes('name');
    const hasEmail = mappedFields.includes('email');
    
    return hasName && hasEmail;
});

// Formatear tamaño de archivo
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

// Obtener batch size configurado (estimación)
const getBatchSize = (): number => {
    // Valor por defecto o estimado basado en configuración
    return 300;
};

// Verificar si es un archivo grande (>5MB)
const isLargeFile = (bytes: number): boolean => {
    const fiveMB = 5 * 1024 * 1024; // 5MB
    return bytes > fiveMB;
};
</script>

<template>
    <Card>
        <CardHeader v-if="mode === 'general'">
            <CardTitle>{{ wizardTitle }}</CardTitle>
        </CardHeader>
        <CardContent class="p-6">
            <div v-if="mode === 'votacion'" class="mb-4">
                <p class="text-sm text-muted-foreground">
                    {{ wizardDescription }}
                </p>
            </div>

            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="config">
                        <span class="flex items-center gap-2">
                            <Upload class="h-4 w-4" />
                            Configuración
                        </span>
                    </TabsTrigger>
                    <TabsTrigger value="mapping" :disabled="!canProceedToMapping">
                        <span class="flex items-center gap-2">
                            <GitBranch class="h-4 w-4" />
                            Mapeo de Campos
                        </span>
                    </TabsTrigger>
                </TabsList>

                <!-- Tab 1: Configuración Básica -->
                <TabsContent value="config" class="space-y-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Nombre de la Importación</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                :placeholder="mode === 'votacion' ? 'Ej: Votantes primera vuelta' : 'Ej: Importación militantes enero 2025'"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-500">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="csv_file">Archivo CSV</Label>
                            <Input
                                id="csv_file"
                                type="file"
                                accept=".csv"
                                @change="handleFileUpload"
                                :class="{ 'border-red-500': form.errors.csv_file }"
                            />
                            <p v-if="form.errors.csv_file" class="text-sm text-red-500">
                                {{ form.errors.csv_file }}
                            </p>
                            
                            <!-- Información del archivo seleccionado -->
                            <div v-if="csvFile" class="mt-2">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <FileText class="h-4 w-4" />
                                    <span>{{ csvFile.name }}</span>
                                    <span>·</span>
                                    <span>{{ formatFileSize(csvFile.size) }}</span>
                                </div>
                                
                                <!-- Advertencia para archivos grandes antes del análisis -->
                                <div v-if="isLargeFile(csvFile.size)" class="mt-2 p-3 bg-orange-50 border border-orange-200 rounded">
                                    <div class="flex items-start gap-2">
                                        <AlertCircle class="h-4 w-4 text-orange-600 mt-0.5 flex-shrink-0" />
                                        <div class="text-sm text-orange-700">
                                            <strong>Archivo grande detectado ({{ formatFileSize(csvFile.size) }})</strong>
                                            <p class="mt-1">Este archivo tardará varios minutos en procesar. Se recomienda:</p>
                                            <ul class="mt-1 space-y-0.5 list-disc list-inside">
                                                <li>Tener una conexión estable a internet</li>
                                                <li>No cerrar la pestaña durante el proceso</li>
                                                <li>Verificar que el mapeo sea correcto antes de iniciar</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-sm text-muted-foreground">
                                El archivo debe contener al menos las columnas: nombre y email (máximo 50MB)
                            </p>
                            <p class="text-sm text-blue-600">
                                <a href="/ejemplo-usuarios.csv" download class="underline">
                                    📥 Descargar plantilla CSV de ejemplo
                                </a>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="import_mode">Modo de Importación</Label>
                            <Select v-model="form.import_mode">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecciona el modo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in importModeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-sm text-muted-foreground">
                                Define cómo manejar usuarios que ya existen en el sistema
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button 
                                v-if="mode === 'votacion' || mode === 'asamblea'"
                                variant="outline"
                                @click="emit('cancel')"
                            >
                                Cancelar
                            </Button>
                            <Button 
                                @click="analyzeCSV"
                                :disabled="!form.name || !csvFile || isAnalyzing"
                            >
                                <FileText class="mr-2 h-4 w-4" />
                                {{ isAnalyzing ? 'Analizando...' : 'Analizar CSV' }}
                            </Button>
                        </div>
                    </div>
                </TabsContent>

                <!-- Tab 2: Mapeo de Campos -->
                <TabsContent value="mapping" class="space-y-6">
                    <div v-if="csvAnalysis">
                        <!-- Información del archivo -->
                        <Alert class="mb-4" :class="{'border-orange-200 bg-orange-50': csvAnalysis.is_large_file}">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                <div class="space-y-2">
                                    <div>
                                        <span v-if="csvAnalysis.estimated">
                                            Se encontraron aproximadamente <strong>~{{ csvAnalysis.total_rows.toLocaleString() }}</strong> registros
                                            <span class="text-sm text-muted-foreground">(estimación)</span>
                                        </span>
                                        <span v-else>
                                            Se encontraron <strong>{{ csvAnalysis.total_rows.toLocaleString() }}</strong> registros
                                        </span>
                                        · Tamaño: <strong>{{ formatFileSize(csvAnalysis.file_size) }}</strong>
                                    </div>
                                    
                                    <!-- Advertencia para archivos grandes -->
                                    <div v-if="csvAnalysis.is_large_file" class="p-3 bg-orange-100 border border-orange-200 rounded">
                                        <div class="flex items-start gap-2">
                                            <AlertCircle class="h-4 w-4 text-orange-600 mt-0.5 flex-shrink-0" />
                                            <div class="text-sm">
                                                <strong class="text-orange-800">Archivo grande detectado</strong>
                                                <ul class="mt-1 text-orange-700 space-y-1">
                                                    <li>• El procesamiento tomará varios minutos</li>
                                                    <li>• Se procesará en batches de {{ getBatchSize() }} registros</li>
                                                    <li>• Puedes seguir el progreso en tiempo real</li>
                                                    <li>• No cierres esta pestaña durante el proceso</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-sm">
                                        Los campos <strong>Nombre</strong> y <strong>Email</strong> son obligatorios.
                                        <span v-if="mode === 'votacion'" class="block mt-1">
                                            Los usuarios serán asignados automáticamente a la votación.
                                        </span>
                                        <span v-if="mode === 'asamblea'" class="block mt-1">
                                            Los usuarios serán asignados automáticamente como participantes de la asamblea.
                                        </span>
                                    </div>
                                </div>
                            </AlertDescription>
                        </Alert>

                        <!-- Tabla de Mapeo -->
                        <div class="border rounded-lg overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Columna del CSV</TableHead>
                                        <TableHead>Mapear a Campo</TableHead>
                                        <TableHead v-if="form.import_mode !== 'insert'">Actualizar</TableHead>
                                        <TableHead>Vista Previa</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="header in csvAnalysis.headers" :key="header">
                                        <TableCell class="font-medium">
                                            {{ header }}
                                        </TableCell>
                                        <TableCell>
                                            <Select 
                                                :model-value="form.field_mappings[header] || 'none'"
                                                @update:model-value="(value) => updateFieldMapping(header, value)"
                                            >
                                                <SelectTrigger class="w-full">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">-- No mapear --</SelectItem>
                                                    <SelectItem
                                                        v-for="(label, field) in csvAnalysis.available_fields"
                                                        :key="field"
                                                        :value="field"
                                                    >
                                                        {{ label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell v-if="form.import_mode !== 'insert'">
                                            <div v-if="form.field_mappings[header] && form.field_mappings[header] !== 'none'">
                                                <!-- Email y documento_identidad nunca se actualizan automáticamente -->
                                                <div v-if="form.field_mappings[header] === 'email' || form.field_mappings[header] === 'documento_identidad'" 
                                                     class="text-sm text-muted-foreground italic">
                                                    Resolución manual
                                                </div>
                                                <Checkbox
                                                    v-else
                                                    :checked="form.update_fields.includes(form.field_mappings[header])"
                                                    @update:checked="(checked) => toggleUpdateField(form.field_mappings[header], checked)"
                                                />
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm text-muted-foreground max-w-32 truncate">
                                                {{ csvAnalysis.sample_data[0]?.[csvAnalysis.headers.indexOf(header)] || '-' }}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between">
                            <Button variant="outline" @click="activeTab = 'config'">
                                Volver a Configuración
                            </Button>
                            <div class="flex gap-2">
                                <Button 
                                    v-if="mode === 'votacion' || mode === 'asamblea'"
                                    variant="outline"
                                    @click="emit('cancel')"
                                >
                                    Cancelar
                                </Button>
                                <Button 
                                    @click="createImport"
                                    :disabled="!canCreateImport || form.processing"
                                >
                                    {{ form.processing ? 'Procesando...' : 'Iniciar Importación' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </CardContent>
    </Card>
</template>