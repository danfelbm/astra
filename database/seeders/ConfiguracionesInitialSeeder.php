<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConfiguracionesInitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $tenantId = 1; // Tenant por defecto

        $configuraciones = [
            // ========================================
            // CONFIGURACIONES DE AUTENTICACIÓN
            // ========================================
            [
                'clave' => 'auth.login_type',
                'valor' => 'email', // Opciones: 'email' o 'documento'
                'tipo' => 'string',
                'descripcion' => 'Tipo de login del sistema (email o documento de identidad)',
                'categoria' => 'autenticacion',
                'publico' => true,
                'validacion' => json_encode(['in:email,documento']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES OTP
            // ========================================
            [
                'clave' => 'otp.channel',
                'valor' => 'email',
                'tipo' => 'string',
                'descripcion' => 'Canal predeterminado para envío de OTP',
                'categoria' => 'otp',
                'publico' => false,
                'validacion' => json_encode(['in:email,whatsapp,both']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'otp.expiry_minutes',
                'valor' => '10',
                'tipo' => 'integer',
                'descripcion' => 'Minutos de validez del código OTP',
                'categoria' => 'otp',
                'publico' => false,
                'validacion' => json_encode(['numeric', 'min:1', 'max:60']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'otp.max_attempts',
                'valor' => '3',
                'tipo' => 'integer',
                'descripcion' => 'Intentos máximos para validar un OTP',
                'categoria' => 'otp',
                'publico' => false,
                'validacion' => json_encode(['numeric', 'min:1', 'max:10']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DEL SISTEMA
            // ========================================
            [
                'clave' => 'system.maintenance_mode',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Modo de mantenimiento del sistema',
                'categoria' => 'sistema',
                'publico' => true,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'system.allow_registration',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Permitir registro de nuevos usuarios',
                'categoria' => 'sistema',
                'publico' => true,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'system.timezone',
                'valor' => 'America/Bogota',
                'tipo' => 'string',
                'descripcion' => 'Zona horaria del sistema',
                'categoria' => 'sistema',
                'publico' => false,
                'validacion' => json_encode(['timezone']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DE APLICACIÓN
            // ========================================
            [
                'clave' => 'app.name',
                'valor' => 'Sistema de Votaciones',
                'tipo' => 'string',
                'descripcion' => 'Nombre de la aplicación',
                'categoria' => 'aplicacion',
                'publico' => true,
                'validacion' => json_encode(['string', 'max:255']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'app.logo_display',
                'valor' => 'logo_text', // Valores: 'logo_text' o 'logo_only'
                'tipo' => 'string',
                'descripcion' => 'Tipo de visualización del logo (logo_text o logo_only)',
                'categoria' => 'aplicacion',
                'publico' => true,
                'validacion' => json_encode(['in:logo_text,logo_only']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'app.logo_text',
                'valor' => 'Sistema de Votaciones',
                'tipo' => 'string',
                'descripcion' => 'Texto del logo',
                'categoria' => 'aplicacion',
                'publico' => true,
                'validacion' => json_encode(['string', 'max:50']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'app.logo_file',
                'valor' => null,
                'tipo' => 'string',
                'descripcion' => 'Ruta del archivo de logo',
                'categoria' => 'aplicacion',
                'publico' => true,
                'validacion' => json_encode(['nullable', 'string']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'app.primary_color',
                'valor' => '#2563eb',
                'tipo' => 'string',
                'descripcion' => 'Color primario de la aplicación',
                'categoria' => 'aplicacion',
                'publico' => true,
                'validacion' => json_encode(['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DE WHATSAPP
            // ========================================
            [
                'clave' => 'whatsapp.enabled',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'WhatsApp habilitado para notificaciones',
                'categoria' => 'whatsapp',
                'publico' => false,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'whatsapp.api_url',
                'valor' => '',
                'tipo' => 'string',
                'descripcion' => 'URL de la API de WhatsApp',
                'categoria' => 'whatsapp',
                'publico' => false,
                'validacion' => json_encode(['nullable', 'url']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'whatsapp.api_token',
                'valor' => '',
                'tipo' => 'string',
                'descripcion' => 'Token de autenticación para WhatsApp API',
                'categoria' => 'whatsapp',
                'publico' => false,
                'validacion' => json_encode(['nullable', 'string']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DE EMAIL
            // ========================================
            [
                'clave' => 'email.from_address',
                'valor' => 'noreply@sistema.com',
                'tipo' => 'string',
                'descripcion' => 'Dirección de email del remitente',
                'categoria' => 'email',
                'publico' => false,
                'validacion' => json_encode(['email']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'email.from_name',
                'valor' => 'Sistema de Votaciones',
                'tipo' => 'string',
                'descripcion' => 'Nombre del remitente de emails',
                'categoria' => 'email',
                'publico' => false,
                'validacion' => json_encode(['string', 'max:255']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DE VOTACIONES
            // ========================================
            [
                'clave' => 'votaciones.allow_change_vote',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Permitir cambiar el voto una vez emitido',
                'categoria' => 'votaciones',
                'publico' => false,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'votaciones.show_results_realtime',
                'valor' => 'false',
                'tipo' => 'boolean',
                'descripcion' => 'Mostrar resultados en tiempo real durante la votación',
                'categoria' => 'votaciones',
                'publico' => false,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'votaciones.require_verification',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Requerir verificación de identidad para votar',
                'categoria' => 'votaciones',
                'publico' => false,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DE SEGURIDAD
            // ========================================
            [
                'clave' => 'security.password_min_length',
                'valor' => '8',
                'tipo' => 'integer',
                'descripcion' => 'Longitud mínima de contraseñas',
                'categoria' => 'seguridad',
                'publico' => false,
                'validacion' => json_encode(['numeric', 'min:6', 'max:32']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'security.session_lifetime',
                'valor' => '120',
                'tipo' => 'integer',
                'descripcion' => 'Duración de la sesión en minutos',
                'categoria' => 'seguridad',
                'publico' => false,
                'validacion' => json_encode(['numeric', 'min:10', 'max:1440']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'security.max_login_attempts',
                'valor' => '5',
                'tipo' => 'integer',
                'descripcion' => 'Intentos máximos de login antes de bloqueo',
                'categoria' => 'seguridad',
                'publico' => false,
                'validacion' => json_encode(['numeric', 'min:3', 'max:10']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // CONFIGURACIONES DE NOTIFICACIONES
            // ========================================
            [
                'clave' => 'notifications.enabled',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Notificaciones del sistema habilitadas',
                'categoria' => 'notificaciones',
                'publico' => false,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'clave' => 'notifications.queue_enabled',
                'valor' => 'true',
                'tipo' => 'boolean',
                'descripcion' => 'Usar colas para el envío de notificaciones',
                'categoria' => 'notificaciones',
                'publico' => false,
                'validacion' => json_encode(['boolean']),
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insertar configuraciones, ignorando duplicados
        foreach ($configuraciones as $config) {
            DB::table('configuraciones')->updateOrInsert(
                ['clave' => $config['clave'], 'tenant_id' => $tenantId],
                $config
            );
        }

        $this->command->info('✅ Configuraciones iniciales creadas exitosamente');
        $this->command->table(
            ['Categoría', 'Total'],
            collect($configuraciones)
                ->groupBy('categoria')
                ->map(fn($items, $key) => [$key, count($items)])
                ->values()
        );
    }
}