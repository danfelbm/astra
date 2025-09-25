<?php

namespace Modules\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Modules\Asamblea\Models\Asamblea;
use Modules\Asamblea\Models\ZoomRegistrant;
use Modules\Elecciones\Models\Candidatura;
use Modules\Elecciones\Models\Cargo;
use Modules\Elecciones\Models\Postulacion;
use Modules\Formularios\Models\Formulario;
use Modules\Formularios\Models\FormularioPermiso;
use Modules\Formularios\Models\FormularioRespuesta;
use Modules\Geografico\Models\Departamento;
use Modules\Geografico\Models\Localidad;
use Modules\Geografico\Models\Municipio;
use Modules\Geografico\Models\Territorio;
use Modules\Votaciones\Models\Votacion;
use Modules\Votaciones\Models\Voto;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Contrato;
use Modules\Core\Traits\HasTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Modules\Core\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasTenant, HasRoles;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Core\Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'documento_identidad',
        'tipo_documento',
        'password',
        'tenant_id',
        'territorio_id',
        'departamento_id',
        'municipio_id',
        'localidad_id',
        'activo',
        'es_miembro',
        'cargo_id',
        'telefono',
        'direccion',
        'created_at',
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['avatar_url'];

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
            'es_miembro' => 'boolean',
        ];
    }

    /**
     * Mutator para normalizar el campo name a formato Title Case
     * Convierte nombres en MAYÚSCULAS a formato apropiado
     * Ej: "MERIELYS PEREZ ARRIETA" -> "Merielys Perez Arrieta"
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? Str::title(mb_strtolower(trim($value))) : $value
        );
    }

    /**
     * Accessor para obtener el nombre completo del tipo de documento
     */
    public function getTipoDocumentoNombreAttribute(): string
    {
        return match($this->tipo_documento) {
            'TI' => 'Tarjeta de Identidad',
            'CC' => 'Cédula de Ciudadanía',
            'CE' => 'Cédula de Extranjería',
            'PA' => 'Pasaporte',
            default => $this->tipo_documento ?? 'No especificado'
        };
    }

    /**
     * Accessor para obtener la URL del avatar
     * Retorna el avatar personalizado o genera uno con UI Avatars como fallback
     */
    public function getAvatarUrlAttribute(): string
    {
        $avatarService = app(\Modules\Core\Services\AvatarService::class);
        return $avatarService->getAvatarUrl($this);
    }

    /**
     * Verificar si el usuario tiene un avatar personalizado
     */
    public function hasCustomAvatar(): bool
    {
        return !empty($this->avatar) && \Storage::disk('public')->exists($this->avatar);
    }

    /**
     * Eliminar el avatar del usuario
     */
    public function deleteAvatar(): bool
    {
        $avatarService = app(\Modules\Core\Services\AvatarService::class);
        return $avatarService->deleteAvatar($this);
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
     * Obtiene los proyectos donde el usuario es responsable.
     */
    public function proyectosComoResponsable()
    {
        return $this->hasMany(Proyecto::class, 'responsable_id');
    }

    /**
     * Obtiene los proyectos donde el usuario es participante.
     */
    public function proyectosComoParticipante()
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_usuario', 'user_id', 'proyecto_id')
                    ->withPivot('rol')
                    ->withTimestamps();
    }

    /**
     * Obtiene los contratos donde el usuario es responsable.
     */
    public function contratosComoResponsable()
    {
        return $this->hasMany(Contrato::class, 'responsable_id');
    }

    /**
     * Obtiene los contratos donde el usuario es contraparte.
     */
    public function contratosComoContraparte()
    {
        return $this->hasMany(Contrato::class, 'contraparte_user_id');
    }

    /**
     * Obtiene los contratos donde el usuario es participante.
     */
    public function contratosComoParticipante()
    {
        return $this->belongsToMany(Contrato::class, 'contrato_usuario', 'user_id', 'contrato_id')
                    ->withPivot('rol', 'notas')
                    ->withTimestamps();
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
        $tenantId = app(\Modules\Core\Services\TenantService::class)->getCurrentTenant()?->id;
        
        return $this->belongsToMany(Asamblea::class, 'asamblea_usuario', 'usuario_id', 'asamblea_id')
            ->withPivot(['tenant_id', 'tipo_participacion', 'asistio', 'hora_registro'])
            ->wherePivot('tenant_id', $tenantId)
            ->withTimestamps();
    }

    /**
     * Relación con roles
     * NOTA: Comentado porque Spatie Permission ya provee esta relación
     * La relación roles() de Spatie se usará automáticamente con la tabla model_has_roles
     */
    // public function roles()
    // {
    //     // No aplicar el scope de tenant para poder acceder a roles globales (super_admin)
    //     return $this->belongsToMany(\Modules\Core\Models\Role::class, 'role_user')
    //                 ->withoutGlobalScope(\App\Scopes\Core\TenantScope::class)
    //                 ->withPivot('assigned_at', 'assigned_by')
    //                 ->withTimestamps();
    // }

    // Métodos wrapper eliminados - usar métodos nativos de Spatie:
    // - hasRole('role_name') - ya provisto por Spatie
    // - hasAnyRole(['role1', 'role2']) - ya provisto por Spatie
    // - hasPermissionTo('permission.name') - ya provisto por Spatie
    // - can('permission.name') - ya provisto por Spatie

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
        return $this->hasMany(Formulario::class, 'creado_por');
    }
    
    /**
     * Obtener los formularios actualizados por el usuario
     */
    public function formulariosActualizados()
    {
        return $this->hasMany(Formulario::class, 'actualizado_por');
    }
    
    /**
     * Obtener las respuestas de formularios del usuario
     */
    public function respuestasFormularios()
    {
        return $this->hasMany(FormularioRespuesta::class, 'usuario_id');
    }
    
    /**
     * Obtener los permisos de formularios del usuario
     */
    public function permisosFormularios()
    {
        return $this->hasMany(FormularioPermiso::class, 'usuario_id');
    }
    
    /**
     * Relación con registros de Zoom
     */
    public function zoomRegistrants()
    {
        return $this->hasMany(ZoomRegistrant::class);
    }
    
    /**
     * Dividir el nombre en first_name y last_name según reglas específicas
     * 
     * Reglas:
     * - 2 palabras: primera = first_name, segunda = last_name
     * - 3 palabras: primera = first_name, últimas dos = last_name
     * - 4 palabras: primeras dos = first_name, últimas dos = last_name
     * - 5+ palabras: primeras dos = first_name, resto = last_name
     */
    public function splitName(): array
    {
        $name = trim($this->name);
        
        if (empty($name)) {
            return ['first' => '', 'last' => ''];
        }
        
        $parts = array_filter(explode(' ', $name)); // Eliminar espacios vacíos
        $count = count($parts);
        
        switch($count) {
            case 1:
                // Si es solo una palabra o un carácter, usar "Participante" como apellido
                return ['first' => $parts[0], 'last' => 'Participante'];
                
            case 2:
                return ['first' => $parts[0], 'last' => $parts[1]];
                
            case 3:
                return [
                    'first' => $parts[0], 
                    'last' => $parts[1] . ' ' . $parts[2]
                ];
                
            case 4:
                return [
                    'first' => $parts[0] . ' ' . $parts[1], 
                    'last' => $parts[2] . ' ' . $parts[3]
                ];
                
            default: // 5 o más palabras
                return [
                    'first' => implode(' ', array_slice($parts, 0, 2)),
                    'last' => implode(' ', array_slice($parts, 2))
                ];
        }
    }
    
    /**
     * Obtener registro de Zoom para una asamblea específica
     */
    public function getZoomRegistrantForAsamblea(\Modules\Asamblea\Models\Asamblea $asamblea)
    {
        return $this->zoomRegistrants()
                   ->where('asamblea_id', $asamblea->id)
                   ->first();
    }
    
    /**
     * Verificar si el usuario está registrado en una asamblea de Zoom
     */
    public function isRegisteredInZoomAsamblea(\Modules\Asamblea\Models\Asamblea $asamblea): bool
    {
        return $this->getZoomRegistrantForAsamblea($asamblea) !== null;
    }
    
    /**
     * Determinar si el usuario tiene algún rol administrativo
     * Un rol es administrativo si tiene is_administrative = true
     * 
     * @return bool
     */
    public function isAdministrative(): bool
    {
        // Verificar si alguno de los roles del usuario es administrativo
        return $this->roles()->where('is_administrative', true)->exists();
    }
    
    /**
     * Determinar si el usuario tiene acceso al panel administrativo
     * Puede acceder si:
     * 1. Tiene algún rol con is_administrative = true
     * 2. Tiene el permiso 'access-admin' o 'dashboard.admin'
     * 
     * @return bool
     */
    public function hasAdministrativeAccess(): bool
    {
        // Verificar por rol administrativo o permisos específicos
        return $this->isAdministrative() || 
               $this->can('access-admin') || 
               $this->can('dashboard.admin');
    }
    
    /**
     * Obtener la ruta de redirección preferida después del login
     * Prioriza roles administrativos cuando el usuario tiene múltiples roles
     * 
     * @return string
     */
    public function getPreferredRedirectRoute(): string
    {
        // Primero buscar un rol administrativo con redirect_after_login configurado
        $adminRoleWithRedirect = $this->roles()
            ->where('is_administrative', true)
            ->whereNotNull('redirect_after_login')
            ->first();
        
        if ($adminRoleWithRedirect && $adminRoleWithRedirect->redirect_after_login) {
            return $adminRoleWithRedirect->redirect_after_login;
        }
        
        // Si no hay rol administrativo con ruta, buscar cualquier rol con ruta configurada
        $roleWithRedirect = $this->roles()
            ->whereNotNull('redirect_after_login')
            ->first();
        
        if ($roleWithRedirect && $roleWithRedirect->redirect_after_login) {
            // Si el usuario tiene acceso administrativo pero el rol no es admin,
            // redirigir al dashboard admin de todos modos
            if ($this->hasAdministrativeAccess() && !str_starts_with($roleWithRedirect->redirect_after_login, 'admin.')) {
                return 'admin.dashboard';
            }
            return $roleWithRedirect->redirect_after_login;
        }
        
        // Si no hay ruta configurada, determinar por tipo de acceso
        return $this->hasAdministrativeAccess() 
            ? 'admin.dashboard' 
            : 'user.dashboard';
    }
}
