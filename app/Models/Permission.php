<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'description', // Campo adicional personalizado
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener permisos por módulo
     * Ej: Permission::byModule('users') retorna ['users.view', 'users.create', ...]
     */
    public static function byModule(string $module): array
    {
        return static::where('name', 'like', $module . '.%')
                     ->pluck('name')
                     ->toArray();
    }

    /**
     * Obtener todos los módulos disponibles
     * Extrae el prefijo antes del punto de cada permiso
     */
    public static function getModules(): array
    {
        return static::pluck('name')
                     ->map(function ($permission) {
                         return explode('.', $permission)[0] ?? null;
                     })
                     ->filter()
                     ->unique()
                     ->values()
                     ->toArray();
    }

    /**
     * Verificar si es un permiso de módulo completo (wildcard)
     * Ej: 'users.*' retorna true, 'users.view' retorna false
     */
    public function isWildcard(): bool
    {
        return str_ends_with($this->name, '.*') || $this->name === '*';
    }

    /**
     * Obtener el módulo al que pertenece este permiso
     */
    public function getModule(): ?string
    {
        $parts = explode('.', $this->name);
        return $parts[0] ?? null;
    }

    /**
     * Obtener la acción de este permiso
     * Ej: 'users.view' retorna 'view'
     */
    public function getAction(): ?string
    {
        $parts = explode('.', $this->name);
        return $parts[1] ?? null;
    }
}