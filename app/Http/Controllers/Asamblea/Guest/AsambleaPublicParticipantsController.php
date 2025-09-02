<?php

namespace App\Http\Controllers\Asamblea\Guest;

use App\Http\Controllers\Core\GuestController;
use App\Models\Asamblea\Asamblea;
use App\Services\Asamblea\AsambleaPublicParticipantsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaPublicParticipantsController extends GuestController
{
    /**
     * Mostrar vista pública de participantes
     */
    public function show(Request $request, Asamblea $asamblea, AsambleaPublicParticipantsService $service): Response
    {
        // Verificar acceso público
        $acceso = $service->verificarAccesoPublico($asamblea);
        if (!$acceso['success']) {
            abort($acceso['status'], $acceso['message']);
        }

        // Obtener datos públicos de la asamblea
        $asambleaData = $service->getAsambleaPublicData($asamblea);

        // Renderizar vista según el modo configurado
        if ($asamblea->public_participants_mode === 'list') {
            return Inertia::render('Guest/Asambleas/ParticipantsList', [
                'asamblea' => $asambleaData,
                'filterFieldsConfig' => $service->getPublicFilterFieldsConfig(),
            ]);
        } else {
            return Inertia::render('Guest/Asambleas/ParticipantsSearch', [
                'asamblea' => $asambleaData,
            ]);
        }
    }

    /**
     * API para obtener participantes en modo listado (con caché)
     */
    public function getParticipants(Request $request, Asamblea $asamblea, AsambleaPublicParticipantsService $service)
    {
        $result = $service->getPublicParticipants($asamblea, $request);
        
        if (!$result['success']) {
            abort($result['status'], $result['message'] ?? 'Error');
        }

        return response()->json([
            'participantes' => $result['participantes'],
            'filterFieldsConfig' => $result['filterFieldsConfig'],
        ]);
    }

    /**
     * API para búsqueda de participantes (modo búsqueda)
     */
    public function search(Request $request, Asamblea $asamblea, AsambleaPublicParticipantsService $service)
    {
        // Validar entrada
        $request->validate([
            'search' => 'required|string|min:3|max:100',
        ], [
            'search.required' => 'Por favor ingrese un término de búsqueda.',
            'search.min' => 'El término de búsqueda debe tener al menos 3 caracteres.',
            'search.max' => 'El término de búsqueda no puede exceder 100 caracteres.',
        ]);

        $result = $service->searchPublicParticipant($asamblea, $request->input('search'));
        
        if (!$result['success']) {
            abort($result['status'], $result['message'] ?? 'Error');
        }

        return response()->json($result['result']);
    }

}