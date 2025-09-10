import { ref, computed } from 'vue';

export interface TemplateVariable {
    key: string;
    label: string;
    example: string;
    category: 'user' | 'location' | 'system' | 'custom';
}

export function useTemplateVariables() {
    // Variables disponibles para plantillas
    const availableVariables = ref<TemplateVariable[]>([
        // Variables de usuario
        { key: '{{nombre}}', label: 'Nombre completo', example: 'Juan Pérez', category: 'user' },
        { key: '{{primer_nombre}}', label: 'Primer nombre', example: 'Juan', category: 'user' },
        { key: '{{apellido}}', label: 'Apellido', example: 'Pérez', category: 'user' },
        { key: '{{email}}', label: 'Email', example: 'usuario@ejemplo.com', category: 'user' },
        { key: '{{telefono}}', label: 'Teléfono', example: '+57 300 123 4567', category: 'user' },
        { key: '{{documento_identidad}}', label: 'Documento', example: '1234567890', category: 'user' },
        
        // Variables de ubicación
        { key: '{{territorio}}', label: 'Territorio', example: 'Territorio Central', category: 'location' },
        { key: '{{departamento}}', label: 'Departamento', example: 'Cundinamarca', category: 'location' },
        { key: '{{municipio}}', label: 'Municipio', example: 'Bogotá D.C.', category: 'location' },
        { key: '{{localidad}}', label: 'Localidad', example: 'Chapinero', category: 'location' },
        
        // Variables del sistema
        { key: '{{fecha_actual}}', label: 'Fecha actual', example: new Date().toLocaleDateString('es-CO'), category: 'system' },
        { key: '{{ano_actual}}', label: 'Año actual', example: new Date().getFullYear().toString(), category: 'system' },
        { key: '{{enlace_desuscripcion}}', label: 'Enlace de desuscripción', example: '[Enlace]', category: 'system' },
        { key: '{{ver_en_navegador}}', label: 'Ver en navegador', example: '[Ver en navegador]', category: 'system' },
    ]);

    // Extraer variables usadas en un contenido
    const extractUsedVariables = (content: string): string[] => {
        const regex = /\{\{([^}]+)\}\}/g;
        const matches = content.matchAll(regex);
        const variables = new Set<string>();
        
        for (const match of matches) {
            variables.add(`{{${match[1]}}}`);
        }
        
        return Array.from(variables);
    };

    // Reemplazar variables con valores de ejemplo
    const replaceWithExamples = (content: string): string => {
        let result = content;
        
        availableVariables.value.forEach(variable => {
            result = result.replaceAll(variable.key, variable.example);
        });
        
        return result;
    };

    // Validar que todas las variables usadas existen
    const validateVariables = (content: string): { valid: boolean; missing: string[] } => {
        const used = extractUsedVariables(content);
        const available = availableVariables.value.map(v => v.key);
        const missing = used.filter(v => !available.includes(v));
        
        return {
            valid: missing.length === 0,
            missing
        };
    };

    // Insertar variable en posición del cursor
    const insertVariable = (
        textareaRef: HTMLTextAreaElement | null, 
        variable: string
    ): string => {
        if (!textareaRef) return '';
        
        const start = textareaRef.selectionStart;
        const end = textareaRef.selectionEnd;
        const text = textareaRef.value;
        
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        
        const newText = before + variable + after;
        
        // Actualizar el textarea
        textareaRef.value = newText;
        
        // Posicionar cursor después de la variable
        setTimeout(() => {
            textareaRef.selectionStart = textareaRef.selectionEnd = start + variable.length;
            textareaRef.focus();
        }, 0);
        
        return newText;
    };

    // Obtener categorías de variables
    const variableCategories = computed(() => {
        const categories = {
            user: { label: 'Usuario', variables: [] as TemplateVariable[] },
            location: { label: 'Ubicación', variables: [] as TemplateVariable[] },
            system: { label: 'Sistema', variables: [] as TemplateVariable[] },
            custom: { label: 'Personalizadas', variables: [] as TemplateVariable[] },
        };
        
        availableVariables.value.forEach(variable => {
            categories[variable.category].variables.push(variable);
        });
        
        return categories;
    });

    // Contar variables usadas
    const countVariables = (content: string): number => {
        return extractUsedVariables(content).length;
    };

    // Estimar longitud del contenido con variables reemplazadas
    const estimateLength = (content: string): { min: number; max: number; avg: number } => {
        let minLength = content;
        let maxLength = content;
        
        // Reemplazar con valores mínimos y máximos estimados
        availableVariables.value.forEach(variable => {
            const minValue = variable.category === 'user' ? 'Juan' : variable.example.substring(0, 5);
            const maxValue = variable.example.padEnd(30, 'x');
            
            minLength = minLength.replaceAll(variable.key, minValue);
            maxLength = maxLength.replaceAll(variable.key, maxValue);
        });
        
        return {
            min: minLength.length,
            max: maxLength.length,
            avg: Math.round((minLength.length + maxLength.length) / 2)
        };
    };

    return {
        availableVariables,
        variableCategories,
        extractUsedVariables,
        replaceWithExamples,
        validateVariables,
        insertVariable,
        countVariables,
        estimateLength,
    };
}