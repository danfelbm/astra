<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema de Votaciones
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los parámetros del sistema de votaciones,
    | incluyendo tiempos de sesión, límites y otras opciones.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuración de Sesión de Urna
    |--------------------------------------------------------------------------
    |
    | Configuración relacionada con el sistema de apertura y cierre de urna
    | para garantizar que los votantes tengan tiempo suficiente para votar.
    |
    */

    // Duración de la sesión de urna en minutos (tiempo para completar el voto)
    'urna_session_duration' => env('URNA_SESSION_DURATION', 5),

    // Tiempo de advertencia antes de expirar (en minutos)
    'urna_warning_time' => env('URNA_WARNING_TIME', 2),

    // Tiempo de alerta crítica antes de expirar (en minutos)
    'urna_critical_time' => env('URNA_CRITICAL_TIME', 1),

    // Permitir extensión de tiempo de urna
    'urna_allow_extension' => env('URNA_ALLOW_EXTENSION', false),

    // Tiempo de extensión en minutos (si está habilitado)
    'urna_extension_time' => env('URNA_EXTENSION_TIME', 3),

    // Máximo número de extensiones permitidas por sesión
    'urna_max_extensions' => env('URNA_MAX_EXTENSIONS', 1),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Limpieza
    |--------------------------------------------------------------------------
    */

    // Tiempo en días para mantener sesiones expiradas antes de eliminarlas
    'urna_cleanup_after_days' => env('URNA_CLEANUP_AFTER_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */

    // Verificar consistencia de IP durante la sesión de urna
    'urna_verify_ip' => env('URNA_VERIFY_IP', true),

    // Permitir múltiples pestañas/ventanas con la misma sesión
    'urna_allow_multiple_tabs' => env('URNA_ALLOW_MULTIPLE_TABS', false),

    // Rate limiting: máximo de aperturas de urna por usuario por hora
    'urna_max_opens_per_hour' => env('URNA_MAX_OPENS_PER_HOUR', 10),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    */

    // Habilitar notificaciones de actividad de urna
    'urna_notifications_enabled' => env('URNA_NOTIFICATIONS_ENABLED', true),

    // Email para notificaciones administrativas de urna
    'urna_admin_email' => env('URNA_ADMIN_EMAIL', null),

    /*
    |--------------------------------------------------------------------------
    | Configuración General de Votaciones
    |--------------------------------------------------------------------------
    */

    // Zona horaria por defecto para votaciones
    'default_timezone' => env('VOTACIONES_DEFAULT_TIMEZONE', 'America/Bogota'),

    // Permitir votos en blanco
    'allow_blank_votes' => env('VOTACIONES_ALLOW_BLANK_VOTES', true),

    // Tiempo de cache para resultados de votación (en minutos)
    'results_cache_duration' => env('VOTACIONES_RESULTS_CACHE_DURATION', 5),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Tokens
    |--------------------------------------------------------------------------
    */

    // Algoritmo de hash para tokens
    'token_hash_algo' => env('VOTACIONES_TOKEN_HASH_ALGO', 'sha256'),

    // Incluir metadata adicional en tokens
    'token_include_metadata' => env('VOTACIONES_TOKEN_INCLUDE_METADATA', true),
];