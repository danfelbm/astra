<?php

namespace Modules\Asamblea\Services;

use Modules\Asamblea\Models\Asamblea;
use Modules\Core\Models\User;
use Modules\Asamblea\Repositories\AsambleaRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para lógica de negocio de Asambleas en espacio público de usuarios
 */
class AsambleaPublicService
{
    public function __construct(
        private AsambleaRepository $repository
    ) {}

    /**
     * Verificar acceso de un usuario a una asamblea
     * Retorna información sobre el tipo de acceso que tiene
     */
    public function verificarAccesoAsamblea(User $user, Asamblea $asamblea): array
    {
        // Verificar si es participante
        $esParticipante = $asamblea->participantes()
            ->where('usuario_id', $user->id)
            ->exists();

        if ($esParticipante) {
            return [
                'tiene_acceso' => true,
                'tipo_acceso' => 'participante',
                'es_participante' => true,
                'es_territorio' => false
            ];
        }

        // Verificar si es de su territorio
        $esDesuTerritorio = $this->esAsambleaDeTerritorio($user, $asamblea);

        return [
            'tiene_acceso' => $esDesuTerritorio,
            'tipo_acceso' => $esDesuTerritorio ? 'territorio' : 'sin_acceso',
            'es_participante' => false,
            'es_territorio' => $esDesuTerritorio
        ];
    }

    /**
     * Verificar si una asamblea pertenece al territorio del usuario
     */
    private function esAsambleaDeTerritorio(User $user, Asamblea $asamblea): bool
    {
        // Verificar por localidad (más específico)
        if ($user->localidad_id && $asamblea->localidad_id === $user->localidad_id) {
            return true;
        }

        // Verificar por municipio
        if ($user->municipio_id && $asamblea->municipio_id === $user->municipio_id) {
            return true;
        }

        // Verificar por departamento
        if ($user->departamento_id && $asamblea->departamento_id === $user->departamento_id) {
            return true;
        }

        // Verificar por territorio (más general)
        if ($user->territorio_id && $asamblea->territorio_id === $user->territorio_id) {
            return true;
        }

        return false;
    }

    /**
     * Marcar asistencia del usuario actual a la asamblea
     */
    public function marcarAsistencia(User $user, Asamblea $asamblea): array
    {
        // Verificar que el usuario sea participante
        $esParticipante = $asamblea->participantes()
            ->where('usuario_id', $user->id)
            ->exists();

        if (!$esParticipante) {
            return [
                'success' => false,
                'message' => 'No eres participante de esta asamblea',
                'status' => 403
            ];
        }

        // Verificar que la asamblea esté en curso
        if ($asamblea->estado !== 'en_curso') {
            return [
                'success' => false,
                'message' => 'La asamblea no está en curso',
                'status' => 400
            ];
        }

        // Actualizar asistencia
        $asamblea->participantes()->updateExistingPivot($user->id, [
            'asistio' => true,
            'hora_registro' => now(),
            'updated_by' => $user->id  // Auto-registro
        ]);

        return [
            'success' => true,
            'message' => 'Asistencia registrada exitosamente',
            'status' => 200
        ];
    }

