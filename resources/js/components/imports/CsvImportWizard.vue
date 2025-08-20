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
    mode: 'general' | 'votacion';
    votacionId?: number;
    votacionTitulo?: string;
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
} | null>(null);

const form = useForm({
    name: '',
    csv_file: null as File | null,
    import_mode: 'both',
    field_mappings: {} as Record<string, string>,
    update_fields: [] as string[],
    votacion_id: props.votacionId || null,
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
    return 'Nueva Importación CSV';
});

// Descripción dinámica
const wizardDescription = computed(() => {
    if (props.mode === 'votacion') {
        return 'Los usuarios importados serán asignados automáticamente a esta votación';
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
    const url = props.mode === 'votacion' 
        ? `/admin/votaciones/${props.votacionId}/imports/store`
        : '/admin/imports';
    
    form.post(url, {
        preserveScroll: true,
        onSuccess: (page: any) => {
            // Extraer el ID de la importación de la respuesta
            const importId = page.props.import?.id || page.props.flash?.import_id;
            
            if (importId) {
                emit('success', importId);
                
                // Redirigir si está configurado
                if (props.redirectOnSuccess) {
                    router.get(`/admin/imports/${importId}`);
                }
            }
        },
        onError: (errors) => {
            console.error('Error al crear importación:', errors);
        },
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
                            <p class="text-sm text-muted-foreground">
                                El archivo debe contener al menos las columnas: nombre y email
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
                                v-if="mode === 'votacion'"
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
                        <Alert class="mb-4">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                Se encontraron <strong>{{ csvAnalysis.total_rows }}</strong> registros. 
                                Los campos <strong>Nombre</strong> y <strong>Email</strong> son obligatorios.
                                <span v-if="mode === 'votacion'" class="block mt-1">
                                    Los usuarios serán asignados automáticamente a la votación.
                                </span>
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
                                    v-if="mode === 'votacion'"
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