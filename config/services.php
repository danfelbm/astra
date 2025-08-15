<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el servicio de OTP (One-Time Password)
    |
    */
    'otp' => [
        'send_immediately' => env('OTP_SEND_IMMEDIATELY', true),  // true = envío inmediato, false = usar cola
        'expiration_minutes' => (int) env('OTP_EXPIRATION_MINUTES', 10),
        'channel' => env('OTP_CHANNEL', 'email'), // email, whatsapp, both
        'prefer_whatsapp' => env('OTP_PREFER_WHATSAPP', false), // Si both, cuál prefiere
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Evolution API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para integración con Evolution API para envío de mensajes
    | de WhatsApp. Requiere una instancia de Evolution API configurada.
    |
    */
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'api_key' => env('WHATSAPP_API_KEY'),
        'base_url' => env('WHATSAPP_BASE_URL'),
        'instance' => env('WHATSAPP_INSTANCE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zoom Meeting SDK Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Zoom Meeting SDK.
    | Requiere una cuenta de desarrollador de Zoom y credenciales de API.
    |
    */
    'zoom' => [
        'enabled' => env('ZOOM_ENABLED', false),
        'api_key' => env('ZOOM_API_KEY'),
        'api_secret' => env('ZOOM_API_SECRET'),
        'sdk_key' => env('ZOOM_SDK_KEY'),
        'sdk_secret' => env('ZOOM_SDK_SECRET'),
        'auth_endpoint' => env('ZOOM_AUTH_ENDPOINT', '/api/zoom/auth'),
        'webhook_secret' => env('ZOOM_WEBHOOK_SECRET'), // Para webhooks de Zoom (opcional)
        
        // Configuraciones por defecto para reuniones
        'default_settings' => [
            'host_video' => env('ZOOM_DEFAULT_HOST_VIDEO', true),
            'participant_video' => env('ZOOM_DEFAULT_PARTICIPANT_VIDEO', false),
            'cn_meeting' => env('ZOOM_DEFAULT_CN_MEETING', false),
            'in_meeting' => env('ZOOM_DEFAULT_IN_MEETING', false),
            'join_before_host' => env('ZOOM_DEFAULT_JOIN_BEFORE_HOST', false),
            'mute_upon_entry' => env('ZOOM_DEFAULT_MUTE_UPON_ENTRY', true),
            'watermark' => env('ZOOM_DEFAULT_WATERMARK', false),
            'use_pmi' => env('ZOOM_DEFAULT_USE_PMI', false),
            'approval_type' => env('ZOOM_DEFAULT_APPROVAL_TYPE', 0), // 0=automático, 1=manual
            'audio' => env('ZOOM_DEFAULT_AUDIO', 'both'), // both, telephony, voip
            'auto_recording' => env('ZOOM_DEFAULT_AUTO_RECORDING', 'none'), // none, local, cloud
            'waiting_room' => env('ZOOM_DEFAULT_WAITING_ROOM', true),
            'allow_multiple_devices' => env('ZOOM_DEFAULT_ALLOW_MULTIPLE_DEVICES', false),
        ],
    ],

];
