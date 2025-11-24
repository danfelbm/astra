<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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
        'parent_id',
        'nivel',
        'ruta',
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
        'parent_id' => 'integer',
        'nivel' => 'integer',
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
        'esta_proximo_vencer',
        'tiene_hijos',
        'ruta_completa'
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
     * Relación con el hito padre (jerarquía).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relación con los hitos hijos (jerarquía).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Obtiene todos los ancestros del hito (jerarquía).
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Obtiene todos los descendientes del hito (jerarquía).
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Obtiene los valores de campos personalizados del hito.
     */
    public function camposPersonalizados(): HasMany
    {
        return $this->hasMany(ValorCampoPersonalizado::class, 'hito_id');
    }

    /**
     * Obtiene las etiquetas del hito.
     */
    public function etiquetas(): BelongsToMany
    {
        return $this->belongsToMany(Etiqueta::class, 'hito_etiqueta')
            ->withPivot(['orden', 'created_at'])
            ->orderBy('hito_etiqueta.orden');
    }

    /**
     * Sincroniza las etiquetas del hito y actualiza los contadores.
     */
    public function syncEtiquetas(array $etiquetaIds): void
    {
        // Obtener etiquetas actuales antes del sync
        $etiquetasAntiguas = $this->etiquetas()->pluck('etiqueta_id')->toArray();

        // Sincronizar etiquetas con ordenamiento
        $syncData = [];
        foreach ($etiquetaIds as $index => $etiquetaId) {
            $syncData[$etiquetaId] = ['orden' => $index];
        }
        $this->etiquetas()->sync($syncData);

        // Actualizar contadores de las etiquetas afectadas
        $etiquetasNuevas = $etiquetaIds;
        $etiquetasAfectadas = array_unique(array_merge($etiquetasAntiguas, $etiquetasNuevas));

        foreach ($etiquetasAfectadas as $etiquetaId) {
            $etiqueta = Etiqueta::find($etiquetaId);
            if ($etiqueta) {
                $etiqueta->recalcularUsos();
            }
        }
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
     * Verifica si el hito tiene hijos (jerarquía).
     */
    public function getTieneHijosAttribute(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Obtiene la ruta completa de la jerarquía.
     */
    public function getRutaCompletaAttribute(): string
    {
        if (!$this->parent_id) {
            return $this->nombre;
        }

        $ruta = [];
        $hito = $this;

        while ($hito) {
            array_unshift($ruta, $hito->nombre);
            $hito = $hito->parent;
        }

        return implode(' / ', $ruta);
    }

    /**
     * Obtiene todos los ancestros del hito.
     */
    public function getAncestros(): Collection
    {
        $ancestros = collect();
        $padre = $this->parent;

        while ($padre) {
            $ancestros->push($padre);
            $padre = $padre->parent;
        }

        return $ancestros;
    }

    /**
     * Obtiene todos los descendientes del hito.
     */
    public function getDescendientes(): Collection
    {
        $descendientes = collect();
        $queue = collect([$this]);

        while ($queue->isNotEmpty()) {
            $hito = $queue->shift();
            $hijos = $hito->children;

            foreach ($hijos as $hijo) {
                $descendientes->push($hijo);
                $queue->push($hijo);
            }
        }

        return $descendientes;
    }

    /**
     * Verifica si el hito es hijo de otro.
     */
    public function esHijoDe($hito): bool
    {
        if (!$hito) return false;

        $hitoId = $hito instanceof self ? $hito->id : $hito;
        return $this->parent_id == $hitoId;
    }

    /**
     * Verifica si el hito es ancestro de otro.
     */
    public function esAncestroDe($hito): bool
    {
        if (!$hito) return false;

        $hitoObj = $hito instanceof self ? $hito : self::find($hito);
        if (!$hitoObj) return false;

        return $hitoObj->getAncestros()->contains('id', $this->id);
    }

    /**
     * Calcula y actualiza el nivel del hito.
     */
    public function recalcularNivel(): void
    {
        $nivel = 0;
        $padre = $this->parent;

        while ($padre) {
            $nivel++;
            $padre = $padre->parent;
        }

        $this->nivel = $nivel;
        $this->saveQuietly();
    }

    /**
     * Calcula y actualiza la ruta completa.
     */
    public function recalcularRuta(): void
    {
        $ruta = [];
        $hito = $this;

        while ($hito) {
            array_unshift($ruta, $hito->id);
            $hito = $hito->parent;
        }

        $this->ruta = implode('/', $ruta);
        $this->saveQuietly();
    }

    /**
     * Valida que no se creen ciclos en la jerarquía.
     */
    public function puedeSerHijoDe($padreId): bool
    {
        if (!$padreId) return true;
        if ($padreId == $this->id) return false;

        // Verificar que el padre propuesto no sea descendiente de este hito
        $descendientes = $this->getDescendientes();
        return !$descendientes->contains('id', $padreId);
    }

    /**
     * Obtiene los valores de campos personalizados formateados.
     */
    public function getCamposPersonalizadosValues(): array
    {
        $valores = [];

        foreach ($this->camposPersonalizados as $valor) {
            $valores[$valor->campo_personalizado_id] = $valor->valor;
        }

        return $valores;
    }

    /**
     * Guarda los valores de campos personalizados.
     */
    public function saveCamposPersonalizados(array $valores): void
    {
        foreach ($valores as $campoId => $valor) {
            ValorCampoPersonalizado::updateOrCreate(
                [
                    'hito_id' => $this->id,
                    'campo_personalizado_id' => $campoId
                ],
                ['valor' => $valor]
            );
        }
    }

    /**
     * Calcula y actualiza el porcentaje completado basado en los entregables.
     * También dispara el recálculo del progreso del proyecto padre.
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

        // Notificar al proyecto para que recalcule su progreso
        // Como porcentaje_completado es un accessor, se calculará automáticamente
        // la próxima vez que se acceda al proyecto
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
     * Scope para hitos raíz (sin padre).
     */
    public function scopeRaices($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope para hitos por nivel de jerarquía.
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Scope para incluir jerarquía completa.
     */
    public function scopeConJerarquia($query)
    {
        return $query->with(['parent', 'children', 'proyecto']);
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

    /**
     * Boot del modelo para manejar jerarquía automáticamente.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Calcular nivel y ruta antes de crear
            if ($model->parent_id) {
                $padre = static::find($model->parent_id);
                if ($padre) {
                    $model->nivel = $padre->nivel + 1;
                    $model->ruta = $padre->ruta . '/' . $model->id;
                } else {
                    $model->nivel = 0;
                    $model->ruta = (string) $model->id;
                }
            } else {
                $model->nivel = 0;
                $model->ruta = (string) $model->id;
            }
        });

        static::updating(function ($model) {
            // Validar que no se creen ciclos en la jerarquía
            if ($model->isDirty('parent_id')) {
                if (!$model->puedeSerHijoDe($model->parent_id)) {
                    throw new \Exception('No se puede crear un ciclo en la jerarquía de hitos');
                }

                // Calcular nuevo nivel y ruta antes de actualizar
                if ($model->parent_id) {
                    $padre = static::find($model->parent_id);
                    if ($padre) {
                        $model->nivel = $padre->nivel + 1;
                        $model->ruta = $padre->ruta . '/' . $model->id;
                    }
                } else {
                    $model->nivel = 0;
                    $model->ruta = (string) $model->id;
                }
            }
        });

        static::updated(function ($model) {
            // Solo recalcular descendientes cuando cambie el padre
            if ($model->wasChanged('parent_id')) {
                // Recalcular niveles y rutas de todos los descendientes
                foreach ($model->getDescendientes() as $descendiente) {
                    $descendiente->recalcularNivel();
                    $descendiente->recalcularRuta();
                }
            }
        });
    }
}