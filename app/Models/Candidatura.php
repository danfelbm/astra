<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Candidatura extends Model
{
    use HasFactory, HasTenant;


    protected $fillable = [
        'user_id',
        'formulario_data',
        'estado',
        'comentarios_admin',
        'aprobado_por',
        'aprobado_at',
        'version',
        'ultimo_autoguardado',
    ];

    protected $casts = [
        'formulario_data' => 'array',
        'aprobado_at' => 'datetime',
        'ultimo_autoguardado' => 'datetime',
        'version' => 'integer',
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

    // Métodos de acción
    public function aprobar(User $admin, ?string $comentarios = null): bool
    {
        $this->update([
            'estado' => self::ESTADO_APROBADO,
            'aprobado_por' => $admin->id,
            'aprobado_at' => Carbon::now(),
            'comentarios_admin' => $comentarios,
        ]);

        // TODO: Enviar email de notificación al usuario
        
        return true;
    }

    public function rechazar(User $admin, string $comentarios): bool
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADO,
            'aprobado_por' => null,
            'aprobado_at' => null,
            'comentarios_admin' => $comentarios,
        ]);

        // TODO: Enviar email de notificación al usuario

        return true;
    }

    public function volverABorrador(): bool
    {
        $this->update([
            'estado' => self::ESTADO_BORRADOR,
            'aprobado_por' => null,
            'aprobado_at' => null,
            'comentarios_admin' => null,
        ]);

        return true;
    }

    public function incrementarVersion(): void
    {
        $this->increment('version');
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
}
