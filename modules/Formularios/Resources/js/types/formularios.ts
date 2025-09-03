// Tipos para el módulo de Formularios

export interface FormularioCategoria {
    id: number;
    tenant_id?: number;
    nombre: string;
    descripcion?: string;
    icono?: string;
    color?: string;
    orden: number;
    activo: boolean;
    created_at: string;
    updated_at: string;
}

export interface Formulario {
    id: number;
    tenant_id?: number;
    titulo: string;
    descripcion?: string;
    slug: string;
    categoria_id?: number;
    categoria?: FormularioCategoria;
    configuracion_campos: FormField[];
    configuracion_general?: Record<string, any>;
    tipo_acceso: 'publico' | 'autenticado' | 'con_permiso';
    permite_visitantes: boolean;
    requiere_captcha: boolean;
    fecha_inicio?: string;
    fecha_fin?: string;
    limite_respuestas?: number;
    limite_por_usuario: number;
    estado: 'borrador' | 'publicado' | 'archivado';
    activo: boolean;
    emails_notificacion?: string[];
    mensaje_confirmacion?: string;
    creado_por?: number;
    actualizado_por?: number;
    created_at: string;
    updated_at: string;
    
    // Propiedades computadas
    estado_temporal?: string;
    estado_temporal_label?: string;
    estado_temporal_color?: string;
    estadisticas?: FormularioEstadisticas;
    url_publica?: string;
}

export interface FormularioEstadisticas {
    total_respuestas: number;
    respuestas_hoy: number;
    respuestas_semana: number;
    respuestas_mes: number;
    usuarios_unicos: number;
    visitantes_unicos: number;
}

export interface FormularioRespuesta {
    id: number;
    tenant_id?: number;
    formulario_id: number;
    formulario?: Formulario;
    usuario_id?: number;
    usuario?: any; // User type
    nombre_visitante?: string;
    email_visitante?: string;
    telefono_visitante?: string;
    documento_visitante?: string;
    respuestas: Record<string, any>;
    metadata?: {
        ip?: string;
        user_agent?: string;
        ultimo_autoguardado?: string;
        primer_autoguardado?: string;
    };
    estado: 'borrador' | 'enviado';
    codigo_confirmacion?: string;
    iniciado_en?: string;
    enviado_en?: string;
    created_at: string;
    updated_at: string;
    
    // Propiedades computadas
    nombre?: string;
    email?: string;
    documento?: string;
    es_visitante?: boolean;
    tiempo_llenado?: string;
}

export interface FormularioPermiso {
    id: number;
    tenant_id?: number;
    formulario_id: number;
    formulario?: Formulario;
    role_id?: number;
    role?: any; // Role type
    usuario_id?: number;
    usuario?: any; // User type
    tipo_permiso: 'ver' | 'llenar' | 'editar_respuesta' | 'ver_respuestas';
    configuracion?: Record<string, any>;
    valido_desde?: string;
    valido_hasta?: string;
    activo: boolean;
    otorgado_por?: number;
    notas?: string;
    created_at: string;
    updated_at: string;
}

// Tipos de campos del formulario (extendiendo los existentes)
export interface FormField {
    id: string;
    type: 'text' | 'textarea' | 'number' | 'email' | 'tel' | 'url' | 
          'date' | 'time' | 'datetime' | 'select' | 'radio' | 
          'checkbox' | 'file' | 'repeater' | 'disclaimer' | 
          'convocatoria' | 'datepicker';
    title: string;
    description?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    editable?: boolean;
    options?: FormFieldOption[];
    validation?: FormFieldValidation;
    conditional?: FormFieldConditional;
    metadata?: Record<string, any>;
}

export interface FormFieldOption {
    value: string;
    label: string;
    disabled?: boolean;
}

export interface FormFieldValidation {
    min?: number;
    max?: number;
    minLength?: number;
    maxLength?: number;
    pattern?: string;
    customMessage?: string;
}

export interface FormFieldConditional {
    field: string;
    operator: 'equals' | 'not_equals' | 'contains' | 'greater_than' | 'less_than';
    value: any;
}

// Tipos para filtros y paginación
export interface FormularioFilters {
    search?: string;
    estado?: string;
    activo?: boolean;
    categoria_id?: number;
    tipo_acceso?: string;
    fecha_inicio?: string;
    fecha_fin?: string;
    advanced_filters?: any;
}

export interface FormularioPaginatedResponse {
    data: Formulario[];
    links: any;
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

export interface RespuestaPaginatedResponse {
    data: FormularioRespuesta[];
    links: any;
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

// Tipos para autoguardado
export interface AutoSaveFormularioData {
    formulario_id: number;
    respuestas: Record<string, any>;
    nombre_visitante?: string;
    email_visitante?: string;
    telefono_visitante?: string;
    documento_visitante?: string;
}

export interface AutoSaveResponse {
    success: boolean;
    message: string;
    data?: {
        respuesta_id: number;
        ultimo_guardado: string;
    };
    errors?: Record<string, string[]>;
}