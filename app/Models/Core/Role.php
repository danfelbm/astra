<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    
    // NO usar HasTenant porque los roles pueden ser del sistema (tenant_id = NULL)
    // o específicos de un tenant

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'display_name',
        'description',
        'is_system',
        'is_administrative',
        'redirect_after_login',
        'guard_name', // Agregado para Spatie
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
        'is_administrative' => 'boolean',
    ];

    /**
     * Obtener los usuarios que tienen este rol
     * NOTA: Comentado porque Spatie Permission ya provee esta relación
     * La relación users() de Spatie se usará automáticamente con la tabla model_has_roles
     */
    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'role_user')
    //                 ->withPivot('assigned_at', 'assigned_by')
    //                 ->withTimestamps();
    // }

    /**
     * Obtener los segmentos asociados a este rol
     */
    public function segments()
    {
        return $this->belongsToMany(Segment::class, 'role_segments')
                    ->withTimestamps();
    }
    



    /**
     * Scope para roles globales (sin tenant)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope para roles de un tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId)
                     ->orWhereNull('tenant_id'); // Incluir roles globales
    }

    /**
     * Determinar si es un rol del sistema (no editable)
     */
    public function isSystemRole(): bool
    {
        // Usar el campo is_system si existe, sino verificar por nombre
        if (isset($this->is_system)) {
            return $this->is_system;
        }
        
        return in_array($this->name, ['super_admin', 'admin', 'manager', 'user', 'end_customer']);
    }
    
    /**
     * Scope para obtener solo roles del sistema
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system', true);
    }
    
    /**
     * Scope para obtener roles disponibles para un tenant
     * (roles del tenant + roles del sistema copiables)
     */
    public function scopeAvailableForTenant($query, $tenantId)
    {
        return $query->where(function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhere(function($sub) {
                  $sub->whereNull('tenant_id')
                      ->where('is_system', true)
                      ->where('name', '!=', 'super_admin'); // super_admin nunca es copiable
              });
        });
    }
    
    /**
     * Crear copia de este rol para un tenant específico
     */
    public function copyForTenant($tenantId)
    {
        // Solo los roles del sistema pueden ser copiados
        if (!$this->is_system || $this->name === 'super_admin') {
            return null;
        }
        
        // Verificar si ya existe una copia para este tenant
        $exists = static::where('tenant_id', $tenantId)
                        ->where('name', $this->name)
                        ->first();
        
        if ($exists) {
            return $exists;
        }
        
        // Crear copia
        return static::create([
            'tenant_id' => $tenantId,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            // Los permisos se copiarán usando Spatie syncPermissions
            'is_system' => false, // Las copias no son roles del sistema
            'is_administrative' => $this->is_administrative, // Mantener el tipo de rol
        ]);
    }
    
    /**
     * Determinar si es un rol administrativo (puede acceder a /admin)
     */
    public function isAdministrative(): bool
    {
        return $this->is_administrative ?? true;
    }
    
    /**
     * Scope para obtener solo roles administrativos
     */
    public function scopeAdministrative($query)
    {
        return $query->where('is_administrative', true);
    }
    
    /**
     * Scope para obtener solo roles no administrativos (usuarios finales)
     */
    public function scopeNonAdministrative($query)
    {
        return $query->where('is_administrative', false);
    }
    
    /**
     * Obtener la ruta de redirección después del login
     * Si no está configurada, usa el comportamiento por defecto
     */
    public function getRedirectRoute(): string
    {
        // Si tiene configuración específica, usarla
        if ($this->redirect_after_login) {
            return $this->redirect_after_login;
        }
        
        // Si el rol no tiene ruta configurada, determinar por tipo
        // Usar el campo is_administrative para determinar si es admin
        if ($this->is_administrative) {
            return 'admin.dashboard';
        }
        
        return 'user.dashboard';
    }
}