    /**
     * Marcar asistencia de un participante específico (solo para moderadores)
     */
    public function marcarAsistenciaParticipante(User $moderador, Asamblea $asamblea, User $participante, bool $asistio): array
    {
        // Verificar que el usuario actual sea moderador de esta asamblea
        $esModerador = $asamblea->participantes()
            ->where('usuario_id', $moderador->id)
            ->wherePivot('tipo_participacion', 'moderador')
            ->exists();

        if (!$esModerador) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para registrar asistencia. Solo los moderadores pueden hacerlo.',
                'status' => 403
            ];
        }

        // Verificar que la asamblea esté en curso
        if ($asamblea->estado !== 'en_curso') {
            return [
                'success' => false,
                'message' => 'Solo se puede registrar asistencia cuando la asamblea está en curso',
                'status' => 400
            ];
        }

        // Verificar que el participante objetivo sea parte de la asamblea
        $esParticipante = $asamblea->participantes()
            ->where('usuario_id', $participante->id)
            ->exists();

        if (!$esParticipante) {
            return [
                'success' => false,
                'message' => 'El usuario no es participante de esta asamblea',
                'status' => 404
            ];
        }

        // Preparar datos de actualización
        $updateData = [
            'asistio' => $asistio,
            'updated_by' => $moderador->id
        ];

        // Si se marca como presente, registrar la hora
        if ($asistio) {
            $updateData['hora_registro'] = now();
        } else {
            // Si se marca como ausente, limpiar la hora de registro
            $updateData['hora_registro'] = null;
        }

        $asamblea->participantes()->updateExistingPivot($participante->id, $updateData);

        return [
            'success' => true,
            'message' => $asistio 
                ? "{$participante->name} marcado como presente" 
                : "{$participante->name} marcado como ausente",
            'status' => 200,
            'participante' => [
                'id' => $participante->id,
                'name' => $participante->name,
                'asistio' => $asistio,
                'hora_registro' => $updateData['hora_registro'] ?? null,
                'updated_by' => $moderador->id,
                'updated_by_name' => $moderador->name
            ]
        ];
    }

    /**
     * Obtener votaciones asociadas a la asamblea para un usuario específico
     */
    public function getVotacionesAsociadas(Asamblea $asamblea, User $user, bool $esParticipante): array
    {
        // Solo mostrar votaciones si es participante
        if (!$esParticipante) {
            return [];
        }

        // Obtener votaciones asociadas a la asamblea
        $votaciones = $asamblea->votaciones()
            ->whereIn('estado', ['activa', 'finalizada'])
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Transformar para incluir información de voto del usuario
        return $votaciones->map(function ($votacion) use ($user) {
            // Verificar si el usuario ya votó
            $yaVoto = $votacion->votos()
                ->where('usuario_id', $user->id)
                ->exists();

            // Verificar si puede votar
            $puedeVotar = !$yaVoto && 
                          $votacion->estado === 'activa' &&
                          now()->between($votacion->fecha_inicio, $votacion->fecha_fin);

            // Verificar si puede ver resultados
            $resultadosVisibles = $this->puedeVerResultados($votacion);

            return [
                'id' => $votacion->id,
                'titulo' => $votacion->titulo,
                'descripcion' => $votacion->descripcion,
                'categoria' => [
                    'id' => $votacion->categoria->id,
                    'nombre' => $votacion->categoria->nombre,
                ],
                'fecha_inicio' => $votacion->fecha_inicio,
                'fecha_fin' => $votacion->fecha_fin,
                'timezone' => $votacion->timezone,
                'estado' => $votacion->estado,
                'estado_visual' => $this->getEstadoVisual($votacion),
                'ya_voto' => $yaVoto,
                'puede_votar' => $puedeVotar,
                'ha_finalizado' => $votacion->estado === 'finalizada' || now()->gt($votacion->fecha_fin),
                'puede_ver_voto' => $yaVoto,
                'resultados_visibles' => $resultadosVisibles,
                'vote_processing' => false,
                'vote_status' => null,
            ];
        })->toArray();
    }

    /**
     * Verificar si se pueden ver los resultados de una votación
     */
    private function puedeVerResultados($votacion): bool
    {
        if (!$votacion->resultados_publicos) {
            return false;
        }

        if ($votacion->fecha_publicacion_resultados) {
            return now()->gte($votacion->fecha_publicacion_resultados);
        }

        return $votacion->estado === 'finalizada';
    }

    /**
     * Obtener estado visual de una votación
     */
    private function getEstadoVisual($votacion): string
    {
        if ($votacion->estado === 'activa' && now()->lt($votacion->fecha_inicio)) {
            return 'pendiente';
        }

        if ($votacion->estado === 'activa' && now()->gt($votacion->fecha_fin)) {
            return 'finalizada';
        }

        return $votacion->estado;
    }

    /**
     * Obtener detalles completos de una asamblea para un usuario específico
     */
    public function getAsambleaDetailsForUser(User $user, Asamblea $asamblea): array
    {
        // Verificar acceso
        $acceso = $this->verificarAccesoAsamblea($user, $asamblea);
        
        if (!$acceso['tiene_acceso']) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para ver esta asamblea',
                'status' => 403
            ];
        }

        // Cargar relaciones básicas
        $asamblea->load([
            'territorio', 
            'departamento', 
            'municipio', 
            'localidad'
        ]);

        // Solo cargar conteos si es participante
        $asistentesCount = 0;
        $participantesCount = 0;
        $alcanzaQuorum = false;

        if ($acceso['es_participante']) {
            $asamblea->loadCount([
                'participantes',
                'participantes as asistentes_count' => function ($query) {
                    $query->where('asamblea_usuario.asistio', true);
                }
            ]);
            
            $asistentesCount = $asamblea->asistentes_count ?? 0;
            $participantesCount = $asamblea->participantes_count ?? 0;
            $alcanzaQuorum = $asamblea->quorum_minimo ? 
                $asistentesCount >= $asamblea->quorum_minimo : true;
        }

        // Obtener información de participación
        $miParticipacion = null;
        if ($acceso['es_participante']) {
            $participante = $asamblea->participantes()
                ->where('usuario_id', $user->id)
                ->first();
                
            if ($participante) {
                $miParticipacion = [
                    'tipo' => $participante->pivot->tipo_participacion,
                    'asistio' => $participante->pivot->asistio,
                    'hora_registro' => $participante->pivot->hora_registro,
                ];
            }
        }

        // Preparar datos de asamblea
        $asambleaData = [
            'id' => $asamblea->id,
            'nombre' => $asamblea->nombre,
            'descripcion' => $asamblea->descripcion,
            'tipo' => $asamblea->tipo,
            'tipo_label' => $asamblea->getTipoLabel(),
            'estado' => $asamblea->estado,
            'estado_label' => $asamblea->getEstadoLabel(),
            'estado_color' => $asamblea->getEstadoColor(),
            'fecha_inicio' => $asamblea->fecha_inicio,
            'fecha_fin' => $asamblea->fecha_fin,
            'lugar' => $asamblea->lugar,
            'territorio' => $asamblea->territorio,
            'departamento' => $asamblea->departamento,
            'municipio' => $asamblea->municipio,
            'localidad' => $asamblea->localidad,
            'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
            'quorum_minimo' => $asamblea->quorum_minimo,
            'acta_url' => $asamblea->acta_url,
            'duracion' => $asamblea->getDuracion(),
            'tiempo_restante' => $asamblea->getTiempoRestante(),
            'rango_fechas' => $asamblea->getRangoFechas(),
            'alcanza_quorum' => $alcanzaQuorum,
            'asistentes_count' => $asistentesCount,
            'participantes_count' => $participantesCount,
            // Campos de videoconferencia
            'zoom_enabled' => $asamblea->zoom_enabled,
            'zoom_integration_type' => $asamblea->zoom_integration_type,
            'zoom_meeting_id' => $asamblea->zoom_meeting_id,
            'zoom_meeting_password' => $asamblea->zoom_meeting_password,
            'zoom_occurrence_ids' => $asamblea->zoom_occurrence_ids,
            'zoom_join_url' => $asamblea->zoom_join_url,
            'zoom_start_url' => $asamblea->zoom_start_url,
            'zoom_static_message' => $asamblea->zoom_static_message,
            'zoom_api_message_enabled' => $asamblea->zoom_api_message_enabled,
            'zoom_api_message' => $asamblea->zoom_api_message,
            'zoom_estado' => $asamblea->getZoomEstado(),
            'zoom_estado_mensaje' => $asamblea->getZoomEstadoMensaje(),
        ];

        // Obtener votaciones asociadas
        $votaciones = $this->getVotacionesAsociadas($asamblea, $user, $acceso['es_participante']);

        return [
            'success' => true,
            'status' => 200,
            'asamblea' => $asambleaData,
            'esParticipante' => $acceso['es_participante'],
            'esDesuTerritorio' => $acceso['es_territorio'],
            'miParticipacion' => $miParticipacion,
            'votaciones' => $votaciones
        ];
    }
}