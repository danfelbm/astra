<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convocatoria extends Model
{
    use HasTenant;
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_apertura',
        'fecha_cierre',
        'cargo_id',
        'periodo_electoral_id',
        'territorio_id',
        'departamento_id',
        'municipio_id',
        'localidad_id',
        'formulario_postulacion',
        'estado',
        'activo',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'formulario_postulacion' => 'array',
        'activo' => 'boolean',
    ];

    // Relaciones principales
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function periodoElectoral(): BelongsTo
    {
        return $this->belongsTo(PeriodoElectoral::class);
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    // Relaciones con ubicaciones geográficas
    public function territorio(): BelongsTo
    {
        return $this->belongsTo(Territorio::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function localidad(): BelongsTo
    {
        return $this->belongsTo(Localidad::class);
    }

    // Scopes para filtrar por estado temporal
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_apertura', '<=', $now)
                    ->where('fecha_cierre', '>=', $now)
                    ->where('estado', 'activa')
                    ->where('activo', true);
    }

    public function scopeFuturas(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_apertura', '>', $now)
                    ->where('activo', true);
    }

    public function scopeCerradas(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->where('fecha_cierre', '<', $now)
              ->orWhere('estado', 'cerrada');
        });
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->where(function ($subQ) use ($now) {
                // Abiertas
                $subQ->where('fecha_apertura', '<=', $now)
                     ->where('fecha_cierre', '>=', $now)
                     ->where('estado', 'activa');
            })->orWhere(function ($subQ) use ($now) {
                // Futuras
                $subQ->where('fecha_apertura', '>', $now)
                     ->where('estado', 'activa');
            });
        })->where('activo', true);
    }

    public function scopePorEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    public function scopeOrdenadosPorFecha(Builder $query): Builder
    {
        return $query->orderBy('fecha_apertura', 'desc');
    }

    // Métodos para determinar el estado temporal
    public function getEstadoTemporal(): string
    {
        $now = Carbon::now();

        if ($this->estado === 'cerrada' || $this->fecha_cierre < $now) {
            return 'cerrada';
        }

        if ($this->fecha_apertura <= $now && $this->fecha_cierre >= $now && $this->estado === 'activa') {
            return 'abierta';
        }

        if ($this->fecha_apertura > $now && $this->estado === 'activa') {
            return 'futura';
        }

        return 'borrador';
    }

    public function estaAbierta(): bool
    {
        return $this->getEstadoTemporal() === 'abierta';
    }

    public function esFutura(): bool
    {
        return $this->getEstadoTemporal() === 'futura';
    }

    public function estaCerrada(): bool
    {
        return $this->getEstadoTemporal() === 'cerrada';
    }

    public function esBorrador(): bool
    {
        return $this->estado === 'borrador';
    }

    // Validar si las fechas están dentro del periodo electoral
    public function fechasDentroDePeriodo(): bool
    {
        if (!$this->periodoElectoral) {
            return false;
        }

        return $this->periodoElectoral->estaEnRango($this->fecha_apertura) &&
               $this->periodoElectoral->estaEnRango($this->fecha_cierre);
    }

    // Métodos helper para información adicional
    public function getDuracion(): string
    {
        $duracion = $this->fecha_apertura->diffInDays($this->fecha_cierre);
        
        if ($duracion < 1) {
            $horas = $this->fecha_apertura->diffInHours($this->fecha_cierre);
            return $horas . ' hora' . ($horas !== 1 ? 's' : '');
        }
        
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
        if ($this->estaCerrada()) {
            return 0;
        }

        $now = Carbon::now();

        if ($this->esFutura()) {
            return $now->diffInDays($this->fecha_apertura);
        }

        // Está abierta
        return $now->diffInDays($this->fecha_cierre);
    }

    public function getEstadoTemporalLabel(): string
    {
        switch ($this->getEstadoTemporal()) {
            case 'abierta':
                return 'Abierta';
            case 'futura':
                return 'Próxima';
            case 'cerrada':
                return 'Cerrada';
            case 'borrador':
                return 'Borrador';
            default:
                return 'Desconocido';
        }
    }

    public function getEstadoTemporalColor(): string
    {
        switch ($this->getEstadoTemporal()) {
            case 'abierta':
                return 'bg-green-100 text-green-800';
            case 'futura':
                return 'bg-blue-100 text-blue-800';
            case 'cerrada':
                return 'bg-gray-100 text-gray-800';
            case 'borrador':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    // Formateo de fechas para la UI
    public function getFechaAperturaFormateada(): string
    {
        return $this->fecha_apertura->format('d/m/Y H:i');
    }

    public function getFechaCierreFormateada(): string
    {
        return $this->fecha_cierre->format('d/m/Y H:i');
    }

    public function getRangoFechas(): string
    {
        return $this->getFechaAperturaFormateada() . ' - ' . $this->getFechaCierreFormateada();
    }

    // Obtener información geográfica (para mostrar en la UI)
    public function getUbicacionTexto(): string
    {
        $partes = array_filter([
            $this->territorio ? $this->territorio->nombre : null,
            $this->departamento ? $this->departamento->nombre : null,
            $this->municipio ? $this->municipio->nombre : null,
            $this->localidad ? $this->localidad->nombre : null,
        ]);

        return empty($partes) ? 'Nacional' : implode(' → ', $partes);
    }

    // Métodos para integración con postulaciones
    public function tienePerfilCandidaturaEnFormulario(): bool
    {
        if (empty($this->formulario_postulacion)) {
            return false;
        }

        return collect($this->formulario_postulacion)
            ->contains('type', 'perfil_candidatura');
    }

    public function esDisponibleParaUsuario(int $userId): bool
    {
        // Verificar que esté abierta o futura
        if (!$this->estaAbierta() && !$this->esFutura()) {
            return false;
        }

        // Verificar que no tenga postulación existente del usuario
        $tienePostulacion = $this->postulaciones()
            ->where('user_id', $userId)
            ->exists();

        if ($tienePostulacion) {
            return false;
        }

        // Validar restricciones geográficas
        $usuario = \App\Models\User::find($userId);
        if (!$usuario) {
            return false;
        }
        
        // Si la convocatoria tiene restricciones geográficas, verificar que el usuario las cumpla
        // La lógica es: si hay una restricción más específica, las menos específicas deben coincidir también
        
        // Verificar localidad (más específico)
        if ($this->localidad_id) {
            // Si hay restricción de localidad, el usuario debe estar en esa localidad exacta
            if ($usuario->localidad_id !== $this->localidad_id) {
                return false;
            }
        }
        
        // Verificar municipio
        if ($this->municipio_id) {
            // Si hay restricción de municipio, el usuario debe estar en ese municipio
            if ($usuario->municipio_id !== $this->municipio_id) {
                return false;
            }
        }
        
        // Verificar departamento
        if ($this->departamento_id) {
            // Si hay restricción de departamento, el usuario debe estar en ese departamento
            if ($usuario->departamento_id !== $this->departamento_id) {
                return false;
            }
        }
        
        // Verificar territorio
        if ($this->territorio_id) {
            // Si hay restricción de territorio, el usuario debe estar en ese territorio
            if ($usuario->territorio_id !== $this->territorio_id) {
                return false;
            }
        }
        
        return true;
    }

    public function getNumeroPostulaciones(): int
    {
        return $this->postulaciones()->count();
    }

    public function getPostulacionesPorEstado(): array
    {
        return [
            'borradores' => $this->postulaciones()->borradores()->count(),
            'enviadas' => $this->postulaciones()->enviadas()->count(),
            'en_revision' => $this->postulaciones()->enRevision()->count(),
            'aceptadas' => $this->postulaciones()->aceptadas()->count(),
            'rechazadas' => $this->postulaciones()->rechazadas()->count(),
        ];
    }

    public function getPostulacionUsuario(int $userId): ?Postulacion
    {
        return $this->postulaciones()
            ->where('user_id', $userId)
            ->first();
    }
}
