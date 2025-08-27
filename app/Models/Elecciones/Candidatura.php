<?php

namespace App\Models\Elecciones;

use App\Jobs\Elecciones\SendCandidaturaAprobadaEmailJob;
use App\Jobs\Elecciones\SendCandidaturaAprobadaWhatsAppJob;
use App\Jobs\Elecciones\SendCandidaturaRechazadaEmailJob;
use App\Jobs\Elecciones\SendCandidaturaRechazadaWhatsAppJob;
use App\Jobs\Elecciones\SendCandidaturaBorradorEmailJob;
use App\Jobs\Elecciones\SendCandidaturaBorradorWhatsAppJob;
use App\Models\Core\User;
use App\Traits\HasTenant;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Candidatura extends Model
{
    use HasFactory, HasTenant, HasAuditLog;

    /**
     * Nombre del log para el sistema de auditoría
     */
    protected $auditLogName = 'candidaturas';


    protected $fillable = [
        'user_id',
        'formulario_data',
        'estado',
        'comentarios_admin',
        'aprobado_por',
        'aprobado_at',
        'version',
        'ultimo_autoguardado',
        'subsanar',
    ];

    protected $casts = [
        'formulario_data' => 'array',
        'aprobado_at' => 'datetime',
        'ultimo_autoguardado' => 'datetime',
        'version' => 'integer',
        'subsanar' => 'boolean',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'version' => 1,
    ];

    // Estados posibles
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(CandidaturaHistorial::class);
    }

    public function campoAprobaciones(): HasMany
    {
        return $this->hasMany(CandidaturaCampoAprobacion::class);
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(CandidaturaComentario::class);
    }

    // Scopes
    public function scopeBorradores(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_BORRADOR);
    }

    public function scopeAprobadas(Builder $query): Builder  
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }

    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_RECHAZADO);
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeDelUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // Métodos de estado
    public function esBorrador(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR;
    }

    public function esAprobada(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    public function esRechazada(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verifica si la candidatura puede ser editada considerando el bloqueo global
     * 
     * @param bool $bloqueoActivo Si el bloqueo global está activo
     * @return bool
     */
    public function puedeEditarConBloqueo(bool $bloqueoActivo = false): bool
    {
        // Si está pendiente, nunca se puede editar
        if ($this->estaPendiente()) {
            return false;
        }

        // Si es borrador y hay bloqueo activo
        if ($this->esBorrador() && $bloqueoActivo) {
            // Solo permitir si tiene la marca de subsanar
            return $this->subsanar === true;
        }

        // Para otros estados, aplicar reglas normales
        // (borrador sin bloqueo, rechazado, o aprobado con campos editables)
        return true;
    }

    // Métodos de acción
    public function aprobar(User $admin, ?string $comentarios = null): bool
    {
        // Registrar estado anterior para auditoría
        $estadoAnterior = $this->estado;
        
        $this->update([
            'estado' => self::ESTADO_APROBADO,
            'aprobado_por' => $admin->id,
            'aprobado_at' => Carbon::now(),
            'comentarios_admin' => $comentarios,
        ]);

        // Registrar en auditoría
        $this->logAction('aprobó', $comentarios, [
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => self::ESTADO_APROBADO,
            'aprobado_por' => $admin->name,
            'con_comentarios' => !empty($comentarios),
        ]);

        // Crear registro en histórico de comentarios
        if ($comentarios) {
            CandidaturaComentario::crearComentario(
                $this,
                $comentarios,
                CandidaturaComentario::TIPO_APROBACION,
                $admin,
                true // Se enviará por email
            );
        }

        // Enviar notificaciones al usuario
        $this->enviarNotificacionAprobacion($comentarios);
        
        return true;
    }

    public function rechazar(User $admin, string $comentarios): bool
    {
        // Registrar estado anterior para auditoría
        $estadoAnterior = $this->estado;
        
        $this->update([
            'estado' => self::ESTADO_RECHAZADO,
            'aprobado_por' => null,
            'aprobado_at' => null,
            'comentarios_admin' => $comentarios,
        ]);

        // Registrar en auditoría
        $this->logAction('rechazó', $comentarios, [
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => self::ESTADO_RECHAZADO,
            'motivo_rechazo' => $comentarios,
        ]);

        // Crear registro en histórico de comentarios
        CandidaturaComentario::crearComentario(
            $this,
            $comentarios,
            CandidaturaComentario::TIPO_RECHAZO,
            $admin,
            true // Se enviará por email
        );

        // Enviar notificaciones al usuario
        $this->enviarNotificacionRechazo($comentarios);

        return true;
    }

    public function volverABorrador(?string $motivo = null): bool
    {
        // Registrar estado anterior para auditoría
        $estadoAnterior = $this->estado;
        
        $this->update([
            'estado' => self::ESTADO_BORRADOR,
            'aprobado_por' => null,
            'aprobado_at' => null,
            'comentarios_admin' => $motivo,
            'subsanar' => true, // Automáticamente habilitar subsanación al volver a borrador
        ]);

        // Registrar en auditoría
        $this->logAction('devolvió a borrador', $motivo, [
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => self::ESTADO_BORRADOR,
            'subsanacion_habilitada' => true,
            'con_motivo' => !empty($motivo),
        ]);

        // Crear registro en histórico de comentarios
        if ($motivo) {
            CandidaturaComentario::crearComentario(
                $this,
                $motivo,
                CandidaturaComentario::TIPO_BORRADOR,
                auth()->user(),
                true // Se enviará por email
            );
        }

        // Enviar notificaciones al usuario
        $this->enviarNotificacionVueltaBorrador($motivo);

        return true;
    }

    public function incrementarVersion(): void
    {
        $versionAnterior = $this->version;
        $this->increment('version');
        
        // Registrar en auditoría
        $this->logAction('incrementó versión', null, [
            'version_anterior' => $versionAnterior,
            'version_nueva' => $this->version,
        ]);
    }

    /**
     * Agregar un comentario sin cambiar el estado
     */
    public function agregarComentario(string $comentario, string $tipo = 'general', bool $enviarEmail = false): CandidaturaComentario
    {
        \Log::info("Agregando comentario a candidatura", [
            'candidatura_id' => $this->id,
            'tipo' => $tipo,
            'enviar_email' => $enviarEmail
        ]);
        
        $comentarioObj = CandidaturaComentario::crearComentario(
            $this,
            $comentario,
            $tipo,
            auth()->user(),
            $enviarEmail
        );

        // Registrar en auditoría
        $tipoLabel = match($tipo) {
            'general' => 'comentario general',
            'nota_admin' => 'nota administrativa',
            default => 'comentario'
        };
        
        $this->logAction('agregó ' . $tipoLabel, null, [
            'tipo_comentario' => $tipo,
            'email_enviado' => $enviarEmail,
            'comentario_id' => $comentarioObj->id,
        ]);

        // Si se debe enviar por email y no es una nota interna
        if ($enviarEmail && $tipo !== CandidaturaComentario::TIPO_NOTA_ADMIN) {
            \Log::info("Intentando enviar notificación de comentario", [
                'candidatura_id' => $this->id,
                'tipo' => $tipo
            ]);
            $this->enviarNotificacionComentario($comentario);
        }

        return $comentarioObj;
    }

    /**
     * Obtener el comentario más reciente de la versión actual
     */
    public function obtenerComentarioActual(): ?CandidaturaComentario
    {
        return $this->comentarios()
            ->where('version_candidatura', $this->version)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obtener el último comentario (independiente de la versión)
     */
    public function obtenerUltimoComentario(): ?CandidaturaComentario
    {
        return $this->comentarios()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Contar total de comentarios
     */
    public function contarComentarios(): int
    {
        return $this->comentarios()->count();
    }

    /**
     * Verificar si tiene comentarios históricos
     */
    public function tieneComentariosHistoricos(): bool
    {
        return $this->contarComentarios() > 1;
    }

    // Determinar si cambios requieren re-aprobación
    public function requiereReaprobacion(array $cambios): bool
    {
        // Campos que requieren re-aprobación si se modifican
        $camposCriticos = [
            'nombre_completo',
            'documento_identidad',
            'fecha_nacimiento',
            'estudios',
            'experiencia_laboral',
            'propuestas',
        ];

        foreach ($camposCriticos as $campo) {
            if (isset($cambios[$campo])) {
                return true;
            }
        }

        return false;
    }

    // Verificar si se pueden editar campos específicos en candidatura aprobada
    public function puedeEditarCampos(array $camposAEditar, array $configuracionCampos): bool
    {
        // Si no está aprobada, se pueden editar todos los campos
        if (!$this->esAprobada()) {
            return true;
        }

        // Si está aprobada, solo se pueden editar campos marcados como editables
        foreach ($camposAEditar as $campoId => $valor) {
            $configuracionCampo = collect($configuracionCampos)->firstWhere('id', $campoId);
            
            // Si el campo no está marcado como editable, no se puede modificar
            if (!$configuracionCampo || !($configuracionCampo['editable'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    // Getters útiles
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_PENDIENTE => 'Pendiente de Revisión',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            default => 'Desconocido',
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_BORRADOR => 'bg-yellow-100 text-yellow-800',
            self::ESTADO_PENDIENTE => 'bg-blue-100 text-blue-800',
            self::ESTADO_APROBADO => 'bg-green-100 text-green-800',
            self::ESTADO_RECHAZADO => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFechaAprobacionAttribute(): ?string
    {
        return $this->aprobado_at?->format('d/m/Y H:i');
    }

    // Método para generar snapshot completo para postulaciones
    public function generarSnapshotCompleto(): array
    {
        // Obtener la configuración de campos activa
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        
        return [
            'candidatura' => [
                'id_original' => $this->id,
                'version_original' => $this->version,
                'formulario_data' => $this->formulario_data,
                'estado_en_momento' => $this->estado,
                'fecha_aprobacion' => $this->fecha_aprobacion,
                'aprobado_por' => [
                    'id' => $this->aprobadoPor?->id,
                    'name' => $this->aprobadoPor?->name,
                    'email' => $this->aprobadoPor?->email,
                ],
                'comentarios_admin' => $this->comentarios_admin,
                'created_at' => $this->created_at->format('d/m/Y H:i'),
                'updated_at' => $this->updated_at->format('d/m/Y H:i'),
            ],
            'usuario' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'configuracion_en_momento' => $config ? [
                'config_id' => $config->id,
                'config_version' => $config->version,
                'campos' => $config->obtenerCampos(),
                'resumen' => $config->resumen,
            ] : null,
            'metadatos_snapshot' => [
                'fecha_snapshot' => Carbon::now()->toISOString(),
                'version_sistema' => '1.0',
            ],
        ];
    }

    // Método para obtener candidaturas aprobadas de un usuario
    public static function obtenerAprobadaDelUsuario(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('estado', self::ESTADO_APROBADO)
            ->first();
    }

    // Métodos para aprobación de campos individuales
    
    /**
     * Obtener aprobaciones de campos agrupadas por campo_id
     */
    public function getCamposAprobaciones(): \Illuminate\Support\Collection
    {
        return $this->campoAprobaciones()
            ->with('aprobadoPor:id,name,email')
            ->get()
            ->keyBy('campo_id');
    }

    /**
     * Verificar si un campo específico está aprobado
     */
    public function isCampoAprobado(string $campoId): bool
    {
        return $this->campoAprobaciones()
            ->where('campo_id', $campoId)
            ->where('aprobado', true)
            ->exists();
    }

    /**
     * Verificar si un campo específico está rechazado
     */
    public function isCampoRechazado(string $campoId): bool
    {
        return $this->campoAprobaciones()
            ->where('campo_id', $campoId)
            ->where('aprobado', false)
            ->whereNotNull('aprobado_por')
            ->exists();
    }

    /**
     * Obtener estado de aprobación de un campo
     */
    public function getEstadoCampo(string $campoId): ?CandidaturaCampoAprobacion
    {
        return $this->campoAprobaciones()
            ->where('campo_id', $campoId)
            ->where('version_candidatura', $this->version)
            ->with('aprobadoPor:id,name,email')
            ->first();
    }

    /**
     * Obtener resumen de estado de aprobación de campos
     */
    public function getEstadoAprobacionCampos(): array
    {
        return CandidaturaCampoAprobacion::obtenerResumen($this->id);
    }

    /**
     * Obtener campos aprobados
     */
    public function getCamposAprobados(): array
    {
        return $this->campoAprobaciones()
            ->where('aprobado', true)
            ->pluck('campo_id')
            ->toArray();
    }

    /**
     * Obtener campos rechazados con comentarios
     */
    public function getCamposRechazados(): \Illuminate\Support\Collection
    {
        return $this->campoAprobaciones()
            ->where('aprobado', false)
            ->whereNotNull('aprobado_por')
            ->with('aprobadoPor:id,name,email')
            ->get();
    }

    /**
     * Verificar si todos los campos requeridos están aprobados
     */
    public function todosCamposRequeridosAprobados(array $camposRequeridos): bool
    {
        return CandidaturaCampoAprobacion::todosCamposRequeridosAprobados($this, $camposRequeridos);
    }

    /**
     * Resetear aprobaciones de campos cuando la candidatura cambia de versión
     */
    public function resetearAprobacionesCampos(): void
    {
        $this->campoAprobaciones()
            ->where('version_candidatura', $this->version)
            ->each(function ($aprobacion) {
                $aprobacion->resetearAprobacion();
            });
    }

    /**
     * Determinar si la candidatura puede ser aprobada globalmente
     * basándose en las aprobaciones individuales de campos
     */
    public function puedeSerAprobadaGlobalmente(array $camposRequeridos = []): bool
    {
        // Si no hay campos requeridos definidos, obtener todos los campos del formulario
        if (empty($camposRequeridos)) {
            $config = CandidaturaConfig::obtenerConfiguracionActiva();
            if ($config && $config->tieneCampos()) {
                $camposRequeridos = collect($config->obtenerCampos())
                    ->where('required', true)
                    ->pluck('id')
                    ->toArray();
            }
        }

        // Verificar que todos los campos requeridos estén aprobados
        return $this->todosCamposRequeridosAprobados($camposRequeridos);
    }

    /**
     * Enviar notificaciones de aprobación al usuario
     */
    private function enviarNotificacionAprobacion(?string $comentarios = null): void
    {
        try {
            $usuario = $this->user;
            
            if (!$usuario) {
                Log::warning("Candidatura {$this->id} no tiene usuario asociado para notificar aprobación");
                return;
            }

            // Enviar email si el usuario tiene dirección de correo
            if (!empty($usuario->email)) {
                SendCandidaturaAprobadaEmailJob::dispatch(
                    $usuario->email,
                    $usuario->name,
                    $this->id,
                    $comentarios
                );
            }

            // Enviar WhatsApp si el usuario tiene teléfono
            if (!empty($usuario->telefono)) {
                SendCandidaturaAprobadaWhatsAppJob::dispatch(
                    $usuario->telefono,
                    $usuario->name,
                    $this->id
                );
            }

            Log::info("Notificaciones de aprobación enviadas", [
                'candidatura_id' => $this->id,
                'usuario_id' => $usuario->id,
                'con_email' => !empty($usuario->email),
                'con_whatsapp' => !empty($usuario->telefono)
            ]);

        } catch (\Exception $e) {
            Log::error("Error enviando notificaciones de aprobación", [
                'candidatura_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar notificaciones de rechazo al usuario
     */
    private function enviarNotificacionRechazo(string $comentarios): void
    {
        try {
            $usuario = $this->user;
            
            if (!$usuario) {
                Log::warning("Candidatura {$this->id} no tiene usuario asociado para notificar rechazo");
                return;
            }

            // Enviar email si el usuario tiene dirección de correo
            if (!empty($usuario->email)) {
                SendCandidaturaRechazadaEmailJob::dispatch(
                    $usuario->email,
                    $usuario->name,
                    $this->id,
                    $comentarios
                );
            }

            // Enviar WhatsApp si el usuario tiene teléfono
            if (!empty($usuario->telefono)) {
                SendCandidaturaRechazadaWhatsAppJob::dispatch(
                    $usuario->telefono,
                    $usuario->name,
                    $this->id,
                    $comentarios
                );
            }

            Log::info("Notificaciones de rechazo enviadas", [
                'candidatura_id' => $this->id,
                'usuario_id' => $usuario->id,
                'con_email' => !empty($usuario->email),
                'con_whatsapp' => !empty($usuario->telefono)
            ]);

        } catch (\Exception $e) {
            Log::error("Error enviando notificaciones de rechazo", [
                'candidatura_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar notificaciones de vuelta a borrador al usuario
     */
    private function enviarNotificacionVueltaBorrador(?string $motivo = null): void
    {
        try {
            $usuario = $this->user;
            
            if (!$usuario) {
                Log::warning("Candidatura {$this->id} no tiene usuario asociado para notificar vuelta a borrador");
                return;
            }

            // Enviar email si el usuario tiene dirección de correo
            if (!empty($usuario->email)) {
                SendCandidaturaBorradorEmailJob::dispatch(
                    $usuario->email,
                    $usuario->name,
                    $this->id,
                    $motivo
                );
            }

            // Enviar WhatsApp si el usuario tiene teléfono
            if (!empty($usuario->telefono)) {
                SendCandidaturaBorradorWhatsAppJob::dispatch(
                    $usuario->telefono,
                    $usuario->name,
                    $this->id,
                    $motivo
                );
            }

            Log::info("Notificaciones de vuelta a borrador enviadas", [
                'candidatura_id' => $this->id,
                'usuario_id' => $usuario->id,
                'con_email' => !empty($usuario->email),
                'con_whatsapp' => !empty($usuario->telefono),
                'con_motivo' => !empty($motivo)
            ]);

        } catch (\Exception $e) {
            Log::error("Error enviando notificaciones de vuelta a borrador", [
                'candidatura_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar notificación de comentario general al usuario
     */
    private function enviarNotificacionComentario(string $comentario): void
    {
        try {
            $usuario = $this->user;
            
            if (!$usuario) {
                Log::warning("Candidatura {$this->id} no tiene usuario asociado para notificar comentario");
                return;
            }

            // Enviar email si el usuario tiene dirección de correo
            if (!empty($usuario->email)) {
                Log::info("Despachando job SendCandidaturaComentarioEmailJob", [
                    'email' => $usuario->email,
                    'candidatura_id' => $this->id
                ]);
                
                \App\Jobs\SendCandidaturaComentarioEmailJob::dispatch(
                    $usuario->email,
                    $usuario->name,
                    $this->id,
                    $comentario
                );
                
                Log::info("Job despachado exitosamente");
            } else {
                Log::warning("Usuario sin email, no se puede enviar notificación", [
                    'usuario_id' => $usuario->id,
                    'candidatura_id' => $this->id
                ]);
            }

            Log::info("Notificación de comentario enviada", [
                'candidatura_id' => $this->id,
                'usuario_id' => $usuario->id,
                'con_email' => !empty($usuario->email)
            ]);

        } catch (\Exception $e) {
            Log::error("Error enviando notificación de comentario", [
                'candidatura_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
