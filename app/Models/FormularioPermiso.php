<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormularioPermiso extends Model
{
    use HasFactory, HasTenant;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'formulario_permisos';

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'formulario_id',
        'role_id',
        'usuario_id',
        'tipo_permiso',
        'configuracion',
        'valido_desde',
        'valido_hasta',
        'activo',
        'otorgado_por',
        'notas',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'configuracion' => 'array',
        'activo' => 'boolean',
        'valido_desde' => 'datetime',
        'valido_hasta' => 'datetime',
    ];

    /**
     * Los atributos que deben ser mutados a fechas.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'valido_desde',
        'valido_hasta',
    ];

    /**
     * Obtener el formulario asociado.
     */
    public function formulario()
    {
        return $this->belongsTo(Formulario::class);
    }

    /**
     * Obtener el rol asociado.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Obtener el usuario asociado.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el usuario que otorgó el permiso.
     */
    public function otorgadoPor()
    {
        return $this->belongsTo(User::class, 'otorgado_por');
    }

    /**
     * Scope para permisos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para permisos vigentes.
     */
    public function scopeVigentes($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valido_desde')
              ->orWhere('valido_desde', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valido_hasta')
              ->orWhere('valido_hasta', '>=', $now);
        });
    }

    /**
     * Verificar si el permiso está vigente.
     */
    public function estaVigente(): bool
    {
        if (!$this->activo) {
            return false;
        }
        
        $now = now();
        
        if ($this->valido_desde && $this->valido_desde > $now) {
            return false;
        }
        
        if ($this->valido_hasta && $this->valido_hasta < $now) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtener el tipo de destinatario.
     */
    public function getTipoDestinatario(): string
    {
        if ($this->usuario_id) {
            return 'usuario';
        }
        
        if ($this->role_id) {
            return 'rol';
        }
        
        return 'desconocido';
    }

    /**
     * Obtener el nombre del destinatario.
     */
    public function getNombreDestinatario(): string
    {
        if ($this->usuario) {
            return $this->usuario->name;
        }
        
        if ($this->role) {
            return $this->role->display_name ?? $this->role->name;
        }
        
        return 'Desconocido';
    }

    /**
     * Obtener el label del tipo de permiso.
     */
    public function getTipoPermisoLabel(): string
    {
        return match($this->tipo_permiso) {
            'ver' => 'Ver',
            'llenar' => 'Llenar',
            'editar_respuesta' => 'Editar respuesta',
            'ver_respuestas' => 'Ver respuestas',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener el color del tipo de permiso.
     */
    public function getTipoPermisoColor(): string
    {
        return match($this->tipo_permiso) {
            'ver' => 'blue',
            'llenar' => 'green',
            'editar_respuesta' => 'yellow',
            'ver_respuestas' => 'purple',
            default => 'gray',
        };
    }
}