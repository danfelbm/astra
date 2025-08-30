<?php

namespace App\Models\Votaciones;

use App\Models\Asamblea\Asamblea;
use App\Models\Core\User;
use App\Models\Geografico\Territorio;
use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Votacion extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'votaciones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'categoria_id',
        'formulario_config',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'resultados_publicos',
        'fecha_publicacion_resultados',
        'limite_censo',
        'mensaje_limite_censo',
        'timezone',
        'territorios_ids',
        'departamentos_ids',
        'municipios_ids',
        'localidades_ids',
    ];

    protected function casts(): array
    {
        return [
            'formulario_config' => 'array',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'resultados_publicos' => 'boolean',
            'fecha_publicacion_resultados' => 'datetime',
            'limite_censo' => 'datetime',
            'territorios_ids' => 'array',
            'departamentos_ids' => 'array',
            'municipios_ids' => 'array',
            'localidades_ids' => 'array',
        ];
    }

    /**
     * Determina si los resultados de esta votación son visibles públicamente.
     * 
     * Lógica:
     * - Si fecha_publicacion_resultados es null: resultados visibles solo después de fecha_fin
     * - Si fecha_publicacion_resultados existe: resultados visibles después de esa fecha
     * - Usa la zona horaria configurada para la votación
     */
    public function resultadosVisibles(): bool
    {
        // Primero verificar que los resultados estén habilitados como públicos
        if (!$this->resultados_publicos) {
            return false;
        }

        $now = Carbon::now($this->timezone);
        
        // Si hay fecha específica de publicación, usar esa fecha
        if ($this->fecha_publicacion_resultados) {
            $fechaPublicacion = Carbon::parse($this->fecha_publicacion_resultados)->setTimezone($this->timezone);
            return $now >= $fechaPublicacion;
        }
        
        // Si no hay fecha específica, solo mostrar después de que termine la votación
        $fechaFin = Carbon::parse($this->fecha_fin)->setTimezone($this->timezone);
        return $now >= $fechaFin;
    }

    /**
     * Scope para obtener votaciones con resultados públicos visibles.
     * Nota: Este scope usa la zona horaria del servidor para consultas en BD.
     * Para validaciones precisas usar el método resultadosVisibles() individual.
     */
    public function scopeConResultadosVisibles($query)
    {
        // Para consultas de BD usamos UTC/servidor ya que las fechas se almacenan en UTC
        // y convertir cada timezone en SQL sería muy costoso
        return $query->where('resultados_publicos', true)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    // Caso 1: Hay fecha específica de publicación y ya pasó
                    $sub->whereNotNull('fecha_publicacion_resultados')
                        ->where('fecha_publicacion_resultados', '<=', now());
                })->orWhere(function ($sub) {
                    // Caso 2: No hay fecha específica y la votación ya terminó
                    $sub->whereNull('fecha_publicacion_resultados')
                        ->where('fecha_fin', '<=', now());
                });
            });
    }

    /**
     * Verifica si la votación está activa en este momento según su zona horaria.
     */
    public function estaActiva(): bool
    {
        $now = Carbon::now($this->timezone);
        $fechaInicio = Carbon::parse($this->fecha_inicio)->setTimezone($this->timezone);
        $fechaFin = Carbon::parse($this->fecha_fin)->setTimezone($this->timezone);
        
        return $this->estado === 'activa' && $now >= $fechaInicio && $now <= $fechaFin;
    }

    /**
     * Obtiene la hora actual en la zona horaria de la votación.
     */
    public function ahora(): Carbon
    {
        return Carbon::now($this->timezone);
    }

    /**
     * Convierte una fecha a la zona horaria de la votación.
     */
    public function enZonaHoraria($fecha): Carbon
    {
        return Carbon::parse($fecha)->setTimezone($this->timezone);
    }

    /**
     * Get the categoria that owns the votacion.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * The users that belong to the votacion.
     */
    public function votantes()
    {
        $tenantId = app(\App\Services\Core\TenantService::class)->getCurrentTenant()?->id;
        
        return $this->belongsToMany(User::class, 'votacion_usuario', 'votacion_id', 'usuario_id')
            ->withPivot(['tenant_id', 'origen_id', 'model_type'])
            ->wherePivot('tenant_id', $tenantId)
            ->withTimestamps();
    }

    /**
     * Get the votos for the votacion.
     */
    public function votos()
    {
        return $this->hasMany(Voto::class);
    }

    /**
     * Las asambleas asociadas a esta votación.
     */
    public function asambleas()
    {
        $tenantId = app(\App\Services\Core\TenantService::class)->getCurrentTenant()?->id;
        
        $relation = $this->belongsToMany(Asamblea::class, 'asamblea_votacion', 'votacion_id', 'asamblea_id')
            ->withPivot(['tenant_id'])
            ->withTimestamps();
            
        // Solo aplicar filtro de tenant si existe un tenant activo
        if ($tenantId) {
            $relation->wherePivot('tenant_id', $tenantId);
        }
        
        return $relation;
    }

    /**
     * Get territorios asociados a esta votación.
     */
    public function territorios()
    {
        if (empty($this->territorios_ids)) {
            return collect();
        }
        return Territorio::whereIn('id', $this->territorios_ids)->get();
    }

    /**
     * Get departamentos asociados a esta votación.
     */
    public function departamentos()
    {
        if (empty($this->departamentos_ids)) {
            return collect();
        }
        return Departamento::whereIn('id', $this->departamentos_ids)->get();
    }

    /**
     * Get municipios asociados a esta votación.
     */
    public function municipios()
    {
        if (empty($this->municipios_ids)) {
            return collect();
        }
        return Municipio::whereIn('id', $this->municipios_ids)->get();
    }

    /**
     * Get localidades asociadas a esta votación.
     */
    public function localidades()
    {
        if (empty($this->localidades_ids)) {
            return collect();
        }
        return Localidad::whereIn('id', $this->localidades_ids)->get();
    }

    /**
     * Verifica si un usuario puede participar en esta votación según su ubicación geográfica.
     */
    public function puedeParticiparPorUbicacion(User $user): bool
    {
        // Si no hay restricciones geográficas, cualquiera puede participar
        if (empty($this->territorios_ids) && empty($this->departamentos_ids) && 
            empty($this->municipios_ids) && empty($this->localidades_ids)) {
            return true;
        }

        // Verificar por localidad (más específico)
        if (!empty($this->localidades_ids) && $user->localidad_id) {
            return in_array($user->localidad_id, $this->localidades_ids);
        }

        // Verificar por municipio
        if (!empty($this->municipios_ids) && $user->municipio_id) {
            return in_array($user->municipio_id, $this->municipios_ids);
        }

        // Verificar por departamento
        if (!empty($this->departamentos_ids) && $user->departamento_id) {
            return in_array($user->departamento_id, $this->departamentos_ids);
        }

        // Verificar por territorio
        if (!empty($this->territorios_ids) && $user->territorio_id) {
            return in_array($user->territorio_id, $this->territorios_ids);
        }

        return false;
    }

    /**
     * Verifica si está dentro del límite de censo configurado.
     * Si no hay límite configurado, retorna true (sin restricción).
     */
    public function estaDentroDelLimiteCenso(): bool
    {
        if (!$this->limite_censo) {
            return true; // Sin límite configurado, siempre disponible
        }

        $now = Carbon::now($this->timezone);
        $limiteCenso = Carbon::parse($this->limite_censo)->setTimezone($this->timezone);
        
        return $now <= $limiteCenso;
    }

    /**
     * Verifica si un usuario puede participar basándose en su fecha de inscripción
     * comparada con el límite del censo de esta votación.
     * 
     * @param User $user
     * @return bool
     */
    public function puedeParticiparPorCenso(User $user): bool
    {
        if (!$this->limite_censo) {
            return true; // Sin límite configurado, todos pueden participar
        }

        // Comparar fecha de inscripción del usuario con el límite del censo
        $limiteCenso = Carbon::parse($this->limite_censo)->setTimezone($this->timezone);
        $fechaInscripcionUsuario = Carbon::parse($user->created_at)->setTimezone($this->timezone);
        
        return $fechaInscripcionUsuario <= $limiteCenso;
    }

    // ================== SCOPES ==================

    /**
     * Scope para votaciones con asambleas asociadas
     */
    public function scopeConAsambleas($query)
    {
        return $query->whereHas('asambleas');
    }

    /**
     * Scope para votaciones sin asambleas
     */
    public function scopeSinAsambleas($query)
    {
        return $query->whereDoesntHave('asambleas');
    }

    /**
     * Scope para votaciones de una asamblea específica
     */
    public function scopeDeAsamblea($query, $asambleaId)
    {
        return $query->whereHas('asambleas', function ($q) use ($asambleaId) {
            $q->where('asambleas.id', $asambleaId);
        });
    }

    /**
     * Scope para votaciones con participantes sincronizados de asamblea
     */
    public function scopeConParticipantesSincronizados($query, $asambleaId = null)
    {
        return $query->whereHas('votantes', function ($q) use ($asambleaId) {
            $q->where('model_type', 'App\Models\Asamblea\Asamblea');
            if ($asambleaId) {
                $q->where('origen_id', $asambleaId);
            }
        });
    }
}
