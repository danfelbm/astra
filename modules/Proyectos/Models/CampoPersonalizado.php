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
        'tenant_id'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'opciones' => 'array',
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
        $labels = [
            'text' => 'Texto',
            'number' => 'Número',
            'date' => 'Fecha',
            'textarea' => 'Área de texto',
            'select' => 'Lista desplegable',
            'checkbox' => 'Casilla de verificación',
            'radio' => 'Botón de opción',
            'file' => 'Archivo'
        ];

        return $labels[$this->tipo] ?? $this->tipo;
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
}