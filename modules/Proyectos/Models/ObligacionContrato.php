<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Models\User;
use Carbon\Carbon;

class ObligacionContrato extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'obligaciones_contrato';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'contrato_id',
        'parent_id',
        'titulo',
        'descripcion',
        // Campos deprecados - mantenidos por compatibilidad
        'fecha_vencimiento',
        'estado',
        'prioridad',
        'orden',
        'nivel',
        'path',
        // responsable_id eliminado - se obtiene desde contrato
        'porcentaje_cumplimiento',
        'notas_cumplimiento',
        'cumplido_at',
        'cumplido_por',
        // Campos activos
        'archivos_adjuntos',
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
        'fecha_vencimiento' => 'date:Y-m-d',
        'cumplido_at' => 'datetime',
        'archivos_adjuntos' => 'array',
        'orden' => 'integer',
        'nivel' => 'integer',
        'porcentaje_cumplimiento' => 'integer',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        // Campos deprecados - comentados para mejorar performance
        // 'estado_label',
        // 'estado_color',
        // 'prioridad_label',
        // 'prioridad_color',
        // 'dias_restantes',
        // 'esta_vencida',
        // 'esta_proxima_vencer',
        'tiene_hijos',
        'total_hijos',
        // 'hijos_completados',
        'ruta_completa'
    ];

    /**
     * Boot del modelo para manejar eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear una obligación, actualizar el path y nivel
        static::creating(function ($obligacion) {
            if ($obligacion->parent_id) {
                $parent = self::find($obligacion->parent_id);
                if ($parent) {
                    $obligacion->nivel = $parent->nivel + 1;
                    $obligacion->path = $parent->path ? $parent->path . '.' . $parent->id : (string)$parent->id;
                }
            } else {
                $obligacion->nivel = 0;
                $obligacion->path = null;
            }

            // Asignar el siguiente orden disponible
            if (!$obligacion->orden) {
                $maxOrden = self::where('contrato_id', $obligacion->contrato_id)
                    ->where('parent_id', $obligacion->parent_id)
                    ->max('orden') ?? 0;
                $obligacion->orden = $maxOrden + 1;
            }
        });

        // Al actualizar, si cambia el parent, actualizar path y nivel
        static::updating(function ($obligacion) {
            if ($obligacion->isDirty('parent_id')) {
                if ($obligacion->parent_id) {
                    $parent = self::find($obligacion->parent_id);
                    if ($parent) {
                        $obligacion->nivel = $parent->nivel + 1;
                        $obligacion->path = $parent->path ? $parent->path . '.' . $parent->id : (string)$parent->id;
                    }
                } else {
                    $obligacion->nivel = 0;
                    $obligacion->path = null;
                }

                // Actualizar también todos los hijos
                $obligacion->actualizarPathHijos();
            }
        });

        // Al eliminar, actualizar el orden de los hermanos
        static::deleted(function ($obligacion) {
            self::where('contrato_id', $obligacion->contrato_id)
                ->where('parent_id', $obligacion->parent_id)
                ->where('orden', '>', $obligacion->orden)
                ->decrement('orden');
        });
    }

    /**
     * Obtiene el contrato al que pertenece la obligación.
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    /**
     * Obtiene la obligación padre.
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Obtiene las obligaciones hijas.
     */
    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('orden');
    }

    /**
     * Obtiene todos los descendientes (recursivo).
     */
    public function descendientes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
                    ->with('descendientes')
                    ->orderBy('orden');
    }

    // Relaciones de responsable y cumplido eliminadas - campos deprecados

    /**
     * Obtiene el usuario que creó la obligación.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó la obligación.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene las evidencias asociadas a la obligación.
     */
    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class, 'obligacion_id');
    }

    /**
     * Obtiene las evidencias aprobadas de la obligación.
     */
    public function evidenciasAprobadas(): HasMany
    {
        return $this->hasMany(Evidencia::class, 'obligacion_id')->where('estado', 'aprobada');
    }

    // Métodos de estado y prioridad eliminados - campos deprecados

    /**
     * Verifica si tiene hijos.
     */
    public function getTieneHijosAttribute(): bool
    {
        return $this->hijos()->exists();
    }

    /**
     * Obtiene el total de hijos directos.
     */
    public function getTotalHijosAttribute(): int
    {
        return $this->hijos()->count();
    }

    // Método getHijosCompletados eliminado - campo estado deprecado

    /**
     * Obtiene la ruta completa de la obligación (breadcrumb).
     */
    public function getRutaCompletaAttribute(): array
    {
        $ruta = [];

        if ($this->path) {
            $ids = explode('.', $this->path);
            $obligaciones = self::whereIn('id', $ids)->orderByRaw("FIELD(id, " . implode(',', $ids) . ")")->get();

            foreach ($obligaciones as $obligacion) {
                $ruta[] = [
                    'id' => $obligacion->id,
                    'titulo' => $obligacion->titulo
                ];
            }
        }

        // Añadir la obligación actual
        $ruta[] = [
            'id' => $this->id,
            'titulo' => $this->titulo
        ];

        return $ruta;
    }

    // Métodos de cumplimiento eliminados - campos deprecados

    /**
     * Actualiza el path de todos los hijos cuando se mueve la obligación.
     */
    protected function actualizarPathHijos(): void
    {
        $hijos = $this->hijos;
        foreach ($hijos as $hijo) {
            $hijo->path = $this->path ? $this->path . '.' . $this->id : (string)$this->id;
            $hijo->nivel = $this->nivel + 1;
            $hijo->save();

            // Recursivamente actualizar los hijos de los hijos
            $hijo->actualizarPathHijos();
        }
    }

    /**
     * Reordena las obligaciones dentro del mismo nivel.
     */
    public static function reordenar(int $contratoId, ?int $parentId, array $ordenIds): void
    {
        foreach ($ordenIds as $orden => $obligacionId) {
            self::where('id', $obligacionId)
                ->where('contrato_id', $contratoId)
                ->where('parent_id', $parentId)
                ->update(['orden' => $orden + 1]);
        }
    }

    /**
     * Scope para obligaciones raíz (sin padre).
     */
    public function scopeRaiz($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scopes de estados eliminados - campos deprecados

    /**
     * Duplica la obligación con todos sus hijos.
     */
    public function duplicar(int $nuevoContratoId = null): ObligacionContrato
    {
        $nuevaObligacion = $this->replicate();
        $nuevaObligacion->titulo = $this->titulo . ' (Copia)';

        if ($nuevoContratoId) {
            $nuevaObligacion->contrato_id = $nuevoContratoId;
        }

        $nuevaObligacion->created_by = auth()->id();
        $nuevaObligacion->updated_by = auth()->id();
        $nuevaObligacion->save();

        // Duplicar hijos recursivamente
        foreach ($this->hijos as $hijo) {
            $nuevoHijo = $hijo->replicate();
            $nuevoHijo->parent_id = $nuevaObligacion->id;
            $nuevoHijo->created_by = auth()->id();
            $nuevoHijo->updated_by = auth()->id();
            $nuevoHijo->save();

            // Duplicar hijos del hijo
            $hijo->duplicarHijos($nuevoHijo);
        }

        return $nuevaObligacion;
    }

    /**
     * Duplica los hijos de una obligación (helper para duplicar).
     */
    protected function duplicarHijos(ObligacionContrato $nuevoPadre): void
    {
        foreach ($this->hijos as $hijo) {
            $nuevoHijo = $hijo->replicate();
            $nuevoHijo->parent_id = $nuevoPadre->id;
            $nuevoHijo->contrato_id = $nuevoPadre->contrato_id;
            $nuevoHijo->created_by = auth()->id();
            $nuevoHijo->updated_by = auth()->id();
            $nuevoHijo->save();

            // Recursivamente duplicar los hijos del hijo
            $hijo->duplicarHijos($nuevoHijo);
        }
    }

    /**
     * Verifica si el usuario puede completar esta obligación.
     * Usa el responsable del contrato asociado.
     */
    public function puedeSerCompletadaPor(User $user): bool
    {
        // Obtener responsable desde el contrato
        $responsableId = $this->contrato?->responsable_id;

        // Si es el responsable del contrato
        if ($responsableId && $responsableId === $user->id) {
            return true;
        }

        // Si tiene permisos de completar cualquier obligación
        if ($user->can('obligaciones.complete')) {
            return true;
        }

        // Si tiene permisos de completar propias y es el responsable del contrato
        if ($user->can('obligaciones.complete_own') && $responsableId === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene el árbol completo de obligaciones para un contrato.
     */
    public static function obtenerArbol(int $contratoId): \Illuminate\Support\Collection
    {
        return self::where('contrato_id', $contratoId)
                   ->whereNull('parent_id')
                   ->with('descendientes')
                   ->orderBy('orden')
                   ->get();
    }
}