<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Cargo extends Model
{
    use HasTenant;
    protected $fillable = [
        'parent_id',
        'nombre',
        'descripcion',
        'es_cargo',
        'activo',
    ];

    protected $casts = [
        'es_cargo' => 'boolean',
        'activo' => 'boolean',
    ];

    // Relación con el cargo padre
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'parent_id');
    }

    // Relación con cargos hijos directos
    public function children(): HasMany
    {
        return $this->hasMany(Cargo::class, 'parent_id');
    }

    // Relación recursiva con todos los descendientes
    public function descendants(): HasMany
    {
        return $this->hasMany(Cargo::class, 'parent_id')->with('descendants');
    }

    // Scopes para filtrar cargos
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeSoloCargos(Builder $query): Builder
    {
        return $query->where('es_cargo', true);
    }

    public function scopeSoloCategories(Builder $query): Builder
    {
        return $query->where('es_cargo', false);
    }

    public function scopeRaices(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Obtener la ruta jerárquica completa
    public function getRutaJerarquica(): string
    {
        $ruta = collect();
        $cargo = $this;

        while ($cargo) {
            $ruta->prepend($cargo->nombre);
            $cargo = $cargo->parent;
        }

        return $ruta->join(' → ');
    }

    // Verificar si tiene descendientes
    public function tieneDescendientes(): bool
    {
        return $this->children()->exists();
    }

    // Obtener todos los ancestros
    public function getAncestors(): array
    {
        $ancestors = [];
        $cargo = $this->parent;

        while ($cargo) {
            $ancestors[] = $cargo;
            $cargo = $cargo->parent;
        }

        return array_reverse($ancestors);
    }

    // Verificar si un cargo es descendiente de otro (prevenir ciclos)
    public function esDescendienteDe(Cargo $posibleAncestro): bool
    {
        $cargo = $this->parent;

        while ($cargo) {
            if ($cargo->id === $posibleAncestro->id) {
                return true;
            }
            $cargo = $cargo->parent;
        }

        return false;
    }
}
