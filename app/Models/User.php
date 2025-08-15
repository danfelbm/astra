<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'documento_identidad',
        'password',
        'tenant_id',
        'territorio_id',
        'departamento_id',
        'municipio_id',
        'localidad_id',
        'activo',
        'cargo_id',
        'telefono',
        'direccion',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Get the votaciones for the user.
     */
    public function votaciones()
    {
        return $this->belongsToMany(Votacion::class, 'votacion_usuario', 'usuario_id', 'votacion_id');
    }

    /**
     * Get the votos for the user.
     */
    public function votos()
    {
        return $this->hasMany(Voto::class, 'usuario_id');
    }

    /**
     * Get the territorio for the user.
     */
    public function territorio()
    {
        return $this->belongsTo(Territorio::class);
    }

    /**
     * Get the departamento for the user.
     */
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Get the municipio for the user.
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Get the localidad for the user.
     */
    public function localidad()
    {
        return $this->belongsTo(Localidad::class);
    }

    /**
     * Get the cargo for the user.
     */
    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    /**
     * Get the postulaciones for the user.
     */
    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class);
    }

    /**
     * Get the candidaturas for the user.
     */
    public function candidaturas()
    {
        return $this->hasMany(Candidatura::class);
    }

    /**
     * Get the asambleas donde el usuario es participante.
     */
    public function asambleas()
    {
        $tenantId = app(\App\Services\TenantService::class)->getCurrentTenant()?->id;
        
        return $this->belongsToMany(Asamblea::class, 'asamblea_usuario', 'usuario_id', 'asamblea_id')
            ->withPivot(['tenant_id', 'tipo_participacion', 'asistio', 'hora_registro'])
            ->wherePivot('tenant_id', $tenantId)
            ->withTimestamps();
    }

    /**
     * Relación con roles
     */
    public function roles()
    {
        // No aplicar el scope de tenant para poder acceder a roles globales (super_admin)
        return $this->belongsToMany(\App\Models\Role::class, 'role_user')
                    ->withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->withPivot('assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    /**
     * Verificar si el usuario es super administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', 'super_admin')->exists();
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->roles()->whereIn('name', ['super_admin', 'admin'])->exists();
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        // Si es super admin, tiene todos los permisos
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Verificar permisos en los roles del usuario
        foreach ($this->roles as $role) {
            // Usar el método hasPermission del rol que incluye verificación de wildcards
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Verificar si el usuario tiene algún rol administrativo
     * (roles que pueden acceder a /admin)
     */
    public function hasAdministrativeRole(): bool
    {
        // Si es super admin, siempre es administrativo
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Verificar si alguno de sus roles es administrativo
        foreach ($this->roles as $role) {
            if ($role->is_administrative) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el usuario tiene acceso a un módulo específico
     * Un usuario tiene acceso si CUALQUIERA de sus roles tiene el módulo permitido
     */
    public function hasModuleAccess(string $module): bool
    {
        // Si es super admin, tiene acceso a todos los módulos
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Verificar en cada rol del usuario
        foreach ($this->roles as $role) {
            if ($role->hasModuleAccess($module)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener todos los módulos permitidos para el usuario
     * Combina los módulos de todos sus roles
     */
    public function getAllowedModules(): array
    {
        // Si es super admin, tiene todos los módulos
        if ($this->isSuperAdmin()) {
            return ['*']; // Acceso a todo
        }
        
        $modules = [];
        foreach ($this->roles as $role) {
            $roleModules = $role->allowed_modules ?? [];
            $modules = array_merge($modules, $roleModules);
        }
        
        // Si algún rol tiene '*', el usuario tiene acceso a todo
        if (in_array('*', $modules)) {
            return ['*'];
        }
        
        // Retornar módulos únicos
        return array_unique($modules);
    }

    /**
     * Obtener número de WhatsApp formateado para el usuario
     * 
     * @return string|null
     */
    public function getWhatsAppNumber(): ?string
    {
        if (empty($this->telefono)) {
            return null;
        }

        $phone = preg_replace('/[\s\-\(\)]/', '', $this->telefono);
        
        // Si ya tiene formato internacional con +, retornar sin el +
        if (str_starts_with($phone, '+')) {
            return substr($phone, 1);
        }
        
        // Si empieza con código de país sin +
        if (strlen($phone) > 10) {
            return $phone;
        }
        
        // Si es un número local colombiano (10 dígitos), agregar código de país
        if (strlen($phone) == 10 && str_starts_with($phone, '3')) {
            return '57' . $phone;
        }
        
        // Retornar como está si no coincide con ningún patrón
        return $phone;
    }

    /**
     * Verificar si el usuario tiene un número de WhatsApp válido
     * 
     * @return bool
     */
    public function hasWhatsAppNumber(): bool
    {
        $phone = $this->getWhatsAppNumber();
        
        if (empty($phone)) {
            return false;
        }
        
        // Debe ser solo números y tener entre 10 y 15 dígitos
        return preg_match('/^\d{10,15}$/', $phone) === 1;
    }
    
    /**
     * Obtener los formularios creados por el usuario
     */
    public function formulariosCreados()
    {
        return $this->hasMany(\App\Models\Formulario::class, 'creado_por');
    }
    
    /**
     * Obtener los formularios actualizados por el usuario
     */
    public function formulariosActualizados()
    {
        return $this->hasMany(\App\Models\Formulario::class, 'actualizado_por');
    }
    
    /**
     * Obtener las respuestas de formularios del usuario
     */
    public function respuestasFormularios()
    {
        return $this->hasMany(\App\Models\FormularioRespuesta::class, 'usuario_id');
    }
    
    /**
     * Obtener los permisos de formularios del usuario
     */
    public function permisosFormularios()
    {
        return $this->hasMany(\App\Models\FormularioPermiso::class, 'usuario_id');
    }
}
