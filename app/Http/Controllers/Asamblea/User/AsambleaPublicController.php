<?php

namespace App\Http\Controllers\Asamblea\User;

use App\Http\Controllers\Core\UserController;
use App\Http\Requests\Asamblea\User\MarcarAsistenciaParticipanteRequest;
use App\Models\Asamblea\Asamblea;
use App\Models\Core\User;
use App\Models\Votaciones\Votacion;
use App\Repositories\Asamblea\AsambleaRepository;
use App\Services\Asamblea\AsambleaPublicService;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaPublicController extends UserController
{
    use HasAdvancedFilters;

    /**
     * Display a listing of asambleas for the authenticated user
     */
    public function index(Request $request, AsambleaRepository $repository): Response
    {
        // Verificar permisos generales de usuario
        abort_unless(auth()->user()->can('asambleas.view_public'), 403, 'No tienes permisos para ver asambleas públicas');
        
        $user = Auth::user();
        $asambleas = $repository->getAsambleasForUser($user, $request);

        return Inertia::render('User/Asambleas/Index', [
            'asambleas' => $asambleas,
            'filters' => $request->only(['estado', 'tipo', 'search']),
            // Props de permisos generales
            'canParticipate' => auth()->user()->can('asambleas.participate'),
            'canViewMinutes' => auth()->user()->can('asambleas.view_minutes'),
        ]);
    }

    /**
     * Display the specified asamblea
     */
    public function show(Asamblea $asamblea, AsambleaPublicService $asambleaPublicService): Response
    {
        // Verificar permisos generales de usuario
        abort_unless(auth()->user()->can('asambleas.view_public'), 403, 'No tienes permisos para ver asambleas públicas');
        
        $user = Auth::user();
        $result = $asambleaPublicService->getAsambleaDetailsForUser($user, $asamblea);
        
        // Si no tiene acceso, abortar
        if (!$result['success']) {
            abort($result['status'], $result['message']);
        }

        return Inertia::render('User/Asambleas/Show', [
            'asamblea' => $result['asamblea'],
            'esParticipante' => $result['esParticipante'],
            'esDesuTerritorio' => $result['esDesuTerritorio'],
            'miParticipacion' => $result['miParticipacion'],
            'votaciones' => $result['votaciones'],
            // Props de permisos generales
            'canParticipate' => auth()->user()->can('asambleas.participate'),
            'canViewMinutes' => auth()->user()->can('asambleas.view_minutes'),
        ]);
    }

    /**
     * Obtener participantes paginados con filtros
     */
    public function getParticipantes(Request $request, Asamblea $asamblea, AsambleaRepository $repository)
    {
        // Verificar permisos generales de usuario
        abort_unless(auth()->user()->can('asambleas.participate'), 403, 'No tienes permisos para participar en asambleas');
        
        $user = Auth::user();
        $result = $repository->getParticipantesForUser($asamblea, $user, $request);
        
        if (!$result['tiene_acceso']) {
            abort(403, 'No tienes permisos para ver los participantes de esta asamblea');
        }

        return response()->json([
            'participantes' => $result['participantes'],
            'filterFieldsConfig' => $result['filterFieldsConfig'],
        ]);
    }

    /**
     * Marcar asistencia del usuario actual a la asamblea
     */
    public function marcarAsistencia(Request $request, Asamblea $asamblea, AsambleaPublicService $asambleaPublicService)
    {
        // Verificar permisos generales de usuario
        abort_unless(auth()->user()->can('asambleas.participate'), 403, 'No tienes permisos para participar en asambleas');
        
        $user = Auth::user();
        $result = $asambleaPublicService->marcarAsistencia($user, $asamblea);
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['status']);
    }

    /**
     * Marcar asistencia de un participante específico (solo para moderadores)
     */
    public function marcarAsistenciaParticipante(MarcarAsistenciaParticipanteRequest $request, Asamblea $asamblea, User $participante, AsambleaPublicService $asambleaPublicService)
    {
        $moderador = Auth::user();
        $result = $asambleaPublicService->marcarAsistenciaParticipante(
            $moderador, 
            $asamblea, 
            $participante, 
            $request->getAsistio()
        );
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'participante' => $result['participante'] ?? null
        ], $result['status']);
    }

}
