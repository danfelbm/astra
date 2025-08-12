<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Get the votaciones for the categoria.
     */
    public function votaciones()
    {
        return $this->hasMany(Votacion::class);
    }
}
