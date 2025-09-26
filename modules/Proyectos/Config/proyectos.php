<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Módulo Proyectos
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar las configuraciones del módulo de proyectos,
    | incluyendo valores por defecto, límites, y otras opciones.
    |
    */

    'name' => 'Proyectos',

    /*
    | Estados disponibles para los proyectos
    */
    'estados' => [
        'planificacion' => 'Planificación',
        'en_progreso' => 'En Progreso',
        'pausado' => 'Pausado',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
    ],

    /*
    | Niveles de prioridad disponibles
    */
    'prioridades' => [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'critica' => 'Crítica',
    ],

    /*
    | Tipos de campos personalizados soportados
    */
    'tipos_campos' => [
        'text' => 'Texto',
        'number' => 'Número',
        'date' => 'Fecha',
        'textarea' => 'Área de texto',
        'select' => 'Lista desplegable',
        'checkbox' => 'Casilla de verificación',
        'radio' => 'Botón de opción',
        'file' => 'Archivo',
    ],

    /*
    | Configuración de paginación
    */
    'paginacion' => [
        'proyectos_por_pagina' => 15,
        'campos_por_pagina' => 20,
    ],

    /*
    | Límites del sistema
    */
    'limites' => [
        'max_campos_personalizados' => 50,
        'max_archivo_size' => 10240, // En KB (10MB)
        'max_descripcion_length' => 5000,
    ],

    /*
    | Configuración de notificaciones
    */
    'notificaciones' => [
        'cambio_estado' => true,
        'asignacion_responsable' => true,
        'proximo_vencimiento' => true,
        'dias_antes_vencimiento' => 3,
    ],

    /*
    | Configuración de etiquetas
    */
    'etiquetas' => [
        'max_por_proyecto' => 10,
        'max_categorias' => 20,
        'colores_disponibles' => [
            'gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green',
            'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet',
            'purple', 'fuchsia', 'pink', 'rose'
        ],
        'iconos_sugeridos' => [
            'Tag', 'Hash', 'Bookmark', 'Flag', 'Star', 'Heart',
            'Zap', 'Target', 'Award', 'TrendingUp', 'Folder',
            'Package', 'Box', 'Layers', 'Grid'
        ],
        'cache_ttl' => 300, // 5 minutos
        'sugerencias_limite' => 15,
    ],

    /*
    | Configuración de paginación para etiquetas
    */
    'paginacion_etiquetas' => [
        'etiquetas_por_pagina' => 20,
        'categorias_por_pagina' => 15,
    ],

    /*
    | Configuración de Hitos
    */
    'hitos' => [
        'estados' => [
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
        ],
        'max_por_proyecto' => 50,
        'dias_aviso_vencimiento' => 7,
        'permitir_duplicacion' => true,
    ],

    /*
    | Configuración de Entregables
    */
    'entregables' => [
        'estados' => [
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
        ],
        'prioridades' => [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
        ],
        'max_por_hito' => 100,
        'max_usuarios_asignados' => 10,
        'roles_asignacion' => [
            'responsable' => 'Responsable',
            'colaborador' => 'Colaborador',
            'revisor' => 'Revisor',
        ],
    ],

    /*
    | Plantillas predefinidas para hitos por tipo de proyecto
    */
    'plantillas_hitos' => [
        'documental' => [
            'Pre-producción' => [
                'Investigación y documentación',
                'Guión técnico',
                'Plan de rodaje',
                'Presupuesto',
            ],
            'Producción' => [
                'Grabación de entrevistas',
                'Grabación de B-Roll',
                'Registro de audio',
            ],
            'Post-producción' => [
                'Edición rough cut',
                'Color grading',
                'Mezcla de audio',
                'Master final',
            ],
        ],
        'software' => [
            'Análisis y Diseño' => [
                'Levantamiento de requerimientos',
                'Diseño de arquitectura',
                'Diseño de base de datos',
                'Mockups UI/UX',
            ],
            'Desarrollo' => [
                'Setup del proyecto',
                'Desarrollo del backend',
                'Desarrollo del frontend',
                'Integración de APIs',
            ],
            'Testing y Despliegue' => [
                'Pruebas unitarias',
                'Pruebas de integración',
                'Corrección de bugs',
                'Despliegue a producción',
            ],
        ],
        'generico' => [
            'Planificación' => [
                'Definición de objetivos',
                'Análisis de recursos',
                'Cronograma',
            ],
            'Ejecución' => [
                'Implementación',
                'Seguimiento',
                'Ajustes',
            ],
            'Cierre' => [
                'Entrega final',
                'Documentación',
                'Evaluación',
            ],
        ],
    ],
];