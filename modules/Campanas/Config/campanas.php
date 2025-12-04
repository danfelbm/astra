<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Módulo de Campañas
    |--------------------------------------------------------------------------
    */
    
    // Configuración de batches para envío masivo
    'batch' => [
        'email' => [
            'size' => env('CAMPANAS_EMAIL_BATCH_SIZE', 100), // Máximo 100 (límite Resend batch API)
            'delay' => env('CAMPANAS_EMAIL_BATCH_DELAY', 1), // Segundos entre batches
        ],
        'whatsapp' => [
            // WhatsApp se envía UNO A UNO con intervalos (Evolution API no soporta batch)
            'min_delay' => env('CAMPANAS_WHATSAPP_MIN_DELAY', 5),   // Mínimo 5 segundos entre mensajes
            'max_delay' => env('CAMPANAS_WHATSAPP_MAX_DELAY', 120), // Máximo 120 segundos entre mensajes
        ],
    ],
    
    // Configuración de tracking
    'tracking' => [
        'pixel_enabled' => env('CAMPANAS_TRACKING_PIXEL', true),
        'click_tracking' => env('CAMPANAS_CLICK_TRACKING', true),
        'ttl' => env('CAMPANAS_TRACKING_TTL', 2592000), // 30 días en segundos
    ],
    
    // Configuración de rate limiting específico para campañas
    'rate_limits' => [
        'email' => env('CAMPANAS_EMAIL_RATE_LIMIT', 2),      // Emails por segundo
        'whatsapp' => env('CAMPANAS_WHATSAPP_RATE_LIMIT', 1), // WhatsApp por segundo - Límite seguro para Evolution API
    ],
    
    // Variables disponibles para plantillas
    'template_variables' => [
        'user' => [
            'name' => 'Nombre del usuario',
            'email' => 'Email del usuario',
            'telefono' => 'Teléfono del usuario',
            'documento_identidad' => 'Documento de identidad',
        ],
        'territorio' => [
            'nombre' => 'Nombre del territorio',
        ],
        'departamento' => [
            'nombre' => 'Nombre del departamento',
        ],
        'municipio' => [
            'nombre' => 'Nombre del municipio',
        ],
        'localidad' => [
            'nombre' => 'Nombre de la localidad',
        ],
    ],
    
    // Configuración de métricas
    'metrics' => [
        'cache_duration' => env('CAMPANAS_METRICS_CACHE', 300), // 5 minutos
        'realtime_update' => env('CAMPANAS_REALTIME_METRICS', true),
    ],
];