<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorCampoPersonalizado extends Model
{
    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'valores_campos_personalizados';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'proyecto_id',
        'contrato_id',
        'campo_personalizado_id',
        'valor'
    ];

    /**
     * Obtiene el proyecto al que pertenece este valor.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Obtiene el contrato al que pertenece este valor.
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    /**
     * Obtiene el campo personalizado al que pertenece este valor.
     */
    public function campoPersonalizado(): BelongsTo
    {
        return $this->belongsTo(CampoPersonalizado::class);
    }

    /**
     * Obtiene el valor formateado según el tipo de campo.
     */
    public function getValorFormateadoAttribute()
    {
        if (!$this->campoPersonalizado) {
            return $this->valor;
        }

        switch ($this->campoPersonalizado->tipo) {
            case 'checkbox':
                return $this->valor == '1' ? 'Sí' : 'No';

            case 'date':
                if ($this->valor) {
                    return \Carbon\Carbon::parse($this->valor)->format('d/m/Y');
                }
                return null;

            case 'select':
            case 'radio':
                // Buscar la etiqueta en las opciones del campo
                $opciones = $this->campoPersonalizado->opciones ?? [];
                foreach ($opciones as $opcion) {
                    if (isset($opcion['value']) && $opcion['value'] == $this->valor) {
                        return $opcion['label'] ?? $this->valor;
                    }
                }
                return $this->valor;

            case 'file':
                // Retornar solo el nombre del archivo
                if ($this->valor) {
                    return basename($this->valor);
                }
                return null;

            default:
                return $this->valor;
        }
    }

    /**
     * Valida si el valor cumple con las reglas del campo.
     */
    public function esValido(): bool
    {
        if (!$this->campoPersonalizado) {
            return true;
        }

        $reglas = $this->campoPersonalizado->reglas_validacion;

        $validator = \Validator::make(
            ['valor' => $this->valor],
            ['valor' => $reglas]
        );

        return !$validator->fails();
    }

    /**
     * Scope para valores de un proyecto específico.
     */
    public function scopeParaProyecto($query, $proyectoId)
    {
        return $query->where('proyecto_id', $proyectoId);
    }

    /**
     * Scope para valores de un contrato específico.
     */
    public function scopeParaContrato($query, $contratoId)
    {
        return $query->where('contrato_id', $contratoId);
    }

    /**
     * Scope para valores de un campo específico.
     */
    public function scopeParaCampo($query, $campoId)
    {
        return $query->where('campo_personalizado_id', $campoId);
    }

    /**
     * Obtiene la entidad relacionada (proyecto o contrato).
     */
    public function entidadRelacionada()
    {
        if ($this->proyecto_id) {
            return $this->proyecto;
        }
        if ($this->contrato_id) {
            return $this->contrato;
        }
        return null;
    }

    /**
     * Obtiene el tipo de entidad.
     */
    public function getTipoEntidadAttribute(): ?string
    {
        if ($this->proyecto_id) {
            return 'proyecto';
        }
        if ($this->contrato_id) {
            return 'contrato';
        }
        return null;
    }
}