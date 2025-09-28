<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Models\User;
use Carbon\Carbon;

class Contrato extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'contratos';

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
        'estado',
        'tipo',
        'monto_total',
        'moneda',
        'responsable_id',
        'contraparte_user_id',
        'contraparte_nombre',
        'contraparte_identificacion',
        'contraparte_email',
        'contraparte_telefono',
        'archivo_pdf',
        'observaciones',
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
        'monto_total' => 'decimal:2',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'estado_label',
        'tipo_label',
        'estado_color',
        'monto_formateado',
        'duracion_dias',
        'dias_restantes',
        'porcentaje_transcurrido',
        'esta_vencido',
        'esta_proximo_vencer',
        'total_obligaciones',
        'obligaciones_cumplidas',
        'obligaciones_pendientes'
    ];

    /**
     * Obtiene el proyecto al que pertenece el contrato.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Obtiene el responsable del contrato.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtiene el usuario contraparte del contrato.
     */
    public function contraparteUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contraparte_user_id');
    }

    /**
     * Obtiene los participantes del contrato.
     */
    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contrato_usuario', 'contrato_id', 'user_id')
                    ->withPivot('rol', 'notas')
                    ->withTimestamps();
    }

    /**
     * Obtiene el usuario que creó el contrato.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó el contrato.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene los valores de campos personalizados del contrato.
     */
    public function camposPersonalizados(): HasMany
    {
        return $this->hasMany(ValorCampoPersonalizado::class, 'contrato_id');
    }

    /**
     * Obtiene las obligaciones del contrato.
     */
    public function obligaciones(): HasMany
    {
        return $this->hasMany(ObligacionContrato::class, 'contrato_id')->whereNull('parent_id')->orderBy('orden');
    }

    /**
     * Obtiene todas las obligaciones del contrato (incluyendo hijos).
     */
    public function todasLasObligaciones(): HasMany
    {
        return $this->hasMany(ObligacionContrato::class, 'contrato_id')->orderBy('orden');
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    public function getEstadoLabelAttribute(): string
    {
        $labels = [
            'borrador' => 'Borrador',
            'activo' => 'Activo',
            'finalizado' => 'Finalizado',
            'cancelado' => 'Cancelado'
        ];

        return $labels[$this->estado] ?? $this->estado;
    }

    /**
     * Obtiene la etiqueta del tipo.
     */
    public function getTipoLabelAttribute(): string
    {
        $labels = [
            'servicio' => 'Servicio',
            'obra' => 'Obra',
            'suministro' => 'Suministro',
            'consultoria' => 'Consultoría',
            'otro' => 'Otro'
        ];

        return $labels[$this->tipo] ?? $this->tipo;
    }

    /**
     * Obtiene el color asociado al estado.
     */
    public function getEstadoColorAttribute(): string
    {
        $colors = [
            'borrador' => 'gray',
            'activo' => 'green',
            'finalizado' => 'blue',
            'cancelado' => 'red'
        ];

        return $colors[$this->estado] ?? 'gray';
    }

    /**
     * Obtiene el monto formateado con la moneda.
     */
    public function getMontoFormateadoAttribute(): string
    {
        if (!$this->monto_total) {
            return 'Sin monto';
        }

        $simbolos = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'MXN' => '$',
            'COP' => '$'
        ];

        $simbolo = $simbolos[$this->moneda] ?? $this->moneda;
        return $simbolo . ' ' . number_format($this->monto_total, 2, '.', ',');
    }

    /**
     * Calcula la duración del contrato en días.
     */
    public function getDuracionDiasAttribute(): ?int
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_inicio->diffInDays($this->fecha_fin);
    }

    /**
     * Calcula los días restantes del contrato.
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->fecha_fin || $this->estado !== 'activo') {
            return null;
        }

        $dias = Carbon::now()->diffInDays($this->fecha_fin, false);
        return max(0, $dias);
    }

    /**
     * Calcula el porcentaje transcurrido del contrato.
     */
    public function getPorcentajeTranscurridoAttribute(): int
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return 0;
        }

        if ($this->estado === 'finalizado') {
            return 100;
        }

        if ($this->estado === 'cancelado' || $this->estado === 'borrador') {
            return 0;
        }

        $totalDias = $this->fecha_inicio->diffInDays($this->fecha_fin);
        if ($totalDias === 0) {
            return 100;
        }

        $diasTranscurridos = $this->fecha_inicio->diffInDays(now());
        if ($diasTranscurridos <= 0) {
            return 0;
        }

        $porcentaje = min(100, max(0, ($diasTranscurridos / $totalDias) * 100));
        return round($porcentaje);
    }

    /**
     * Verifica si el contrato está vencido.
     */
    public function getEstaVencidoAttribute(): bool
    {
        if (!$this->fecha_fin || $this->estado !== 'activo') {
            return false;
        }

        return $this->fecha_fin->isPast();
    }

    /**
     * Verifica si el contrato está próximo a vencer (30 días).
     */
    public function getEstaProximoVencerAttribute(): bool
    {
        if (!$this->fecha_fin || $this->estado !== 'activo' || $this->esta_vencido) {
            return false;
        }

        return $this->fecha_fin->isBefore(Carbon::now()->addDays(30));
    }

    /**
     * Scope para contratos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para contratos por estado.
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para contratos por tipo.
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para contratos de un proyecto específico.
     */
    public function scopeDelProyecto($query, $proyectoId)
    {
        return $query->where('proyecto_id', $proyectoId);
    }

    /**
     * Scope para contratos del responsable actual.
     */
    public function scopeMisContratos($query)
    {
        if (auth()->check()) {
            return $query->where('responsable_id', auth()->id());
        }
        return $query;
    }

    /**
     * Scope para contratos vencidos.
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_fin', '<', now())
                    ->where('estado', 'activo');
    }

    /**
     * Scope para contratos próximos a vencer (30 días).
     */
    public function scopeProximosVencer($query, $dias = 30)
    {
        return $query->where('fecha_fin', '<=', Carbon::now()->addDays($dias))
                    ->where('fecha_fin', '>', now())
                    ->where('estado', 'activo');
    }

    /**
     * Scope para contratos por rango de fechas.
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->where(function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
              ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
              ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                  $q2->where('fecha_inicio', '<=', $fechaInicio)
                     ->where('fecha_fin', '>=', $fechaFin);
              });
        });
    }

    /**
     * Scope para contratos con monto mayor a.
     */
    public function scopeMontoMayorA($query, $monto)
    {
        return $query->where('monto_total', '>', $monto);
    }

    /**
     * Cambia el estado del contrato.
     */
    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosPermitidos = ['borrador', 'activo', 'finalizado', 'cancelado'];

        if (!in_array($nuevoEstado, $estadosPermitidos)) {
            return false;
        }

        // Validar transiciones de estado
        $transicionesValidas = [
            'borrador' => ['activo', 'cancelado'],
            'activo' => ['finalizado', 'cancelado'],
            'finalizado' => [],
            'cancelado' => []
        ];

        if (!in_array($nuevoEstado, $transicionesValidas[$this->estado] ?? [])) {
            return false;
        }

        $this->estado = $nuevoEstado;
        return $this->save();
    }

    /**
     * Duplica el contrato como plantilla.
     */
    public function duplicar(): Contrato
    {
        $nuevoContrato = $this->replicate();
        $nuevoContrato->nombre = $this->nombre . ' (Copia)';
        $nuevoContrato->estado = 'borrador';
        $nuevoContrato->fecha_inicio = null;
        $nuevoContrato->fecha_fin = null;
        $nuevoContrato->archivo_pdf = null;
        $nuevoContrato->created_by = auth()->id();
        $nuevoContrato->updated_by = auth()->id();
        $nuevoContrato->save();

        // Duplicar campos personalizados
        foreach ($this->camposPersonalizados as $campo) {
            ValorCampoPersonalizado::create([
                'contrato_id' => $nuevoContrato->id,
                'campo_personalizado_id' => $campo->campo_personalizado_id,
                'valor' => $campo->valor
            ]);
        }

        return $nuevoContrato;
    }

    /**
     * Obtiene el total de obligaciones del contrato.
     */
    public function getTotalObligacionesAttribute(): int
    {
        return $this->todasLasObligaciones()->count();
    }

    /**
     * Obtiene el número de obligaciones cumplidas.
     */
    public function getObligacionesCumplidasAttribute(): int
    {
        return $this->todasLasObligaciones()->where('estado', 'cumplida')->count();
    }

    /**
     * Obtiene el número de obligaciones pendientes.
     */
    public function getObligacionesPendientesAttribute(): int
    {
        return $this->todasLasObligaciones()->whereNotIn('estado', ['cumplida', 'cancelada'])->count();
    }
}