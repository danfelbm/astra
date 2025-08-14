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

];
