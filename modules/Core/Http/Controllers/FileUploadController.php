<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Http\Controllers\Base\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Services\NomenclaturaService;

class FileUploadController extends Controller
{
    /**
     * Carga un archivo al storage
     */
    public function upload(Request $request)
    {
        // Validar que haya al menos un archivo
        $validator = Validator::make($request->all(), [
            'files' => 'required',
            'files.*' => 'required|file|max:102400', // Máximo 100MB por archivo
            'field_id' => 'required|string',
            'module' => 'required|string|in:votaciones,convocatorias,postulaciones,candidaturas,user-updates,evidencias,contratos,campanas,comentarios',
            'folder' => 'nullable|string', // Folder personalizado opcional
            'max_size' => 'nullable|integer', // Tamaño máximo en bytes
            // Parámetros de contexto para nomenclatura de evidencias
            'proyecto_id' => 'nullable|integer|exists:proyectos,id',
            'hito_id' => 'nullable|integer',
            'entregable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedFiles = [];
        $files = $request->file('files');

        // Asegurarse de que files sea un array
        if (!is_array($files)) {
            $files = [$files];
        }

        $module = $request->input('module');
        $fieldId = $request->input('field_id');
        $customFolder = $request->input('folder'); // Folder personalizado

        // Contexto para nomenclatura de evidencias
        $proyectoId = $request->input('proyecto_id');
        $hitoId = $request->input('hito_id');
        $entregableId = $request->input('entregable_id');

        // Cargar proyecto y servicio de nomenclatura si es evidencia con contexto
        $proyecto = null;
        $nomenclaturaService = null;
        if ($module === 'evidencias' && $proyectoId) {
            $proyecto = Proyecto::find($proyectoId);
            $nomenclaturaService = app(NomenclaturaService::class);

            // Obtener hito_id del entregable si no se proporcionó directamente
            if (!$hitoId && $entregableId) {
                $entregable = Entregable::find($entregableId);
                if ($entregable) {
                    $hitoId = $entregable->hito_id;
                }
            }
        }

        foreach ($files as $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Determinar nombre y ruta según contexto
                if ($nomenclaturaService && $proyecto) {
                    // Usar nomenclatura configurada del proyecto
                    $contexto = [
                        'proyecto_id' => $proyectoId,
                        'proyecto' => $proyecto,
                        'hito_id' => $hitoId,
                        'entregable_id' => $entregableId,
                        'original' => $originalName,
                        'extension' => $extension,
                    ];

                    $patron = $proyecto->nomenclatura_archivos;
                    $fileName = $nomenclaturaService->generarNombreCompleto($patron, $contexto);

                    // Usar estructura de carpetas por proyecto/hito/entregable
                    $path = $nomenclaturaService->generarRuta($proyectoId, $hitoId, $entregableId);
                } else {
                    // Comportamiento original: nombre con slug + uniqid
                    $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . uniqid() . '.' . $extension;

                    // Determinar la ruta de almacenamiento
                    $path = $customFolder
                        ? "{$customFolder}/" . date('Y/m')
                        : "uploads/{$module}/{$fieldId}/" . date('Y/m');
                }

                // Almacenar el archivo
                $storedPath = $file->storeAs($path, $fileName, 'public');

                // Agregar información del archivo subido
                $uploadedFiles[] = [
                    'id' => uniqid(),
                    'name' => $originalName,
                    'size' => $file->getSize(),
                    'path' => $storedPath,
                    'url' => Storage::url($storedPath),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString(),
                ];

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo: ' . $originalName,
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'files' => $uploadedFiles,
            'message' => count($uploadedFiles) . ' archivo(s) subido(s) exitosamente'
        ]);
    }

    /**
     * Elimina un archivo del storage
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            
            // Verificar que el archivo existe
            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no existe'
                ], 404);
            }
            
            // Eliminar el archivo
            Storage::disk('public')->delete($path);
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descarga un archivo del storage
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            
            // Verificar que el archivo existe
            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no existe'
                ], 404);
            }
            
            // Descargar el archivo
            return Storage::disk('public')->download($path);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene la información de un archivo
     */
    public function info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            
            // Verificar que el archivo existe
            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no existe'
                ], 404);
            }
            
            // Obtener información del archivo
            $fileInfo = [
                'path' => $path,
                'url' => Storage::url($path),
                'size' => Storage::disk('public')->size($path),
                'last_modified' => Storage::disk('public')->lastModified($path),
                'mime_type' => Storage::disk('public')->mimeType($path),
            ];
            
            return response()->json([
                'success' => true,
                'file' => $fileInfo
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}