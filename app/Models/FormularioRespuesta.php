<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormularioRespuesta extends Model
{
    use HasFactory, HasTenant;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'formulario_respuestas';

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'formulario_id',
        'usuario_id',
        'nombre_visitante',
        'email_visitante',
        'telefono_visitante',
        'documento_visitante',
        'respuestas',
        'metadata',
        'estado',
        'codigo_confirmacion',
        'iniciado_en',
        'enviado_en',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'respuestas' => 'array',
        'metadata' => 'array',
        'iniciado_en' => 'datetime',
        'enviado_en' => 'datetime',
    ];

    /**
     * Los atributos que deben ser mutados a fechas.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'iniciado_en',
        'enviado_en',
    ];

    /**
     * Boot del modelo para eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Generar código de confirmación al crear
        static::creating(function ($respuesta) {
            if (empty($respuesta->codigo_confirmacion)) {
                $respuesta->codigo_confirmacion = static::generarCodigoUnico();
            }
            
            // Establecer iniciado_en si no está definido
            if (empty($respuesta->iniciado_en)) {
                $respuesta->iniciado_en = now();
            }
        });

        // Establecer enviado_en al cambiar a estado enviado
        static::updating(function ($respuesta) {
            if ($respuesta->isDirty('estado') && $respuesta->estado === 'enviado' && empty($respuesta->enviado_en)) {
                $respuesta->enviado_en = now();
            }
        });
    }

    /**
     * Generar un código único de confirmación.
     */
    protected static function generarCodigoUnico(): string
    {
        do {
            $codigo = strtoupper(Str::random(2)) . '-' . 
                     str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT) . '-' . 
                     strtoupper(Str::random(2));
        } while (static::where('codigo_confirmacion', $codigo)->exists());
        
        return $codigo;
    }

    /**
     * Obtener el formulario asociado.
     */
    public function formulario()
    {
        return $this->belongsTo(Formulario::class);
    }

    /**
     * Obtener el usuario que respondió (si aplica).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para respuestas enviadas.
     */
    public function scopeEnviadas($query)
    {
        return $query->where('estado', 'enviado');
    }

    /**
     * Scope para borradores.
     */
    public function scopeBorradores($query)
    {
        return $query->where('estado', 'borrador');
    }

    /**
     * Obtener el nombre del respondiente.
     */
    public function getNombreRespondiente(): string
    {
        if ($this->usuario) {
            return $this->usuario->name;
        }
        
        return $this->nombre_visitante ?? 'Anónimo';
    }

    /**
     * Obtener el email del respondiente.
     */
    public function getEmailRespondiente(): ?string
    {
        if ($this->usuario) {
            return $this->usuario->email;
        }
        
        return $this->email_visitante;
    }

    /**
     * Obtener el documento del respondiente.
     */
    public function getDocumentoRespondiente(): ?string
    {
        if ($this->usuario) {
            return $this->usuario->documento_identidad;
        }
        
        return $this->documento_visitante;
    }

    /**
     * Verificar si la respuesta es de un visitante.
     */
    public function esDeVisitante(): bool
    {
        return $this->usuario_id === null;
    }

    /**
     * Obtener el tiempo de llenado en minutos.
     */
    public function getTiempoLlenado(): ?int
    {
        if (!$this->iniciado_en || !$this->enviado_en) {
            return null;
        }
        
        return $this->iniciado_en->diffInMinutes($this->enviado_en);
    }

    /**
     * Obtener el tiempo de llenado formateado.
     */
    public function getTiempoLlenadoFormateado(): ?string
    {
        $minutos = $this->getTiempoLlenado();
        
        if ($minutos === null) {
            return null;
        }
        
        if ($minutos < 1) {
            return 'Menos de 1 minuto';
        }
        
        if ($minutos < 60) {
            return $minutos . ' ' . ($minutos === 1 ? 'minuto' : 'minutos');
        }
        
        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;
        
        $resultado = $horas . ' ' . ($horas === 1 ? 'hora' : 'horas');
        
        if ($minutosRestantes > 0) {
            $resultado .= ' y ' . $minutosRestantes . ' ' . ($minutosRestantes === 1 ? 'minuto' : 'minutos');
        }
        
        return $resultado;
    }

    /**
     * Obtener valor de una respuesta específica.
     */
    public function getRespuesta($campoId)
    {
        return $this->respuestas[$campoId] ?? null;
    }

    /**
     * Exportar respuestas a formato CSV.
     */
    public function exportarACsv(): array
    {
        $datos = [
            'codigo_confirmacion' => $this->codigo_confirmacion,
            'fecha_envio' => $this->enviado_en?->format('Y-m-d H:i:s'),
            'nombre' => $this->getNombreRespondiente(),
            'email' => $this->getEmailRespondiente(),
            'documento' => $this->getDocumentoRespondiente(),
            'tiempo_llenado' => $this->getTiempoLlenadoFormateado(),
        ];
        
        // Agregar las respuestas dinámicas
        if ($this->formulario && $this->formulario->configuracion_campos) {
            foreach ($this->formulario->configuracion_campos as $campo) {
                $valor = $this->getRespuesta($campo['id']);
                
                // Formatear valores especiales
                if (is_array($valor)) {
                    $valor = implode(', ', $valor);
                } elseif (is_bool($valor)) {
                    $valor = $valor ? 'Sí' : 'No';
                }
                
                $datos[$campo['title']] = $valor;
            }
        }
        
        return $datos;
    }
}