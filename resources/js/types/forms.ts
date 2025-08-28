// Shared types for dynamic forms system

export interface FormField {
    id: string;
    type: 'text' | 'textarea' | 'number' | 'email' | 'date' | 'select' | 'radio' | 'checkbox' | 'file' | 'perfil_candidatura' | 'convocatoria' | 'datepicker' | 'disclaimer' | 'repeater';
    title: string;
    description?: string;
    required: boolean;
    options?: string[];
    editable?: boolean; // Permite editar este campo en candidaturas aprobadas
    placeholder?: string; // Texto de placeholder para inputs
    // Configuración específica para perfil_candidatura en votaciones (deprecated - será removido)
    perfilCandidaturaConfig?: {
        cargo_id?: number;
        periodo_electoral_id?: number;
        territorio_id?: number;
        departamento_id?: number;
        municipio_id?: number;
        localidad_id?: number;
        territorios_ids?: number[];
        departamentos_ids?: number[];
        municipios_ids?: number[];
        localidades_ids?: number[];
        multiple?: boolean;
        mostrarVotoBlanco?: boolean;
    };
    // Configuración para campo convocatoria (en votaciones y candidaturas)
    convocatoriaConfig?: {
        convocatoria_id?: number; // ID de la convocatoria seleccionada
        multiple?: boolean; // Si permite selección múltiple
        mostrarVotoBlanco?: boolean; // Si muestra opción de voto en blanco (solo votaciones)
        filtrarPorUbicacion?: boolean; // Si filtra convocatorias por ubicación del usuario (candidaturas)
        ordenCandidatos?: 'aleatorio' | 'alfabetico' | 'fecha_postulacion'; // Orden de presentación de candidatos
    };
    // Configuración para campo file
    fileConfig?: {
        multiple?: boolean; // Si permite múltiples archivos
        maxFiles?: number; // Número máximo de archivos
        maxFileSize?: number; // Tamaño máximo en MB
        accept?: string; // Tipos de archivo aceptados
    };
    // Configuración para campo datepicker
    datepickerConfig?: {
        minDate?: string; // Fecha mínima permitida (formato YYYY-MM-DD)
        maxDate?: string; // Fecha máxima permitida (formato YYYY-MM-DD)
        format?: string; // Formato de visualización (por defecto: DD/MM/YYYY)
        allowPastDates?: boolean; // Permitir fechas pasadas
        allowFutureDates?: boolean; // Permitir fechas futuras
    };
    // Configuración para campo disclaimer
    disclaimerConfig?: {
        disclaimerText: string; // Texto del disclaimer a mostrar en el modal
        modalTitle?: string; // Título del modal (por defecto: "Términos y Condiciones")
        acceptButtonText?: string; // Texto del botón aceptar (por defecto: "Acepto")
        declineButtonText?: string; // Texto del botón rechazar (por defecto: "No acepto")
    };
    // Configuración para campo repeater
    repeaterConfig?: {
        minItems?: number; // Número mínimo de elementos (por defecto: 1)
        maxItems?: number; // Número máximo de elementos (por defecto: 10)
        itemName?: string; // Nombre del elemento (por defecto: "Elemento")
        addButtonText?: string; // Texto del botón agregar (por defecto: "Agregar")
        removeButtonText?: string; // Texto del botón eliminar (por defecto: "Eliminar")
        fields: FormField[]; // Subcampos del repetidor
    };
    // Configuración para campo numérico
    numberConfig?: {
        min?: number; // Valor mínimo permitido
        max?: number; // Valor máximo permitido
        step?: number; // Incremento del campo (por defecto: 1)
        decimals?: number; // Número de decimales permitidos (por defecto: 0)
    };
    // Configuración para campos condicionales
    conditionalConfig?: {
        enabled: boolean; // Si las condiciones están activas
        showWhen: 'all' | 'any'; // Mostrar cuando TODAS o ALGUNA condición se cumpla
        conditions: ConditionalRule[]; // Array de condiciones a evaluar
    };
}

// Operadores disponibles para condiciones
export type ConditionalOperator = 'equals' | 'not_equals' | 'contains' | 'not_contains' | 'is_empty' | 'is_not_empty';

// Regla condicional individual
export interface ConditionalRule {
    fieldId: string; // ID del campo a evaluar
    operator: ConditionalOperator; // Operador de comparación
    value?: string | string[]; // Valor(es) a comparar (opcional para is_empty/is_not_empty)
}

export interface FieldType {
    value: FormField['type'];
    label: string;
}

export interface GeographicEntity {
    id: number;
    nombre: string;
}

export interface Territorio extends GeographicEntity {}

export interface Departamento extends GeographicEntity {
    territorio_id: number;
}

export interface Municipio extends GeographicEntity {
    departamento_id: number;
}

export interface Localidad extends GeographicEntity {
    municipio_id: number;
}

export interface GeographicRestrictions {
    territorios_ids: number[];
    departamentos_ids: number[];
    municipios_ids: number[];
    localidades_ids: number[];
}

export interface TimezoneOption {
    value: string;
    label: string;
}

// Available field types for form builder
export const FIELD_TYPES: FieldType[] = [
    { value: 'text', label: 'Texto corto' },
    { value: 'textarea', label: 'Texto largo' },
    { value: 'number', label: 'Campo numérico' },
    { value: 'email', label: 'Correo electrónico' },
    { value: 'date', label: 'Fecha (HTML5)' },
    { value: 'select', label: 'Lista desplegable' },
    { value: 'radio', label: 'Opción múltiple' },
    { value: 'checkbox', label: 'Casillas de verificación' },
    { value: 'file', label: 'Archivo' },
    { value: 'datepicker', label: 'Selector de fecha' },
    { value: 'disclaimer', label: 'Aceptar disclaimer' },
    { value: 'repeater', label: 'Repetidor' },
    { value: 'perfil_candidatura', label: 'Perfil de Candidatura' }, // Para convocatorias: vincular candidatura aprobada
    { value: 'convocatoria', label: 'Elegir Convocatoria' }, // Para votaciones y candidaturas: seleccionar convocatoria
];

// Available timezones for Latin America
export const LATIN_AMERICA_TIMEZONES: TimezoneOption[] = [
    { value: 'America/Bogota', label: 'Colombia (GMT-5) - America/Bogota' },
    { value: 'America/Mexico_City', label: 'México (GMT-6) - America/Mexico_City' },
    { value: 'America/Buenos_Aires', label: 'Argentina (GMT-3) - America/Buenos_Aires' },
    { value: 'America/Sao_Paulo', label: 'Brasil (GMT-3) - America/Sao_Paulo' },
    { value: 'America/Santiago', label: 'Chile (GMT-3) - America/Santiago' },
    { value: 'America/Lima', label: 'Perú (GMT-5) - America/Lima' },
    { value: 'America/Caracas', label: 'Venezuela (GMT-4) - America/Caracas' },
    { value: 'America/La_Paz', label: 'Bolivia (GMT-4) - America/La_Paz' },
    { value: 'America/Quito', label: 'Ecuador (GMT-5) - America/Quito' },
    { value: 'America/Panama', label: 'Panamá (GMT-5) - America/Panama' },
    { value: 'America/Costa_Rica', label: 'Costa Rica (GMT-6) - America/Costa_Rica' },
    { value: 'America/Guatemala', label: 'Guatemala (GMT-6) - America/Guatemala' },
    { value: 'America/Montevideo', label: 'Uruguay (GMT-3) - America/Montevideo' },
    { value: 'America/Asuncion', label: 'Paraguay (GMT-3) - America/Asuncion' },
];