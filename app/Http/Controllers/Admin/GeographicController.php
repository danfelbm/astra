<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Territorio;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Localidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeographicController extends Controller
{
    /**
     * Get all territorios activos.
     */
    public function territorios(): JsonResponse
    {
        $territorios = Territorio::activos()
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($territorios);
    }

    /**
     * Get departamentos por territorio.
     */
    public function departamentos(Request $request): JsonResponse
    {
        $territorioIds = $request->get('territorio_ids');
        
        $query = Departamento::activos()
            ->orderBy('nombre');

        if ($territorioIds) {
            // Convertir string separado por comas a array si es necesario
            if (is_string($territorioIds)) {
                $territorioIds = explode(',', $territorioIds);
            }
            $territorioIds = array_filter($territorioIds); // Remover valores vacíos
            
            if (!empty($territorioIds)) {
                $query->whereIn('territorio_id', $territorioIds);
            }
        }

        $departamentos = $query->get(['id', 'nombre', 'territorio_id']);

        return response()->json($departamentos);
    }

    /**
     * Get municipios por departamento.
     */
    public function municipios(Request $request): JsonResponse
    {
        $departamentoIds = $request->get('departamento_ids');
        
        $query = Municipio::activos()
            ->orderBy('nombre');

        if ($departamentoIds) {
            // Convertir string separado por comas a array si es necesario
            if (is_string($departamentoIds)) {
                $departamentoIds = explode(',', $departamentoIds);
            }
            $departamentoIds = array_filter($departamentoIds); // Remover valores vacíos
            
            if (!empty($departamentoIds)) {
                $query->whereIn('departamento_id', $departamentoIds);
            }
        }

        $municipios = $query->get(['id', 'nombre', 'departamento_id']);

        return response()->json($municipios);
    }

    /**
     * Get localidades por municipio.
     */
    public function localidades(Request $request): JsonResponse
    {
        $municipioIds = $request->get('municipio_ids');
        
        $query = Localidad::activos()
            ->orderBy('nombre');

        if ($municipioIds) {
            // Convertir string separado por comas a array si es necesario
            if (is_string($municipioIds)) {
                $municipioIds = explode(',', $municipioIds);
            }
            $municipioIds = array_filter($municipioIds); // Remover valores vacíos
            
            if (!empty($municipioIds)) {
                $query->whereIn('municipio_id', $municipioIds);
            }
        }

        $localidades = $query->get(['id', 'nombre', 'municipio_id']);

        return response()->json($localidades);
    }

    /**
     * Get entidades por IDs para cargar valores seleccionados.
     */
    public function entidadesPorIds(Request $request): JsonResponse
    {
        $data = [];

        // Territorios
        if ($request->has('territorios_ids') && is_array($request->territorios_ids)) {
            $data['territorios'] = Territorio::whereIn('id', $request->territorios_ids)
                ->get(['id', 'nombre']);
        }

        // Departamentos
        if ($request->has('departamentos_ids') && is_array($request->departamentos_ids)) {
            $data['departamentos'] = Departamento::whereIn('id', $request->departamentos_ids)
                ->with('territorio:id,nombre')
                ->get(['id', 'nombre', 'territorio_id']);
        }

        // Municipios
        if ($request->has('municipios_ids') && is_array($request->municipios_ids)) {
            $data['municipios'] = Municipio::whereIn('id', $request->municipios_ids)
                ->with('departamento:id,nombre')
                ->get(['id', 'nombre', 'departamento_id']);
        }

        // Localidades
        if ($request->has('localidades_ids') && is_array($request->localidades_ids)) {
            $data['localidades'] = Localidad::whereIn('id', $request->localidades_ids)
                ->with('municipio:id,nombre')
                ->get(['id', 'nombre', 'municipio_id']);
        }

        return response()->json($data);
    }
}
