<?php

namespace App\Models;

use App\Traits\HasTenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulacion extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'postulaciones';

    protected $fillable = [
        'convocatoria_id',
        'user_id',
        'formulario_data',
        'candidatura_snapshot',
        'candidatura_id_origen',
        'origen', // 'convocatoria' o 'candidatura'
        'estado',
        'fecha_postulacion',
        'comentarios_admin',
        'revisado_por',
        'revisado_at',
    ];

    protected $casts = [
        'formulario_data' => 'array',
        'candidatura_snapshot' => 'array',
        'fecha_postulacion' => 'datetime',
        'revisado_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => 'borrador',
    ];

    // Estados posibles
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_ENVIADA = 'enviada';
    const ESTADO_EN_REVISION = 'en_revision';
    const ESTADO_ACEPTADA = 'aceptada';
    const ESTADO_RECHAZADA = 'rechazada';

    // Relaciones principales
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(PostulacionHistorial::class)
            ->orderBy('fecha_cambio', 'desc');
    }

    // Scopes para filtrar por estado
    public function scopeBorradores(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_BORRADOR);
    }

    public function scopeEnviadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ENVIADA);
    }

    public function scopeEnRevision(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_EN_REVISION);
    }

    public function scopeAceptadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACEPTADA);
    }

    public function scopeRechazadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_RECHAZADA);
    }

    public function scopePorConvocatoria(Builder $query, int $convocatoriaId): Builder
    {
        return $query->where('convocatoria_id', $convocatoriaId);
    }

    public function scopePorUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    public function scopeOrdenadosPorFecha(Builder $query, string $direccion = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direccion);
    }

    // Métodos de estado
    public function esBorrador(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR;
    }

    public function estaEnviada(): bool
    {
        return $this->estado === self::ESTADO_ENVIADA;
    }

    public function estaEnRevision(): bool
    {
        return $this->estado === self::ESTADO_EN_REVISION;
    }

    public function estaAceptada(): bool
    {
        return $this->estado === self::ESTADO_ACEPTADA;
    }

    public function estaRechazada(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADA;
    }

    // Métodos para manejo de candidatura vinculada
    public function tieneCandidaturaVinculada(): bool
    {
        return !empty($this->candidatura_snapshot);
    }

    public function getCandidaturaData(): ?array
    {
        return $this->candidatura_snapshot;
    }

    public function generarSnapshotCandidatura(Candidatura $candidatura): array
    {
        // Crear snapshot completo de la candidatura aprobada
        return [
            'id_original' => $candidatura->id,
            'version_original' => $candidatura->version,
            'formulario_data' => $candidatura->formulario_data,
            'estado_en_momento' => $candidatura->estado,
            'fecha_aprobacion' => $candidatura->fecha_aprobacion,
            'aprobado_por' => $candidatura->aprobadoPor?->name,
            'comentarios_admin' => $candidatura->comentarios_admin,
            'fecha_snapshot' => Carbon::now()->toISOString(),
            'configuracion_campos' => $this->obtenerConfiguracionCandidatura($candidatura),
        ];
    }

    private function obtenerConfiguracionCandidatura(Candidatura $candidatura): ?array
    {
        // Obtener la configuración de campos activa en el momento
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        
        return $config ? [
            'config_id' => $config->id,
            'config_version' => $config->version,
            'campos' => $config->obtenerCampos(),
        ] : null;
    }

    // Métodos de acción para cambio de estado
    public function enviar(?User $admin = null): bool
    {
        // Puede enviar desde borrador o rechazada
        if (!$this->esBorrador() && !$this->estaRechazada()) {
            return false;
        }

        $estadoAnterior = $this->estado;

        $this->update([
            'estado' => self::ESTADO_ENVIADA,
            'fecha_postulacion' => Carbon::now(),
        ]);

        // Crear registro de historial
        PostulacionHistorial::crearRegistro(
            $this,
            $estadoAnterior,
            self::ESTADO_ENVIADA,
            $admin ?? auth()->user(),
            null,
            'Postulación marcada como enviada'
        );

        return true;
    }

    public function marcarEnRevision(User $revisor): bool
    {
        // Permitir desde enviada, borrador, o rechazada (más flexible)
        if (!in_array($this->estado, [self::ESTADO_ENVIADA, self::ESTADO_BORRADOR, self::ESTADO_RECHAZADA])) {
            return false;
        }

        $estadoAnterior = $this->estado;

        $this->update([
            'estado' => self::ESTADO_EN_REVISION,
            'revisado_por' => $revisor->id,
            'revisado_at' => Carbon::now(),
        ]);

        // Crear registro de historial
        PostulacionHistorial::crearRegistro(
            $this,
            $estadoAnterior,
            self::ESTADO_EN_REVISION,
            $revisor,
            null,
            'Postulación marcada en revisión por administrador'
        );

        return true;
    }

    public function aceptar(User $revisor, ?string $comentarios = null): bool
    {
        if (!$this->estaEnRevision() && !$this->estaEnviada()) {
            return false;
        }

        $estadoAnterior = $this->estado;

        $this->update([
            'estado' => self::ESTADO_ACEPTADA,
            'revisado_por' => $revisor->id,
            'revisado_at' => Carbon::now(),
            'comentarios_admin' => $comentarios,
        ]);

        // Crear registro de historial
        PostulacionHistorial::crearRegistro(
            $this,
            $estadoAnterior,
            self::ESTADO_ACEPTADA,
            $revisor,
            $comentarios,
            'Postulación aceptada por administrador'
        );

        return true;
    }

    public function rechazar(User $revisor, string $comentarios): bool
    {
        if (!$this->estaEnRevision() && !$this->estaEnviada()) {
            return false;
        }

        $estadoAnterior = $this->estado;

        $this->update([
            'estado' => self::ESTADO_RECHAZADA,
            'revisado_por' => $revisor->id,
            'revisado_at' => Carbon::now(),
            'comentarios_admin' => $comentarios,
        ]);

        // Crear registro de historial
        PostulacionHistorial::crearRegistro(
            $this,
            $estadoAnterior,
            self::ESTADO_RECHAZADA,
            $revisor,
            $comentarios,
            'Postulación rechazada por administrador'
        );

        return true;
    }

    public function volverABorrador(?User $admin = null, ?string $comentarios = null): bool
    {
        $estadoAnterior = $this->estado;

        // NO borrar fecha_postulacion
        // Actualizar estado y comentarios si se proporcionan
        $datosActualizar = [
            'estado' => self::ESTADO_BORRADOR,
        ];
        
        // Si se proporcionan comentarios, actualizarlos
        if ($comentarios !== null) {
            $datosActualizar['comentarios_admin'] = $comentarios;
        }
        
        $this->update($datosActualizar);

        // Crear registro de historial
        PostulacionHistorial::crearRegistro(
            $this,
            $estadoAnterior,
            self::ESTADO_BORRADOR,
            $admin ?? auth()->user(),
            $comentarios,
            'Postulación devuelta a borrador'
        );

        return true;
    }

    // Getters para la UI
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_ENVIADA => 'Enviada',
            self::ESTADO_EN_REVISION => 'En Revisión',
            self::ESTADO_ACEPTADA => 'Aceptada',
            self::ESTADO_RECHAZADA => 'Rechazada',
            default => 'Desconocido',
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_BORRADOR => 'bg-yellow-100 text-yellow-800',
            self::ESTADO_ENVIADA => 'bg-blue-100 text-blue-800',
            self::ESTADO_EN_REVISION => 'bg-purple-100 text-purple-800',
            self::ESTADO_ACEPTADA => 'bg-green-100 text-green-800',
            self::ESTADO_RECHAZADA => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFechaPostulacionFormateada(): ?string
    {
        return $this->fecha_postulacion?->format('d/m/Y H:i');
    }

    public function getFechaRevisionFormateada(): ?string
    {
        return $this->revisado_at?->format('d/m/Y H:i');
    }

    // Método para verificar si puede ser editada
    public function puedeSerEditada(): bool
    {
        // Puede ser editada si está en borrador o rechazada
        return $this->esBorrador() || $this->estaRechazada();
    }

    // Método para verificar si puede ser enviada
    public function puedeSerEnviada(): bool
    {
        // Puede ser enviada si está en borrador o rechazada y tiene datos
        return ($this->esBorrador() || $this->estaRechazada()) && !empty($this->formulario_data);
    }

    /**
     * Verificar si un usuario ya tiene una postulación para una convocatoria específica
     * 
     * @param int $userId
     * @param int $convocatoriaId
     * @return bool
     */
    public static function usuarioTienePostulacion(int $userId, int $convocatoriaId): bool
    {
        return static::where('user_id', $userId)
            ->where('convocatoria_id', $convocatoriaId)
            ->exists();
    }
}
