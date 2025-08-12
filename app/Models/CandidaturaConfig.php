<?php

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidaturaConfig extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'candidatura_config';

    protected $fillable = [
        'campos',
        'activo',
        'created_by',
        'version',
    ];

    protected $casts = [
        'campos' => 'array',
        'activo' => 'boolean',
        'version' => 'integer',
    ];

    protected $attributes = [
        'activo' => true,
        'version' => 1,
    ];

    // Relaciones
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActiva(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeUltima(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Métodos estáticos
    public static function obtenerConfiguracionActiva(): ?self
    {
        return self::activa()->ultima()->first();
    }

    public static function crearConfiguracion(array $campos, User $admin): self
    {
        // Desactivar configuración anterior
        self::where('activo', true)->update(['activo' => false]);

        return self::create([
            'campos' => $campos,
            'activo' => true,
            'created_by' => $admin->id,
            'version' => self::max('version') + 1,
        ]);
    }

    // Métodos de instancia
    public function activar(): bool
    {
        // Desactivar otras configuraciones
        self::where('activo', true)->where('id', '!=', $this->id)->update(['activo' => false]);
        
        return $this->update(['activo' => true]);
    }

    public function desactivar(): bool
    {
        return $this->update(['activo' => false]);
    }

    public function obtenerCampos(): array
    {
        return $this->campos ?? [];
    }

    public function tieneCampos(): bool
    {
        return !empty($this->campos);
    }

    public function contarCampos(): int
    {
        return count($this->obtenerCampos());
    }

    // Validar estructura de campos
    public function validarEstructuraCampos(): bool
    {
        $campos = $this->obtenerCampos();
        
        foreach ($campos as $campo) {
            // Verificar que cada campo tenga las propiedades mínimas requeridas
            if (!isset($campo['id'], $campo['type'], $campo['title'])) {
                return false;
            }
            
            // Verificar tipos válidos
            $tiposValidos = ['text', 'textarea', 'number', 'email', 'date', 'select', 'radio', 'checkbox', 'file', 'convocatoria', 'datepicker', 'disclaimer', 'repeater'];
            if (!in_array($campo['type'], $tiposValidos)) {
                return false;
            }
        }
        
        return true;
    }

    // Obtener campos por tipo
    public function obtenerCamposPorTipo(string $tipo): array
    {
        return array_filter($this->obtenerCampos(), function($campo) use ($tipo) {
            return $campo['type'] === $tipo;
        });
    }

    // Obtener campos requeridos
    public function obtenerCamposRequeridos(): array
    {
        return array_filter($this->obtenerCampos(), function($campo) {
            return $campo['required'] ?? false;
        });
    }

    // Getters útiles
    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'Activa' : 'Inactiva';
    }

    public function getColorEstadoAttribute(): string
    {
        return $this->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
    }

    public function getResumenAttribute(): string
    {
        $total = $this->contarCampos();
        $requeridos = count($this->obtenerCamposRequeridos());
        
        return "{$total} campos ({$requeridos} requeridos)";
    }

    public function getFechaCreacionAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }
}
