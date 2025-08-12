<?php

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CandidaturaHistorial extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'candidatura_historiales';

    protected $fillable = [
        'candidatura_id',
        'version',
        'formulario_data',
        'configuracion_campos_en_momento',
        'estado_en_momento',
        'comentarios_admin_en_momento',
        'created_by',
        'motivo_cambio',
    ];

    protected $casts = [
        'formulario_data' => 'array',
        'configuracion_campos_en_momento' => 'array',
        'version' => 'integer',
    ];

    // Relaciones
    public function candidatura(): BelongsTo
    {
        return $this->belongsTo(Candidatura::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDeCandidatura(Builder $query, int $candidaturaId): Builder
    {
        return $query->where('candidatura_id', $candidaturaId);
    }

    public function scopeOrdenadoPorVersion(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('version', $direction);
    }

    public function scopeRecientes(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Métodos estáticos para crear historial
    public static function crearHistorial(Candidatura $candidatura, ?string $motivoCambio = null): self
    {
        return self::create([
            'candidatura_id' => $candidatura->id,
            'version' => $candidatura->version,
            'formulario_data' => $candidatura->formulario_data ?? [],
            'estado_en_momento' => $candidatura->estado,
            'comentarios_admin_en_momento' => $candidatura->comentarios_admin,
            'created_by' => Auth::id(),
            'motivo_cambio' => $motivoCambio,
        ]);
    }

    // Getters útiles
    public function getEstadoLabelAttribute(): string
    {
        return match($this->estado_en_momento) {
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente de Revisión',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            default => 'Desconocido',
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado_en_momento) {
            'borrador' => 'bg-yellow-100 text-yellow-800',
            'pendiente' => 'bg-blue-100 text-blue-800',
            'aprobado' => 'bg-green-100 text-green-800',
            'rechazado' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    // Método para obtener resumen de cambios
    public function getResumenCambiosAttribute(): string
    {
        if ($this->motivo_cambio) {
            return $this->motivo_cambio;
        }

        return "Versión {$this->version} - {$this->estado_label}";
    }

    // Método para obtener el nombre legible de un campo
    public function getNombreCampo(string $fieldId): string
    {
        if (!$this->configuracion_campos_en_momento) {
            return $fieldId;
        }

        $campo = collect($this->configuracion_campos_en_momento)->firstWhere('id', $fieldId);
        
        if ($campo && isset($campo['title'])) {
            return "{$fieldId} - {$campo['title']}";
        }

        return $fieldId;
    }

    // Método para obtener los datos del formulario con nombres legibles
    public function getFormularioDataConNombresAttribute(): array
    {
        $datosConNombres = [];
        
        foreach ($this->formulario_data as $fieldId => $valor) {
            $nombreLegible = $this->getNombreCampo($fieldId);
            $datosConNombres[$nombreLegible] = $valor;
        }

        return $datosConNombres;
    }
}
