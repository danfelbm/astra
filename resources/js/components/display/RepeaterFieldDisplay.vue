<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Users, User, Phone, Mail, Calendar, Hash, FileText, Briefcase } from 'lucide-vue-next';
import { computed } from 'vue';
import type { FormField } from '@/types/forms';

interface RepeaterItem {
    id: string;
    data: Record<string, any>;
}

interface Props {
    value: RepeaterItem[] | any;
    label?: string;
    showLabel?: boolean;
    fields?: FormField[];
    itemName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: false,
    itemName: 'Elemento'
});

// Procesar los items del repetidor
const items = computed(() => {
    if (!props.value) return [];
    
    // Si es un array de objetos con estructura de repetidor
    if (Array.isArray(props.value)) {
        return props.value.map((item, index) => {
            // Si ya tiene la estructura correcta
            if (item.id && item.data) {
                return item;
            }
            // Si es un objeto plano, convertirlo
            return {
                id: `item_${index}`,
                data: item
            };
        });
    }
    
    return [];
});

// Formatear el valor según su tipo
const formatValue = (value: any, fieldId?: string): string => {
    if (value === null || value === undefined || value === '') {
        return 'No especificado';
    }
    
    // Si es un array
    if (Array.isArray(value)) {
        return value.join(', ');
    }
    
    // Si es una fecha
    if (fieldId && (fieldId.includes('fecha') || fieldId.includes('date'))) {
        try {
            return new Date(value).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch {
            return value.toString();
        }
    }
    
    // Si es un booleano
    if (typeof value === 'boolean') {
        return value ? 'Sí' : 'No';
    }
    
    return value.toString();
};

// Obtener el label de un campo
const getFieldLabel = (fieldId: string): string => {
    // Si tenemos la configuración de campos
    if (props.fields && props.fields.length > 0) {
        const field = props.fields.find(f => f.id === fieldId);
        if (field) return field.title;
    }
    
    // Generar label basado en el ID del campo
    return fieldId
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
        .replace(/Id$/i, '')
        .replace(/Contacto$/i, '')
        .trim();
};

// Obtener ícono según el tipo de campo
const getFieldIcon = (fieldId: string) => {
    const id = fieldId.toLowerCase();
    
    if (id.includes('nombre') || id.includes('name')) return User;
    if (id.includes('telefono') || id.includes('phone') || id.includes('celular')) return Phone;
    if (id.includes('email') || id.includes('correo')) return Mail;
    if (id.includes('fecha') || id.includes('date')) return Calendar;
    if (id.includes('numero') || id.includes('cantidad')) return Hash;
    if (id.includes('documento') || id.includes('cedula')) return FileText;
    if (id.includes('cargo') || id.includes('empresa') || id.includes('trabajo')) return Briefcase;
    
    return null;
};

// Determinar si un campo es importante (para resaltarlo)
const isImportantField = (fieldId: string): boolean => {
    const importantFields = ['nombre', 'name', 'titulo', 'title', 'cargo', 'empresa'];
    return importantFields.some(field => fieldId.toLowerCase().includes(field));
};
</script>

<template>
    <div class="space-y-2">
        <!-- Label opcional -->
        <div v-if="showLabel && label" class="flex items-center gap-2">
            <Users class="h-4 w-4 text-muted-foreground" />
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ label }}
            </label>
            <Badge variant="secondary" class="ml-2">
                {{ items.length }} {{ items.length === 1 ? itemName : `${itemName}s` }}
            </Badge>
        </div>
        
        <!-- Mensaje si no hay items -->
        <div v-if="items.length === 0" class="text-sm text-muted-foreground italic">
            No hay {{ itemName.toLowerCase() }}s registrados
        </div>
        
        <!-- Lista de items -->
        <div v-else class="space-y-3">
            <Card 
                v-for="(item, index) in items" 
                :key="item.id"
                class="hover:shadow-md transition-all duration-200"
            >
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="font-normal">
                                {{ itemName }} {{ index + 1 }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                
                <CardContent class="space-y-3">
                    <!-- Iterar sobre los campos del item -->
                    <div 
                        v-for="(value, key) in item.data" 
                        :key="key"
                        class="flex flex-col sm:flex-row sm:items-start gap-2"
                    >
                        <!-- Label del campo -->
                        <div class="flex items-center gap-2 min-w-[140px]">
                            <component 
                                v-if="getFieldIcon(String(key))"
                                :is="getFieldIcon(String(key))" 
                                class="h-4 w-4 text-muted-foreground flex-shrink-0" 
                            />
                            <Label 
                                class="text-sm"
                                :class="isImportantField(String(key)) ? 'font-semibold' : 'font-medium text-muted-foreground'"
                            >
                                {{ getFieldLabel(String(key)) }}:
                            </Label>
                        </div>
                        
                        <!-- Valor del campo -->
                        <div class="flex-1">
                            <p 
                                class="text-sm"
                                :class="isImportantField(String(key)) ? 'font-medium text-gray-900 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300'"
                            >
                                {{ formatValue(value, String(key)) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Si no hay datos en el item -->
                    <div v-if="Object.keys(item.data).length === 0" class="text-sm text-muted-foreground italic">
                        Sin información disponible
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>