<?php

return [
    'name' => 'Asamblea',
    'version' => '1.0.0',
    'description' => 'Módulo de gestión de Asambleas',
    'zoom' => [
        'enabled' => env('ZOOM_ENABLED', false),
        'sdk_key' => env('ZOOM_SDK_KEY'),
        'sdk_secret' => env('ZOOM_SDK_SECRET'),
    ],
];
