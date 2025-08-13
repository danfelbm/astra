<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CandidaturaCampoAprobacion extends Model
{
    use HasFactory, HasTenant;

    /**
     * Nombre de la tabla
     */
    protected $table = 'candidatura_campo_aprobaciones';

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'candidatura_id',
        'campo_id',
        'aprobado',
        'aprobado_por',
        'aprobado_at',
        'comentario',
        'version_candidatura',
        'valor_aprobado',
        'tenant_id',
    ];

    /**
     * Cast de atributos
     */
    protected $casts = [
        'aprobado' => 'boolean',
        'aprobado_at' => 'datetime',
        'valor_aprobado' => 'array',
        'version_candidatura' => 'integer',
    ];

    // Relaciones

    /**
     * Candidatura asociada
     */
    public function candidatura(): BelongsTo
    {
        return $this->belongsTo(Candidatura::class);
    }

    /**
     * Usuario que aprobó/rechazó el campo
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    // Métodos de estado

    /**
     * Verificar si el campo está aprobado
     */
    public function estaAprobado(): bool
    {
        return $this->aprobado === true;
    }

    /**
     * Verificar si el campo está rechazado
     */
    public function estaRechazado(): bool
    {
        return $this->aprobado === false && $this->aprobado_por !== null;
    }

    /**
     * Verificar si el campo está pendiente de revisión
     */
    public function estaPendiente(): bool
    {
        return $this->aprobado_por === null;
    }

    // Métodos de acción

    /**
     * Aprobar el campo
     */
    public function aprobar(User $usuario, ?string $comentario = null): bool
    {
        return $this->update([
            'aprobado' => true,
            'aprobado_por' => $usuario->id,
            'aprobado_at' => Carbon::now(),
            'comentario' => $comentario,
        ]);
    }

    /**
     * Rechazar el campo
     */
    public function rechazar(User $usuario, string $comentario): bool
    {
        return $this->update([
            'aprobado' => false,
            'aprobado_por' => $usuario->id,
            'aprobado_at' => Carbon::now(),
            'comentario' => $comentario,
        ]);
    }

    /**
     * Resetear aprobación (volver a pendiente)
     */
    public function resetearAprobacion(): bool
    {
        return $this->update([
            'aprobado' => false,
            'aprobado_por' => null,
            'aprobado_at' => null,
            'comentario' => null,
        ]);
    }

    // Métodos estáticos

    /**
     * Crear o actualizar aprobación de campo
     */
    public static function crearOActualizar(
        Candidatura $candidatura,
        string $campoId,
        bool $aprobado,
        User $usuario,
        ?string $comentario = null,
        $valorActual = null
    ): self {
        return static::updateOrCreate(
            [
                'candidatura_id' => $candidatura->id,
                'campo_id' => $campoId,
                'version_candidatura' => $candidatura->version,
            ],
            [
                'aprobado' => $aprobado,
                'aprobado_por' => $usuario->id,
                'aprobado_at' => Carbon::now(),
                'comentario' => $comentario,
                'valor_aprobado' => $valorActual,
                'tenant_id' => $candidatura->tenant_id,
            ]
        );
    }

    /**
     * Obtener aprobaciones de una candidatura agrupadas por campo
     */
    public static function obtenerPorCandidatura(int $candidaturaId): \Illuminate\Support\Collection
    {
        return static::where('candidatura_id', $candidaturaId)
            ->with('aprobadoPor:id,name,email')
            ->get()
            ->keyBy('campo_id');
    }

    /**
     * Verificar si todos los campos requeridos están aprobados
     */
    public static function todosCamposRequeridosAprobados(
        Candidatura $candidatura,
        array $camposRequeridos
    ): bool {
        $aprobaciones = static::where('candidatura_id', $candidatura->id)
            ->whereIn('campo_id', $camposRequeridos)
            ->where('aprobado', true)
            ->pluck('campo_id')
            ->toArray();

        return count($aprobaciones) === count($camposRequeridos);
    }

    /**
     * Obtener resumen de aprobaciones
     */
    public static function obtenerResumen(int $candidaturaId, ?int $totalCampos = null): array
    {
        $aprobaciones = static::where('candidatura_id', $candidaturaId)->get();
        $aprobados = $aprobaciones->where('aprobado', true)->count();
        $rechazados = $aprobaciones->where('aprobado', false)
            ->whereNotNull('aprobado_por')->count();
        
        // Si se proporciona total de campos, usar ese, sino contar registros
        $total = $totalCampos ?? $aprobaciones->count();
        $pendientes = $total - $aprobados - $rechazados;
        
        return [
            'total' => $total,
            'aprobados' => $aprobados,
            'rechazados' => $rechazados,
            'pendientes' => max(0, $pendientes), // Asegurar que no sea negativo
            'porcentaje_aprobado' => $total > 0 
                ? round(($aprobados / $total) * 100, 2)
                : 0,
        ];
    }

    // Getters útiles

    /**
     * Obtener estado como texto
     */
    public function getEstadoLabelAttribute(): string
    {
        if ($this->estaAprobado()) {
            return 'Aprobado';
        } elseif ($this->estaRechazado()) {
            return 'Rechazado';
        } else {
            return 'Pendiente';
        }
    }

    /**
     * Obtener color del estado para UI
     */
    public function getEstadoColorAttribute(): string
    {
        if ($this->estaAprobado()) {
            return 'bg-green-100 text-green-800';
        } elseif ($this->estaRechazado()) {
            return 'bg-red-100 text-red-800';
        } else {
            return 'bg-yellow-100 text-yellow-800';
        }
    }

    /**
     * Obtener fecha formateada de aprobación
     */
    public function getFechaAprobacionAttribute(): ?string
    {
        return $this->aprobado_at?->format('d/m/Y H:i');
    }

    /**
     * Obtener información del revisor
     */
    public function getRevisorInfoAttribute(): ?array
    {
        if (!$this->aprobadoPor) {
            return null;
        }

        return [
            'id' => $this->aprobadoPor->id,
            'name' => $this->aprobadoPor->name,
            'email' => $this->aprobadoPor->email,
            'fecha' => $this->fecha_aprobacion,
        ];
    }
}