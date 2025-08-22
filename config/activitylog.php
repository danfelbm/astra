<?php

return [

    /*
     * Si se establece en false, no se guardarán actividades en la base de datos.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * Cuando se ejecute el comando de limpieza, todas las actividades registradas
     * anteriores al número de días especificado aquí serán eliminadas.
     * Configurado a 365 días para mantener un año completo de auditoría.
     */
    'delete_records_older_than_days' => 365,

    /*
     * Si no se pasa un nombre de log al helper activity()
     * usaremos este nombre de log por defecto.
     */
    'default_log_name' => 'sistema',

    /*
     * Puedes especificar un driver de autenticación aquí que obtenga modelos de usuario.
     * Si es null, usaremos el driver de autenticación actual de Laravel.
     */
    'default_auth_driver' => null,

    /*
     * Si se establece en true, el subject retorna modelos soft deleted.
     * Útil para auditar incluso registros eliminados.
     */
    'subject_returns_soft_deleted_models' => true,

    /*
     * Este modelo se usará para registrar actividad.
     * Debe implementar la interfaz Spatie\Activitylog\Contracts\Activity
     * y extender Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * Este es el nombre de la tabla que será creada por la migración
     * y usada por el modelo Activity incluido con este paquete.
     */
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    /*
     * Esta es la conexión de base de datos que será usada por la migración
     * y el modelo Activity incluido con este paquete.
     */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),

    /*
     * Configuración personalizada para el sistema de votaciones
     */
    'log_names' => [
        'candidaturas' => 'Auditoría de Candidaturas',
        'usuarios' => 'Auditoría de Usuarios',
        'votaciones' => 'Auditoría de Votaciones',
        'convocatorias' => 'Auditoría de Convocatorias',
        'postulaciones' => 'Auditoría de Postulaciones',
        'configuracion' => 'Auditoría de Configuración',
        'autenticacion' => 'Auditoría de Autenticación',
        'sistema' => 'Auditoría General del Sistema',
    ],

    /*
     * Eventos que deben registrarse automáticamente
     */
    'auto_log_events' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'restored' => true,
    ],

    /*
     * Campos que nunca deben registrarse en los logs por seguridad
     */
    'excluded_fields' => [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],
];