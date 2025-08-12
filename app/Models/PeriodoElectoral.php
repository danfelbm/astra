<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PeriodoElectoral extends Model
{
    use HasTenant;
    protected $table = 'periodos_electorales';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'activo' => 'boolean',
    ];

    // Scopes para filtrar por estado temporal
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_inicio', '<=', $now)
                    ->where('fecha_fin', '>=', $now)
                    ->where('activo', true);
    }

    public function scopeFuturos(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_inicio', '>', $now)
                    ->where('activo', true);
    }

    public function scopePasados(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_fin', '<', $now);
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->where(function ($subQ) use ($now) {
                // Vigentes
                $subQ->where('fecha_inicio', '<=', $now)
                     ->where('fecha_fin', '>=', $now);
            })->orWhere(function ($subQ) use ($now) {
                // Futuros
                $subQ->where('fecha_inicio', '>', $now);
            });
        })->where('activo', true);
    }

    public function scopeOrdenadosCronologicamente(Builder $query): Builder
    {
        return $query->orderBy('fecha_inicio', 'asc');
    }

    // Métodos para determinar el estado temporal
    public function getEstado(): string
    {
        $now = Carbon::now();

        if ($this->fecha_fin < $now) {
            return 'pasado';
        }

        if ($this->fecha_inicio <= $now && $this->fecha_fin >= $now) {
            return 'vigente';
        }

        return 'futuro';
    }

    public function estaVigente(): bool
    {
        return $this->getEstado() === 'vigente';
    }

    public function esFuturo(): bool
    {
        return $this->getEstado() === 'futuro';
    }

    public function esPasado(): bool
    {
        return $this->getEstado() === 'pasado';
    }

    public function estaEnRango(Carbon $fecha): bool
    {
        return $fecha >= $this->fecha_inicio && $fecha <= $this->fecha_fin;
    }

    // Métodos helper para información adicional
    public function getDuracion(): string
    {
        $duracion = $this->fecha_inicio->diffInDays($this->fecha_fin);
        
        if ($duracion < 30) {
            return $duracion . ' día' . ($duracion !== 1 ? 's' : '');
        }

        $meses = intval($duracion / 30);
        $diasRestantes = $duracion % 30;

        $resultado = $meses . ' mes' . ($meses !== 1 ? 'es' : '');
        
        if ($diasRestantes > 0) {
            $resultado .= ' y ' . $diasRestantes . ' día' . ($diasRestantes !== 1 ? 's' : '');
        }

        return $resultado;
    }

    public function getDiasRestantes(): int
    {
        if ($this->esPasado()) {
            return 0;
        }

        $now = Carbon::now();

        if ($this->esFuturo()) {
            return $now->diffInDays($this->fecha_inicio);
        }

        // Es vigente
        return $now->diffInDays($this->fecha_fin);
    }

    public function getEstadoLabel(): string
    {
        switch ($this->getEstado()) {
            case 'vigente':
                return 'Vigente';
            case 'futuro':
                return 'Próximo';
            case 'pasado':
                return 'Finalizado';
            default:
                return 'Desconocido';
        }
    }

    public function getEstadoColor(): string
    {
        switch ($this->getEstado()) {
            case 'vigente':
                return 'bg-green-100 text-green-800';
            case 'futuro':
                return 'bg-blue-100 text-blue-800';
            case 'pasado':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    // Formateo de fechas para la UI
    public function getFechaInicioFormateada(): string
    {
        return $this->fecha_inicio->format('d/m/Y H:i');
    }

    public function getFechaFinFormateada(): string
    {
        return $this->fecha_fin->format('d/m/Y H:i');
    }

    public function getRangoFechas(): string
    {
        return $this->getFechaInicioFormateada() . ' - ' . $this->getFechaFinFormateada();
    }
}
