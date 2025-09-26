<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Models\User;

class Hito extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'hitos';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'proyecto_id',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'orden',
        'estado',
        'porcentaje_completado',
        'responsable_id',
        'tenant_id',
        'created_by',
        'updated_by'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'porcentaje_completado' => 'integer',
        'orden' => 'integer',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'estado_label',
        'estado_color',
        'dias_restantes',
        'total_entregables',
        'entregables_completados',
        'esta_vencido',
        'esta_proximo_vencer'
    ];

    /**
     * Obtiene el proyecto al que pertenece el hito.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Obtiene el responsable del hito.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtiene el usuario que creó el hito.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó el hito.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene los entregables del hito.
     */
    public function entregables(): HasMany
    {
        return $this->hasMany(Entregable::class)->orderBy('orden');
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    public function getEstadoLabelAttribute(): string
    {
        $labels = [
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        ];

        return $labels[$this->estado] ?? $this->estado;
    }

    /**
     * Obtiene el color asociado al estado.
     */
    public function getEstadoColorAttribute(): string
    {
        $colors = [
            'pendiente' => 'gray',
            'en_progreso' => 'yellow',
            'completado' => 'green',
            'cancelado' => 'red'
        ];

        return $colors[$this->estado] ?? 'gray';
    }

    /**
     * Calcula los días restantes hasta la fecha de fin.
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->fecha_fin) {
            return null;
        }

        if ($this->estado === 'completado' || $this->estado === 'cancelado') {
            return null;
        }

        $hoy = now()->startOfDay();
        $fin = $this->fecha_fin->startOfDay();

        return $fin->diffInDays($hoy, false) * -1; // Negativo si ya pasó
    }

    /**
     * Obtiene el total de entregables.
     */
    public function getTotalEntregablesAttribute(): int
    {
        return $this->entregables()->count();
    }

    /**
     * Obtiene el número de entregables completados.
     */
    public function getEntregablesCompletadosAttribute(): int
    {
        return $this->entregables()->where('estado', 'completado')->count();
    }

    /**
     * Verifica si el hito está vencido.
     */
    public function getEstaVencidoAttribute(): bool
    {
        if ($this->estado === 'completado' || $this->estado === 'cancelado') {
            return false;
        }

        if (!$this->fecha_fin) {
            return false;
        }

        return $this->fecha_fin->isPast();
    }

    /**
     * Verifica si el hito está próximo a vencer (7 días).
     */
    public function getEstaProximoVencerAttribute(): bool
    {
        if ($this->esta_vencido) {
            return false;
        }

        if ($this->estado === 'completado' || $this->estado === 'cancelado') {
            return false;
        }

        if (!$this->fecha_fin) {
            return false;
        }

        $diasRestantes = $this->dias_restantes;
        return $diasRestantes !== null && $diasRestantes > 0 && $diasRestantes <= 7;
    }

    /**
     * Calcula y actualiza el porcentaje completado basado en los entregables.
     */
    public function calcularPorcentajeCompletado(): void
    {
        $totalEntregables = $this->entregables()->count();

        if ($totalEntregables === 0) {
            $this->porcentaje_completado = 0;
        } else {
            $completados = $this->entregables()->where('estado', 'completado')->count();
            $this->porcentaje_completado = round(($completados / $totalEntregables) * 100);
        }

        // Si todos los entregables están completados, marcar el hito como completado
        if ($this->porcentaje_completado === 100 && $this->estado !== 'completado') {
            $this->estado = 'completado';
        }

        $this->save();
    }

    /**
     * Scope para hitos pendientes.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para hitos en progreso.
     */
    public function scopeEnProgreso($query)
    {
        return $query->where('estado', 'en_progreso');
    }

    /**
     * Scope para hitos completados.
     */
    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    /**
     * Scope para hitos vencidos.
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_fin', '<', now())
                    ->whereNotIn('estado', ['completado', 'cancelado']);
    }

    /**
     * Scope para hitos próximos a vencer.
     */
    public function scopeProximosVencer($query, $dias = 7)
    {
        return $query->where('fecha_fin', '<=', now()->addDays($dias))
                    ->where('fecha_fin', '>', now())
                    ->whereNotIn('estado', ['completado', 'cancelado']);
    }

    /**
     * Scope para hitos del usuario actual.
     */
    public function scopeMisHitos($query)
    {
        if (auth()->check()) {
            return $query->where('responsable_id', auth()->id());
        }
        return $query;
    }

    /**
     * Duplica el hito con sus entregables.
     */
    public function duplicar(string $nuevoNombre = null): Hito
    {
        $nuevoHito = $this->replicate();
        $nuevoHito->nombre = $nuevoNombre ?? $this->nombre . ' (Copia)';
        $nuevoHito->estado = 'pendiente';
        $nuevoHito->porcentaje_completado = 0;
        $nuevoHito->created_by = auth()->id();
        $nuevoHito->updated_by = auth()->id();
        $nuevoHito->save();

        // Duplicar entregables
        foreach ($this->entregables as $entregable) {
            $nuevoEntregable = $entregable->replicate();
            $nuevoEntregable->hito_id = $nuevoHito->id;
            $nuevoEntregable->estado = 'pendiente';
            $nuevoEntregable->completado_at = null;
            $nuevoEntregable->completado_por = null;
            $nuevoEntregable->notas_completado = null;
            $nuevoEntregable->created_by = auth()->id();
            $nuevoEntregable->updated_by = auth()->id();
            $nuevoEntregable->save();
        }

        return $nuevoHito;
    }

    /**
     * Reordena los hitos dentro del proyecto.
     */
    public static function reordenar(int $proyectoId, array $ordenIds): void
    {
        foreach ($ordenIds as $orden => $hitoId) {
            static::where('id', $hitoId)
                  ->where('proyecto_id', $proyectoId)
                  ->update(['orden' => $orden + 1]);
        }
    }
}