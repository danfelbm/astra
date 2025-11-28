<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Models\User;

class Proyecto extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'proyectos';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'prioridad',
        'responsable_id',
        'tenant_id',
        'created_by',
        'updated_by',
        'activo',
        'nomenclatura_archivos'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'activo' => 'boolean',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'estado_label',
        'prioridad_label',
        'duracion_dias',
        'porcentaje_completado'
    ];

    /**
     * Obtiene el responsable del proyecto.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtiene el usuario que creó el proyecto.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó el proyecto.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene los valores de campos personalizados del proyecto.
     */
    public function camposPersonalizados(): HasMany
    {
        return $this->hasMany(ValorCampoPersonalizado::class);
    }

    /**
     * Obtiene las etiquetas del proyecto.
     */
    public function etiquetas(): BelongsToMany
    {
        return $this->belongsToMany(Etiqueta::class, 'proyecto_etiqueta')
            ->withPivot(['orden', 'created_at'])
            ->orderBy('proyecto_etiqueta.orden');
    }

    /**
     * Obtiene los contratos del proyecto.
     */
    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    /**
     * Obtiene los participantes del proyecto.
     */
    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_usuario', 'proyecto_id', 'user_id')
                    ->withPivot('rol')
                    ->withTimestamps();
    }

    /**
     * Obtiene los gestores del proyecto.
     * Los gestores tienen permisos de edición sobre el proyecto.
     */
    public function gestores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_usuario', 'proyecto_id', 'user_id')
                    ->wherePivot('rol', 'gestor')
                    ->withTimestamps();
    }

    /**
     * Obtiene los hitos del proyecto.
     */
    public function hitos(): HasMany
    {
        return $this->hasMany(Hito::class)->orderBy('orden');
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    public function getEstadoLabelAttribute(): string
    {
        $labels = [
            'planificacion' => 'Planificación',
            'en_progreso' => 'En Progreso',
            'pausado' => 'Pausado',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        ];

        // Si el estado es null o no existe en los labels, retornar string vacío o valor por defecto
        if ($this->estado === null) {
            return 'Sin estado';
        }

        return $labels[$this->estado] ?? $this->estado;
    }

    /**
     * Obtiene la etiqueta de prioridad.
     */
    public function getPrioridadLabelAttribute(): string
    {
        $labels = [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Crítica'
        ];

        // Si la prioridad es null, retornar valor por defecto
        if ($this->prioridad === null) {
            return 'Media'; // Valor por defecto
        }

        return $labels[$this->prioridad] ?? $this->prioridad;
    }

    /**
     * Obtiene el color asociado al estado.
     */
    public function getEstadoColorAttribute(): string
    {
        $colors = [
            'planificacion' => 'blue',
            'en_progreso' => 'yellow',
            'pausado' => 'orange',
            'completado' => 'green',
            'cancelado' => 'red'
        ];

        return $colors[$this->estado] ?? 'gray';
    }

    /**
     * Obtiene el color asociado a la prioridad.
     */
    public function getPrioridadColorAttribute(): string
    {
        $colors = [
            'baja' => 'gray',
            'media' => 'blue',
            'alta' => 'orange',
            'critica' => 'red'
        ];

        return $colors[$this->prioridad] ?? 'gray';
    }

    /**
     * Calcula la duración del proyecto en días.
     */
    public function getDuracionDiasAttribute(): ?int
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_inicio->diffInDays($this->fecha_fin);
    }

    /**
     * Calcula el porcentaje completado basado en hitos y entregables.
     * Jerarquía: Proyecto > Hitos > Entregables
     *
     * El progreso se calcula considerando:
     * 1. El porcentaje de cada hito (que a su vez se basa en sus entregables)
     * 2. Promedio ponderado de todos los hitos del proyecto
     */
    public function getPorcentajeCompletadoAttribute(): int
    {
        // Estados absolutos
        if ($this->estado === 'completado') {
            return 100;
        }

        if ($this->estado === 'cancelado') {
            return 0;
        }

        // Obtener hitos del proyecto
        $hitos = $this->hitos;

        // Si no hay hitos, retornar 0% (sin trabajo planificado = sin progreso)
        // No usamos cálculo por fechas porque es engañoso (confunde tiempo con trabajo)
        if ($hitos->isEmpty()) {
            return 0;
        }

        // Calcular progreso basado en hitos y entregables
        $sumaProgresos = 0;
        $totalHitos = $hitos->count();

        foreach ($hitos as $hito) {
            // Cada hito contribuye con su porcentaje_completado
            // que ya está calculado basado en sus entregables
            $sumaProgresos += $hito->porcentaje_completado ?? 0;
        }

        $progresoPromedio = $totalHitos > 0 ? ($sumaProgresos / $totalHitos) : 0;

        return round($progresoPromedio);
    }


    /**
     * Scope para proyectos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para proyectos por estado.
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para proyectos por prioridad.
     */
    public function scopePrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para proyectos del responsable actual.
     */
    public function scopeMisProyectos($query)
    {
        if (auth()->check()) {
            return $query->where('responsable_id', auth()->id());
        }
        return $query;
    }

    /**
     * Scope para proyectos donde el usuario actual es gestor.
     * Los gestores tienen permisos de edición sobre el proyecto.
     */
    public function scopeMisProyectosComoGestor($query)
    {
        if (auth()->check()) {
            return $query->whereHas('gestores', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }
        return $query;
    }

    /**
     * Scope para proyectos en progreso.
     */
    public function scopeEnProgreso($query)
    {
        return $query->where('estado', 'en_progreso');
    }

    /**
     * Scope para proyectos vencidos.
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_fin', '<', now())
                    ->whereNotIn('estado', ['completado', 'cancelado']);
    }

    /**
     * Scope para proyectos con etiquetas específicas.
     */
    public function scopeConEtiquetas($query, array $etiquetaIds)
    {
        return $query->whereHas('etiquetas', function ($q) use ($etiquetaIds) {
            $q->whereIn('etiqueta_id', $etiquetaIds);
        });
    }

    /**
     * Scope para proyectos con cualquiera de las etiquetas.
     */
    public function scopeConCualquierEtiqueta($query, array $etiquetaIds)
    {
        return $query->whereHas('etiquetas', function ($q) use ($etiquetaIds) {
            $q->whereIn('etiqueta_id', $etiquetaIds);
        });
    }

    /**
     * Scope para proyectos con todas las etiquetas especificadas.
     */
    public function scopeConTodasLasEtiquetas($query, array $etiquetaIds)
    {
        foreach ($etiquetaIds as $etiquetaId) {
            $query->whereHas('etiquetas', function ($q) use ($etiquetaId) {
                $q->where('etiqueta_id', $etiquetaId);
            });
        }
        return $query;
    }

    /**
     * Sincroniza las etiquetas del proyecto.
     */
    public function sincronizarEtiquetas(array $etiquetaIds): void
    {
        $etiquetasConOrden = [];
        foreach ($etiquetaIds as $index => $etiquetaId) {
            $etiquetasConOrden[$etiquetaId] = ['orden' => $index + 1];
        }

        $this->etiquetas()->sync($etiquetasConOrden);

        // Actualizar contador de usos en las etiquetas
        Etiqueta::whereIn('id', $etiquetaIds)->each(function ($etiqueta) {
            $etiqueta->recalcularUsos();
        });
    }

    /**
     * Sincroniza los gestores del proyecto.
     * Los gestores son usuarios con permisos de edición sobre el proyecto.
     *
     * @param array $gestoresIds Array de IDs de usuarios que serán gestores
     * @return void
     */
    public function sincronizarGestores(array $gestoresIds): void
    {
        // Filtrar IDs inválidos (0, null, false, strings vacías)
        $gestoresIds = array_filter($gestoresIds, function($id) {
            return !empty($id) && is_numeric($id) && $id > 0;
        });

        // Si no hay gestores válidos, solo mantener otros participantes
        if (empty($gestoresIds)) {
            $otrosParticipantes = $this->participantes()
                ->wherePivotIn('rol', ['participante', 'supervisor', 'colaborador'])
                ->get()
                ->mapWithKeys(function ($user) {
                    return [$user->id => ['rol' => $user->pivot->rol]];
                })
                ->toArray();

            $this->participantes()->sync($otrosParticipantes);
            return;
        }

        // Preparar datos para sincronizar con rol 'gestor'
        $gestoresConRol = [];
        foreach ($gestoresIds as $userId) {
            $gestoresConRol[$userId] = ['rol' => 'gestor'];
        }

        // Obtener otros participantes (no gestores) que deben mantenerse
        $otrosParticipantes = $this->participantes()
            ->wherePivotIn('rol', ['participante', 'supervisor', 'colaborador'])
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->id => ['rol' => $user->pivot->rol]];
            })
            ->toArray();

        // Usar array_replace en lugar de array_merge para preservar claves numéricas
        $dataToSync = array_replace($otrosParticipantes, $gestoresConRol);

        // Sincronizar: mantener otros roles + nuevos gestores
        $this->participantes()->sync($dataToSync);
    }

    /**
     * Agrega una etiqueta al proyecto.
     */
    public function agregarEtiqueta(int $etiquetaId, int $orden = null): void
    {
        if (!$this->etiquetas()->where('etiqueta_id', $etiquetaId)->exists()) {
            $orden = $orden ?? $this->etiquetas()->count() + 1;
            $this->etiquetas()->attach($etiquetaId, ['orden' => $orden]);

            Etiqueta::find($etiquetaId)?->incrementarUsos();
        }
    }

    /**
     * Quita una etiqueta del proyecto.
     */
    public function quitarEtiqueta(int $etiquetaId): void
    {
        $this->etiquetas()->detach($etiquetaId);

        Etiqueta::find($etiquetaId)?->decrementarUsos();
    }

    /**
     * Obtiene los contratos activos del proyecto.
     */
    public function getContratosActivos()
    {
        return $this->contratos()->where('estado', 'activo')->get();
    }

    /**
     * Obtiene el monto total de todos los contratos.
     */
    public function getMontoTotalContratos(): float
    {
        return $this->contratos()
            ->whereIn('estado', ['activo', 'finalizado'])
            ->sum('monto_total');
    }

    /**
     * Obtiene el número de contratos por estado.
     */
    public function getContratosPorEstado(): array
    {
        return $this->contratos()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();
    }

    /**
     * Verifica si tiene contratos vencidos.
     */
    public function tieneContratosVencidos(): bool
    {
        return $this->contratos()
            ->where('estado', 'activo')
            ->where('fecha_fin', '<', now())
            ->exists();
    }

    /**
     * Obtiene contratos próximos a vencer.
     */
    public function getContratosProximosVencer($dias = 30)
    {
        return $this->contratos()
            ->where('estado', 'activo')
            ->where('fecha_fin', '<=', now()->addDays($dias))
            ->where('fecha_fin', '>', now())
            ->get();
    }

    /**
     * Obtiene los hitos pendientes del proyecto.
     */
    public function getHitosPendientes()
    {
        return $this->hitos()->where('estado', 'pendiente')->get();
    }

    /**
     * Obtiene los hitos en progreso del proyecto.
     */
    public function getHitosEnProgreso()
    {
        return $this->hitos()->where('estado', 'en_progreso')->get();
    }

    /**
     * Obtiene los hitos completados del proyecto.
     */
    public function getHitosCompletados()
    {
        return $this->hitos()->where('estado', 'completado')->get();
    }

    /**
     * Obtiene el progreso total del proyecto basado en hitos.
     */
    public function getProgresoHitos(): int
    {
        $totalHitos = $this->hitos()->count();

        if ($totalHitos === 0) {
            return 0;
        }

        $hitosCompletados = $this->hitos()->where('estado', 'completado')->count();

        return round(($hitosCompletados / $totalHitos) * 100);
    }

    /**
     * Obtiene los hitos vencidos del proyecto.
     */
    public function getHitosVencidos()
    {
        return $this->hitos()
            ->where('fecha_fin', '<', now())
            ->whereNotIn('estado', ['completado', 'cancelado'])
            ->get();
    }

    /**
     * Obtiene los hitos próximos a vencer.
     */
    public function getHitosProximosVencer($dias = 7)
    {
        return $this->hitos()
            ->where('fecha_fin', '<=', now()->addDays($dias))
            ->where('fecha_fin', '>', now())
            ->whereNotIn('estado', ['completado', 'cancelado'])
            ->get();
    }

    /**
     * Verifica si tiene hitos vencidos.
     */
    public function tieneHitosVencidos(): bool
    {
        return $this->hitos()
            ->where('fecha_fin', '<', now())
            ->whereNotIn('estado', ['completado', 'cancelado'])
            ->exists();
    }

    /**
     * Obtiene estadísticas de hitos del proyecto.
     */
    public function getEstadisticasHitos(): array
    {
        return [
            'total' => $this->hitos()->count(),
            'pendientes' => $this->hitos()->where('estado', 'pendiente')->count(),
            'en_progreso' => $this->hitos()->where('estado', 'en_progreso')->count(),
            'completados' => $this->hitos()->where('estado', 'completado')->count(),
            'cancelados' => $this->hitos()->where('estado', 'cancelado')->count(),
            'vencidos' => $this->getHitosVencidos()->count(),
            'proximos_vencer' => $this->getHitosProximosVencer()->count(),
            'progreso_general' => $this->getProgresoHitos()
        ];
    }
}