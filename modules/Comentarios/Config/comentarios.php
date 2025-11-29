<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Módulo de Comentarios
    |--------------------------------------------------------------------------
    */

    // Horas límite para editar un comentario (después no se puede editar)
    'horas_edicion' => 24,

    // Emojis permitidos para reacciones
    'emojis' => [
        'thumbs_up' => '👍',
        'thumbs_down' => '👎',
        'heart' => '❤️',
        'laugh' => '😄',
        'clap' => '👏',
        'fire' => '🔥',
        'check' => '✅',
        'eyes' => '👀',
    ],

    // Máximo de caracteres por comentario
    'max_caracteres' => 10000,

    // Comentarios por página en paginación
    'por_pagina' => 20,

    // Niveles máximos de anidamiento (0 = ilimitado)
    'max_niveles' => 0,

    // Modelos permitidos para comentarios (mapeo tipo => clase)
    'modelos' => [
        'hitos' => \Modules\Proyectos\Models\Hito::class,
        'entregables' => \Modules\Proyectos\Models\Entregable::class,
        'evidencias' => \Modules\Proyectos\Models\Evidencia::class,
        // Agregar más modelos aquí según se vayan integrando:
        // 'asambleas' => \Modules\Asamblea\Models\Asamblea::class,
        // 'candidaturas' => \Modules\Elecciones\Models\Candidatura::class,
    ],

    // Configuración de Activity Log
    'activity_log' => [
        'log_name' => 'comentarios',
        'description' => 'Auditoría de Comentarios',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificaciones (TODO: implementar en desarrollo posterior)
    |--------------------------------------------------------------------------
    */
    'notificaciones' => [
        // 'habilitadas' => false,
        // 'canales' => ['database', 'mail'],
        // 'notificar_menciones' => true,
        // 'notificar_respuestas' => true,
    ],
];
