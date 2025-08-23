<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asamblea extends Model
{
    use HasTenant;
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'territorio_id',
        'departamento_id',
        'municipio_id',
        'localidad_id',
        'lugar',
        'quorum_minimo',
        'tipo',
        'estado',
        'activo',
        'acta_url',
        // Campos de Zoom
        'zoom_enabled',
        'zoom_meeting_id',
        'zoom_meeting_password',
        'zoom_meeting_type',
        'zoom_settings',
        'zoom_created_at',
        'zoom_join_url',
        'zoom_start_url',
        // Nuevos campos de integración
        'zoom_integration_type',
        'zoom_occurrence_ids',
        'zoom_prefix',
        'zoom_registration_open_date',
        'zoom_static_message',
        'zoom_api_message_enabled',
        'zoom_api_message',
        // Campos para consulta pública de participantes
        'public_participants_enabled',
        'public_participants_mode',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'activo' => 'boolean',
        'quorum_minimo' => 'integer',
        // Casts para campos de Zoom
        'zoom_enabled' => 'boolean',
        'zoom_api_message_enabled' => 'boolean',
        'zoom_settings' => 'array',
        'zoom_created_at' => 'datetime',
        'zoom_registration_open_date' => 'datetime',
        // Casts para campos de consulta pública
        'public_participants_enabled' => 'boolean',
        'public_participants_mode' => 'string',
    ];

    // Relación con participantes (usuarios)
    public function participantes(): BelongsToMany
    {
        $tenantId = app(\App\Services\TenantService::class)->getCurrentTenant()?->id;
        
        $relation = $this->belongsToMany(User::class, 'asamblea_usuario', 'asamblea_id', 'usuario_id')
            ->withPivot(['tenant_id', 'tipo_participacion', 'asistio', 'hora_registro', 'updated_by'])
            ->withTimestamps();
            
        // Solo aplicar filtro de tenant si existe un tenant activo
        if ($tenantId) {
            $relation->wherePivot('tenant_id', $tenantId);
        }
        
        return $relation;
    }
    
    // Relación con todos los participantes sin filtro de tenant (para administración)
    public function allParticipantes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asamblea_usuario', 'asamblea_id', 'usuario_id')
            ->withPivot(['tenant_id', 'tipo_participacion', 'asistio', 'hora_registro', 'updated_by'])
            ->withTimestamps();
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

    // Scopes para filtrar por estado
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeProgramadas(Builder $query): Builder
    {
        return $query->where('estado', 'programada')
                    ->where('activo', true);
    }

    public function scopeEnCurso(Builder $query): Builder
    {
        return $query->where('estado', 'en_curso')
                    ->where('activo', true);
    }

    public function scopeFinalizadas(Builder $query): Builder
    {
        return $query->where('estado', 'finalizada');
    }

    public function scopeCanceladas(Builder $query): Builder
    {
        return $query->where('estado', 'cancelada');
    }

    public function scopeFuturas(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_inicio', '>', $now)
                    ->where('estado', '!=', 'cancelada')
                    ->where('activo', true);
    }

    public function scopePasadas(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_fin', '<', $now);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('fecha_inicio', '<=', $now)
                    ->where('fecha_fin', '>=', $now)
                    ->where('activo', true);
    }

    // Scope para filtrar por territorio (inclusivo en cascada)
    public function scopePorTerritorio(Builder $query, $territorioId = null, $departamentoId = null, $municipioId = null, $localidadId = null): Builder
    {
        return $query->where(function($q) use ($territorioId, $departamentoId, $municipioId, $localidadId) {
            // Si el usuario tiene localidad, puede ver asambleas de su localidad específica
            if ($localidadId) {
                $q->orWhere('localidad_id', $localidadId);
            }
            
            // Si tiene municipio, puede ver asambleas de todo su municipio (sin localidad específica)
            if ($municipioId) {
                $q->orWhere(function($subQ) use ($municipioId) {
                    $subQ->where('municipio_id', $municipioId)
                         ->whereNull('localidad_id');
                });
            }
            
            // Si tiene departamento, puede ver asambleas de todo su departamento (sin municipio específico)
            if ($departamentoId) {
                $q->orWhere(function($subQ) use ($departamentoId) {
                    $subQ->where('departamento_id', $departamentoId)
                         ->whereNull('municipio_id');
                });
            }
            
            // Si tiene territorio, puede ver asambleas de todo su territorio (sin departamento específico)
            if ($territorioId) {
                $q->orWhere(function($subQ) use ($territorioId) {
                    $subQ->where('territorio_id', $territorioId)
                         ->whereNull('departamento_id');
                });
            }
        });
    }

    public function scopeOrdenadoPorFecha(Builder $query): Builder
    {
        return $query->orderBy('fecha_inicio', 'desc');
    }

    // Métodos para determinar el estado temporal
    public function getEstadoTemporal(): string
    {
        $now = Carbon::now();

        if ($this->estado === 'cancelada') {
            return 'cancelada';
        }

        if ($this->estado === 'finalizada' || $this->fecha_fin < $now) {
            return 'finalizada';
        }

        if ($this->estado === 'en_curso') {
            return 'en_curso';
        }

        if ($this->fecha_inicio <= $now && $this->fecha_fin >= $now) {
            return 'vigente';
        }

        if ($this->fecha_inicio > $now) {
            return 'futura';
        }

        return 'programada';
    }

    public function estaVigente(): bool
    {
        $now = Carbon::now();
        return $this->fecha_inicio <= $now && 
               $this->fecha_fin >= $now && 
               $this->activo && 
               !in_array($this->estado, ['cancelada', 'finalizada']);
    }

    public function esFutura(): bool
    {
        $now = Carbon::now();
        return $this->fecha_inicio > $now && 
               $this->activo && 
               $this->estado !== 'cancelada';
    }

    public function esPasada(): bool
    {
        $now = Carbon::now();
        return $this->fecha_fin < $now || $this->estado === 'finalizada';
    }

    // Métodos helper para información adicional
    public function getDuracion(): string
    {
        $duracion = $this->fecha_inicio->diffInHours($this->fecha_fin);
        
        if ($duracion < 24) {
            return $duracion . ' hora' . ($duracion !== 1 ? 's' : '');
        }

        $dias = intval($duracion / 24);
        $horasRestantes = $duracion % 24;

        $resultado = $dias . ' día' . ($dias !== 1 ? 's' : '');
        
        if ($horasRestantes > 0) {
            $resultado .= ' y ' . $horasRestantes . ' hora' . ($horasRestantes !== 1 ? 's' : '');
        }

        return $resultado;
    }

    public function getTiempoRestante(): string
    {
        if ($this->esPasada()) {
            return 'Finalizada';
        }

        $now = Carbon::now();

        if ($this->esFutura()) {
            $diff = $now->diff($this->fecha_inicio);
            if ($diff->days > 0) {
                return $diff->days . ' día' . ($diff->days !== 1 ? 's' : '');
            }
            return $diff->h . ' hora' . ($diff->h !== 1 ? 's' : '');
        }

        // Es vigente
        $diff = $now->diff($this->fecha_fin);
        return 'Termina en ' . $diff->h . ' hora' . ($diff->h !== 1 ? 's' : '');
    }

    public function getEstadoLabel(): string
    {
        return match($this->estado) {
            'programada' => 'Programada',
            'en_curso' => 'En Curso',
            'finalizada' => 'Finalizada',
            'cancelada' => 'Cancelada',
            default => 'Desconocido',
        };
    }

    public function getEstadoColor(): string
    {
        return match($this->estado) {
            'programada' => 'bg-blue-100 text-blue-800',
            'en_curso' => 'bg-green-100 text-green-800',
            'finalizada' => 'bg-gray-100 text-gray-800',
            'cancelada' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getTipoLabel(): string
    {
        return match($this->tipo) {
            'ordinaria' => 'Ordinaria',
            'extraordinaria' => 'Extraordinaria',
            default => $this->tipo,
        };
    }

    // Verificar si se alcanzó el quorum
    public function alcanzaQuorum(): bool
    {
        if (!$this->quorum_minimo) {
            return true;
        }

        // Usar allParticipantes si es super admin, participantes si no
        $relation = auth()->user()?->isSuperAdmin() ? $this->allParticipantes() : $this->participantes();
        $asistentes = $relation->wherePivot('asistio', true)->count();

        return $asistentes >= $this->quorum_minimo;
    }

    // Obtener cantidad de asistentes
    public function getAsistentesCount(): int
    {
        // Usar allParticipantes si es super admin, participantes si no
        $relation = auth()->user()?->isSuperAdmin() ? $this->allParticipantes() : $this->participantes();
        return $relation->wherePivot('asistio', true)->count();
    }

    // Obtener cantidad total de participantes invitados
    public function getParticipantesCount(): int
    {
        // Usar allParticipantes si es super admin, participantes si no
        $relation = auth()->user()?->isSuperAdmin() ? $this->allParticipantes() : $this->participantes();
        return $relation->count();
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

    // Obtener ubicación completa
    public function getUbicacionCompleta(): string
    {
        $partes = [];
        
        if ($this->lugar) {
            $partes[] = $this->lugar;
        }
        
        if ($this->localidad) {
            $partes[] = $this->localidad->nombre;
        }
        
        if ($this->municipio) {
            $partes[] = $this->municipio->nombre;
        }
        
        if ($this->departamento) {
            $partes[] = $this->departamento->nombre;
        }
        
        if ($this->territorio) {
            $partes[] = $this->territorio->nombre;
        }
        
        return implode(', ', $partes);
    }

    // Métodos para integración con Zoom
    
    /**
     * Verificar si la asamblea tiene videoconferencia habilitada
     */
    public function tieneZoomHabilitado(): bool
    {
        return $this->zoom_enabled && !empty($this->zoom_meeting_id);
    }
    
    /**
     * Verificar si la videoconferencia está disponible para unirse
     */
    public function zoomDisponibleParaUnirse(): bool
    {
        if (!$this->tieneZoomHabilitado()) {
            return false;
        }
        
        // Si es modo API y tiene fecha de apertura de inscripciones, usarla
        if ($this->zoom_integration_type === 'api' && $this->zoom_registration_open_date) {
            return $this->zoomApiRegistrationOpen();
        }
        
        // Lógica original para SDK o API sin fecha específica
        $now = now();
        $inicioPermitido = $this->fecha_inicio->copy()->subMinutes(15); // 15 minutos antes
        $finPermitido = $this->fecha_fin->copy()->addMinutes(30); // 30 minutos después
        
        return $now >= $inicioPermitido && $now <= $finPermitido;
    }
    
    /**
     * Verificar si las inscripciones de API están abiertas
     */
    public function zoomApiRegistrationOpen(): bool
    {
        if (!$this->tieneZoomHabilitado() || $this->zoom_integration_type !== 'api') {
            return false;
        }
        
        // Si no hay fecha específica, usar lógica por defecto
        if (!$this->zoom_registration_open_date) {
            $now = now();
            $inicioPermitido = $this->fecha_inicio->copy()->subMinutes(15);
            return $now >= $inicioPermitido;
        }
        
        // Usar fecha específica de apertura
        $now = now();
        $finPermitido = $this->fecha_fin->copy()->addMinutes(30); // Hasta 30 min después del fin
        
        return $now >= $this->zoom_registration_open_date && $now <= $finPermitido;
    }
    
    /**
     * Obtener el estado de la videoconferencia
     */
    public function getZoomEstado(): string
    {
        if (!$this->tieneZoomHabilitado()) {
            return 'disabled';
        }
        
        $now = now();
        $inicioPermitido = $this->fecha_inicio->subMinutes(15);
        $finPermitido = $this->fecha_fin->addMinutes(30);
        
        if ($now < $inicioPermitido) {
            return 'pending'; // Pendiente de inicio
        }
        
        if ($now > $finPermitido) {
            return 'finished'; // Finalizada
        }
        
        if ($now >= $this->fecha_inicio && $now <= $this->fecha_fin) {
            return 'active'; // En curso
        }
        
        return 'available'; // Disponible para unirse
    }
    
    /**
     * Obtener mensaje del estado de Zoom
     */
    public function getZoomEstadoMensaje(): string
    {
        $estado = $this->getZoomEstado();
        
        return match($estado) {
            'disabled' => 'Videoconferencia no habilitada',
            'pending' => 'Videoconferencia disponible 15 minutos antes del inicio',
            'available' => 'Videoconferencia disponible',
            'active' => 'Videoconferencia en curso',
            'finished' => 'Videoconferencia finalizada',
            default => 'Estado desconocido'
        };
    }
    
    /**
     * Obtener configuración de Zoom con valores por defecto
     */
    public function getZoomSettingsWithDefaults(): array
    {
        $defaults = [
            'host_video' => true,
            'participant_video' => false,
            'waiting_room' => true,
            'mute_upon_entry' => true,
            'auto_recording' => 'none'
        ];
        
        return array_merge($defaults, $this->zoom_settings ?? []);
    }
    
    /**
     * Scope para asambleas con Zoom habilitado
     */
    public function scopeConZoomHabilitado(Builder $query): Builder
    {
        return $query->where('zoom_enabled', true)
                    ->whereNotNull('zoom_meeting_id');
    }
    
    /**
     * Scope para asambleas con Zoom disponible para unirse
     */
    public function scopeZoomDisponible(Builder $query): Builder
    {
        $now = now();
        $inicioPermitido = $now->copy()->subMinutes(15);
        $finPermitido = $now->copy()->addMinutes(30);
        
        return $query->conZoomHabilitado()
                    ->where('fecha_inicio', '<=', $finPermitido)
                    ->where('fecha_fin', '>=', $inicioPermitido);
    }
    
    /**
     * Relación con registros de Zoom
     */
    public function zoomRegistrants(): HasMany
    {
        return $this->hasMany(ZoomRegistrant::class);
    }
    
    /**
     * Verificar si usa integración API
     */
    public function usesZoomApi(): bool
    {
        return $this->zoom_integration_type === 'api';
    }
    
    /**
     * Verificar si usa integración SDK
     */
    public function usesZoomSdk(): bool
    {
        return $this->zoom_integration_type === 'sdk';
    }
    
    /**
     * Verificar si usa mensaje estático en lugar de videoconferencia
     */
    public function usesStaticMessage(): bool
    {
        return $this->zoom_integration_type === 'message';
    }
    
    /**
     * Verificar si tiene mensaje API habilitado
     */
    public function hasZoomApiMessage(): bool
    {
        return $this->zoom_api_message_enabled && !empty($this->zoom_api_message);
    }
    
    /**
     * Obtener registro de un usuario específico
     */
    public function getZoomRegistrantForUser(User $user): ?ZoomRegistrant
    {
        return $this->zoomRegistrants()
                   ->where('user_id', $user->id)
                   ->first();
    }
    
    /**
     * Verificar si un usuario está registrado en Zoom
     */
    public function isUserRegisteredInZoom(User $user): bool
    {
        if ($this->usesZoomSdk()) {
            // Para SDK no hay registro previo
            return false;
        }
        
        return $this->getZoomRegistrantForUser($user) !== null;
    }
    
    /**
     * Obtener configuraciones para el tipo de integración actual
     */
    public function getZoomIntegrationConfig(): array
    {
        $baseConfig = [
            'type' => $this->zoom_integration_type,
            'enabled' => $this->zoom_enabled,
            'meeting_id' => $this->zoom_meeting_id,
            'password' => $this->zoom_meeting_password,
        ];
        
        if ($this->usesZoomApi()) {
            $baseConfig['occurrence_ids'] = $this->zoom_occurrence_ids;
            $baseConfig['join_url'] = $this->zoom_join_url;
            $baseConfig['start_url'] = $this->zoom_start_url;
        } else {
            $baseConfig['settings'] = $this->getZoomSettingsWithDefaults();
        }
        
        return $baseConfig;
    }
}
