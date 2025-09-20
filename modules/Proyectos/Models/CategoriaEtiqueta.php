<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;

class CategoriaEtiqueta extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'categorias_etiquetas';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'slug',
        'color',
        'icono',
        'descripcion',
        'orden',
        'activo',
        'tenant_id',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'etiquetas_count',
        'color_class',
    ];

    /**
     * Obtiene las etiquetas de esta categoría.
     */
    public function etiquetas(): HasMany
    {
        return $this->hasMany(Etiqueta::class, 'categoria_etiqueta_id');
    }

    /**
     * Obtiene el número de etiquetas en esta categoría.
     */
    public function getEtiquetasCountAttribute(): int
    {
        return $this->etiquetas()->count();
    }

    /**
     * Obtiene la clase CSS del color para shadcn-vue.
     */
    public function getColorClassAttribute(): string
    {
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

        return $colorMap[$this->color] ?? $colorMap['gray'];
    }

    /**
     * Scope para categorías activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por el campo orden.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc')->orderBy('nombre', 'asc');
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
     * Genera un slug único para la categoría.
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
     * Boot del modelo.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generarSlug($model->nombre);
            }

            if (is_null($model->orden)) {
                $model->orden = static::where('tenant_id', $model->tenant_id)->max('orden') + 1;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('nombre') && !$model->isDirty('slug')) {
                $model->slug = static::generarSlug($model->nombre);
            }
        });
    }
}