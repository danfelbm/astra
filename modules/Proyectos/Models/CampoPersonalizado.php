<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasTenant;
use Illuminate\Support\Str;

class CampoPersonalizado extends Model
{
    use HasTenant;

    /**
     * Tipos de campo disponibles
     */
    const TIPOS_DISPONIBLES = [
        'text' => 'Texto',
        'number' => 'Número',
        'date' => 'Fecha',
        'textarea' => 'Área de texto',
        'select' => 'Lista desplegable',
        'checkbox' => 'Casilla de verificación',
        'radio' => 'Botón de opción',
        'file' => 'Archivo'
    ];

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'campos_personalizados';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'slug',
        'tipo',
        'opciones',
        'es_requerido',
        'orden',
        'activo',
        'descripcion',
        'placeholder',
        'validacion',
        'aplicar_para', // Array de entidades donde aplica el campo
        'tenant_id'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'opciones' => 'array',
        'aplicar_para' => 'array', // Array de entidades: ['proyectos', 'contratos']
        'es_requerido' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer'
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'tipo_label',
        'reglas_validacion'
    ];

    /**
     * Boot del modelo para generar automáticamente el slug.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->nombre, '_');
            }
        });
    }

    /**
     * Obtiene los valores asociados a este campo.
     */
    public function valores(): HasMany
    {
        return $this->hasMany(ValorCampoPersonalizado::class);
    }

    /**
     * Obtiene la etiqueta del tipo de campo.
     */
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS_DISPONIBLES[$this->tipo] ?? $this->tipo;
    }

    /**
     * Genera las reglas de validación para este campo.
     */
    public function getReglasValidacionAttribute(): array
    {
        $reglas = [];

        // Si es requerido
        if ($this->es_requerido) {
            $reglas[] = 'required';
        } else {
            $reglas[] = 'nullable';
        }

        // Reglas según el tipo
        switch ($this->tipo) {
            case 'number':
                $reglas[] = 'numeric';
                break;
            case 'date':
                $reglas[] = 'date';
                break;
            case 'file':
                $reglas[] = 'file';
                $reglas[] = 'max:10240'; // Máximo 10MB
                break;
            case 'text':
                $reglas[] = 'string';
                $reglas[] = 'max:255';
                break;
            case 'textarea':
                $reglas[] = 'string';
                $reglas[] = 'max:5000';
                break;
            case 'select':
            case 'radio':
                if (!empty($this->opciones)) {
                    $opciones = array_column($this->opciones, 'value');
                    $reglas[] = 'in:' . implode(',', $opciones);
                }
                break;
            case 'checkbox':
                $reglas[] = 'boolean';
                break;
        }

        // Si hay validación personalizada
        if (!empty($this->validacion)) {
            $reglas = array_merge($reglas, explode('|', $this->validacion));
        }

        return $reglas;
    }

    /**
     * Obtiene el valor para un proyecto específico.
     */
    public function getValorParaProyecto($proyectoId)
    {
        return $this->valores()
                    ->where('proyecto_id', $proyectoId)
                    ->first()?->valor;
    }

    /**
     * Establece el valor para un proyecto específico.
     */
    public function setValorParaProyecto($proyectoId, $valor)
    {
        return ValorCampoPersonalizado::updateOrCreate(
            [
                'proyecto_id' => $proyectoId,
                'campo_personalizado_id' => $this->id
            ],
            ['valor' => $valor]
        );
    }

    /**
     * Scope para campos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por el campo orden.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Scope para campos requeridos.
     */
    public function scopeRequeridos($query)
    {
        return $query->where('es_requerido', true);
    }

    /**
     * Scope para campos por tipo.
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para campos que aplican a proyectos.
     */
    public function scopeParaProyectos($query)
    {
        return $query->where(function($q) {
            $q->whereJsonContains('aplicar_para', 'proyectos')
              ->orWhereNull('aplicar_para'); // Para compatibilidad con campos existentes
        });
    }

    /**
     * Scope para campos que aplican a contratos.
     */
    public function scopeParaContratos($query)
    {
        return $query->whereJsonContains('aplicar_para', 'contratos');
    }

    /**
     * Scope para campos que aplican a una entidad específica.
     */
    public function scopeParaEntidad($query, $entidad)
    {
        return $query->where(function($q) use ($entidad) {
            $q->whereJsonContains('aplicar_para', $entidad);
            // Para compatibilidad: si el campo es null, asumimos que aplica a proyectos
            if ($entidad === 'proyectos') {
                $q->orWhereNull('aplicar_para');
            }
        });
    }

    /**
     * Verifica si el campo aplica para una entidad.
     */
    public function aplicaPara($entidad): bool
    {
        if (empty($this->aplicar_para)) {
            // Para compatibilidad: campos sin aplicar_para aplican solo a proyectos
            return $entidad === 'proyectos';
        }

        return in_array($entidad, $this->aplicar_para);
    }

    /**
     * Obtiene el valor para un contrato específico.
     */
    public function getValorParaContrato($contratoId)
    {
        return $this->valores()
                    ->where('contrato_id', $contratoId)
                    ->first()?->valor;
    }

    /**
     * Establece el valor para un contrato específico.
     */
    public function setValorParaContrato($contratoId, $valor)
    {
        return ValorCampoPersonalizado::updateOrCreate(
            [
                'contrato_id' => $contratoId,
                'campo_personalizado_id' => $this->id
            ],
            ['valor' => $valor]
        );
    }
}