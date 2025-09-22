<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;

class Etiqueta extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'etiquetas';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'slug',
        'categoria_etiqueta_id',
        'parent_id',
        'nivel',
        'ruta',
        'color',
        'descripcion',
        'usos_count',
        'tenant_id',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'usos_count' => 'integer',
        'categoria_etiqueta_id' => 'integer',
        'parent_id' => 'integer',
        'nivel' => 'integer',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'color_efectivo',
        'color_class',
        'nombre_completo',
        'tiene_hijos',
        'ruta_completa',
    ];

    /**
     * Obtiene la categoría de la etiqueta.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEtiqueta::class, 'categoria_etiqueta_id');
    }

    /**
     * Obtiene los proyectos que tienen esta etiqueta.
     */
    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_etiqueta')
            ->withPivot(['orden', 'created_at']);
    }

    /**
     * Relación con la etiqueta padre.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relación con las etiquetas hijas.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Obtiene todos los ancestros de la etiqueta.
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Obtiene todos los descendientes de la etiqueta.
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Obtiene el color efectivo (propio o de la categoría).
     */
    public function getColorEfectivoAttribute(): string
    {
        return $this->color ?: ($this->categoria->color ?? 'gray');
    }

    /**
     * Obtiene la clase CSS del color para shadcn-vue.
     */
    public function getColorClassAttribute(): string
    {
        $color = $this->color_efectivo;

        $colorMap = [
            'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
            'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'lime' => 'bg-lime-100 text-lime-800 dark:bg-lime-900 dark:text-lime-200',
            'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
            'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            'cyan' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
            'sky' => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200',
            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200',
            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'fuchsia' => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900 dark:text-fuchsia-200',
            'pink' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
            'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200',
        ];

        return $colorMap[$color] ?? $colorMap['gray'];
    }

    /**
     * Obtiene el nombre completo con la categoría.
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->categoria ? "{$this->categoria->nombre}: {$this->nombre}" : $this->nombre;
    }

    /**
     * Verifica si la etiqueta tiene hijos.
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
        $etiqueta = $this;

        while ($etiqueta) {
            array_unshift($ruta, $etiqueta->nombre);
            $etiqueta = $etiqueta->parent;
        }

        return implode(' / ', $ruta);
    }

    /**
     * Scope para buscar por nombre o slug.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('slug', 'like', "%{$termino}%")
              ->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }

    /**
     * Scope para etiquetas por categoría.
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_etiqueta_id', $categoriaId);
    }

    /**
     * Scope para etiquetas más usadas.
     */
    public function scopeMasUsadas($query, $limite = 10)
    {
        return $query->orderByDesc('usos_count')->limit($limite);
    }

    /**
     * Scope para etiquetas no utilizadas.
     */
    public function scopeNoUtilizadas($query)
    {
        return $query->where('usos_count', 0);
    }

    /**
     * Scope para etiquetas raíz (sin padre).
     */
    public function scopeRaices($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope para etiquetas por nivel de jerarquía.
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
        return $query->with(['parent', 'children', 'categoria']);
    }

    /**
     * Genera un slug único para la etiqueta.
     */
    public static function generarSlug(string $nombre): string
    {
        $slug = \Str::slug($nombre);
        $count = static::where('slug', 'like', "{$slug}%")
            ->where('tenant_id', auth()->user()->tenant_id ?? null)
            ->count();

        return $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
    }

    /**
     * Incrementa el contador de usos.
     */
    public function incrementarUsos(): void
    {
        $this->increment('usos_count');
    }

    /**
     * Decrementa el contador de usos.
     */
    public function decrementarUsos(): void
    {
        if ($this->usos_count > 0) {
            $this->decrement('usos_count');
        }
    }

    /**
     * Actualiza el contador de usos basado en los proyectos asociados.
     */
    public function recalcularUsos(): void
    {
        $this->usos_count = $this->proyectos()->count();
        $this->save();
    }

    /**
     * Obtiene todos los ancestros de la etiqueta.
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
     * Obtiene todos los descendientes de la etiqueta.
     */
    public function getDescendientes(): Collection
    {
        $descendientes = collect();
        $queue = collect([$this]);

        while ($queue->isNotEmpty()) {
            $etiqueta = $queue->shift();
            $hijos = $etiqueta->children;

            foreach ($hijos as $hijo) {
                $descendientes->push($hijo);
                $queue->push($hijo);
            }
        }

        return $descendientes;
    }

    /**
     * Verifica si la etiqueta es hija de otra.
     */
    public function esHijoDe($etiqueta): bool
    {
        if (!$etiqueta) return false;

        $etiquetaId = $etiqueta instanceof self ? $etiqueta->id : $etiqueta;
        return $this->parent_id == $etiquetaId;
    }

    /**
     * Verifica si la etiqueta es ancestro de otra.
     */
    public function esAncestroDe($etiqueta): bool
    {
        if (!$etiqueta) return false;

        $etiquetaObj = $etiqueta instanceof self ? $etiqueta : self::find($etiqueta);
        if (!$etiquetaObj) return false;

        return $etiquetaObj->getAncestros()->contains('id', $this->id);
    }

    /**
     * Calcula y actualiza el nivel de la etiqueta.
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
        // Usar saveQuietly para evitar disparar eventos y evitar loops infinitos
        $this->saveQuietly();
    }

    /**
     * Calcula y actualiza la ruta completa.
     */
    public function recalcularRuta(): void
    {
        $ruta = [];
        $etiqueta = $this;

        while ($etiqueta) {
            array_unshift($ruta, $etiqueta->slug);
            $etiqueta = $etiqueta->parent;
        }

        $this->ruta = implode('/', $ruta);
        // Usar saveQuietly para evitar disparar eventos y evitar loops infinitos
        $this->saveQuietly();
    }

    /**
     * Valida que no se creen ciclos en la jerarquía.
     */
    public function puedeSerHijoDe($padreId): bool
    {
        if (!$padreId) return true;
        if ($padreId == $this->id) return false;

        // Verificar que el padre propuesto no sea descendiente de esta etiqueta
        $descendientes = $this->getDescendientes();
        return !$descendientes->contains('id', $padreId);
    }

    /**
     * Boot del modelo.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generarSlug($model->nombre);
            }

            // Calcular nivel y ruta antes de crear
            if ($model->parent_id) {
                $padre = static::find($model->parent_id);
                if ($padre) {
                    $model->nivel = $padre->nivel + 1;
                    $model->ruta = $padre->ruta . '/' . $model->slug;
                } else {
                    $model->nivel = 0;
                    $model->ruta = $model->slug;
                }
            } else {
                $model->nivel = 0;
                $model->ruta = $model->slug;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('nombre') && !$model->isDirty('slug')) {
                $model->slug = static::generarSlug($model->nombre);
            }

            // Validar que no se creen ciclos en la jerarquía
            if ($model->isDirty('parent_id')) {
                if (!$model->puedeSerHijoDe($model->parent_id)) {
                    throw new \Exception('No se puede crear un ciclo en la jerarquía de etiquetas');
                }

                // Calcular nuevo nivel y ruta antes de actualizar
                if ($model->parent_id) {
                    $padre = static::find($model->parent_id);
                    if ($padre) {
                        $model->nivel = $padre->nivel + 1;
                        $model->ruta = $padre->ruta . '/' . $model->slug;
                    }
                } else {
                    $model->nivel = 0;
                    $model->ruta = $model->slug;
                }
            }
        });

        static::updated(function ($model) {
            // Solo recalcular descendientes cuando cambie el padre
            // No recalcular el modelo actual para evitar loops
            if ($model->wasChanged('parent_id')) {
                // Recalcular niveles y rutas de todos los descendientes
                foreach ($model->getDescendientes() as $descendiente) {
                    $descendiente->recalcularNivel();
                    $descendiente->recalcularRuta();
                }
            }
        });

        // Al eliminar una etiqueta, actualizar los contadores
        static::deleting(function ($model) {
            // Los proyectos se desvinculan automáticamente por la FK cascade
            // Solo necesitamos registrar la actividad si es necesario
            activity()
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log("Etiqueta '{$model->nombre}' eliminada");
        });
    }

    /**
     * Busca o crea una etiqueta por nombre y categoría.
     */
    public static function buscarOCrear(string $nombre, int $categoriaId, array $atributosAdicionales = []): self
    {
        $slug = static::generarSlug($nombre);
        $tenant_id = auth()->user()->tenant_id ?? null;

        return static::firstOrCreate(
            [
                'slug' => $slug,
                'tenant_id' => $tenant_id,
            ],
            array_merge([
                'nombre' => $nombre,
                'categoria_etiqueta_id' => $categoriaId,
            ], $atributosAdicionales)
        );
    }
}