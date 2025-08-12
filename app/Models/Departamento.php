<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    protected $fillable = [
        'territorio_id',
        'nombre',
        'codigo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function territorio(): BelongsTo
    {
        return $this->belongsTo(Territorio::class);
    }

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
