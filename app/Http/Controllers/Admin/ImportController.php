<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CsvImport;
use App\Models\Votacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    /**
     * Mostrar progreso de una importación específica
     */
    public function show(CsvImport $import): Response
    {
        $import->load(['votacion', 'createdBy']);
        
        return Inertia::render('Admin/Imports/Show', [
            'import' => $import,
        ]);
    }

    /**
     * Obtener estado actual de una importación (para polling)
     */
    public function status(CsvImport $import): JsonResponse
    {
        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'progress_percentage' => $import->progress_percentage,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->failed_rows,
            'error_count' => $import->error_count,
            'errors' => $import->errors,
            'duration' => $import->duration,
            'is_active' => $import->is_active,
            'is_completed' => $import->is_completed,
            'is_failed' => $import->is_failed,
            'has_errors' => $import->has_errors,
            'started_at' => $import->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $import->completed_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Listar todas las importaciones de una votación
     */
    public function index(Votacion $votacion): Response
    {
        $imports = CsvImport::forVotacion($votacion->id)
            ->with(['createdBy'])
            ->recent()
            ->paginate(10);

        return Inertia::render('Admin/Imports/Index', [
            'votacion' => $votacion,
            'imports' => $imports,
        ]);
    }

    /**
     * Obtener importaciones recientes de una votación (para Tab 3)
     */
    public function recent(Votacion $votacion): JsonResponse
    {
        $imports = CsvImport::forVotacion($votacion->id)
            ->with(['createdBy'])
            ->recent()
            ->limit(5)
            ->get();

        return response()->json($imports);
    }

    /**
     * Obtener importación activa de una votación (para íconos de progreso)
     */
    public function active(Votacion $votacion): JsonResponse
    {
        $activeImport = CsvImport::forVotacion($votacion->id)
            ->active()
            ->first();

        return response()->json($activeImport);
    }
}
