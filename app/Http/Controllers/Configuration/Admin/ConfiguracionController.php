<?php

namespace App\Http\Controllers\Configuration\Admin;

use App\Http\Controllers\Core\AdminController;
use App\Services\Core\ConfiguracionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends AdminController
{
    /**
     * Mostrar la página de configuración
     */
    public function index(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('settings.view'), 403, 'No tienes permisos para ver la configuración');
        
        $configuracionLogo = ConfiguracionService::obtenerConfiguracionLogo();
        $configuracionCandidaturas = ConfiguracionService::obtenerConfiguracionCandidaturas();

        return Inertia::render('Admin/Configuracion', [
            'configuracion' => $configuracionLogo,
            'configuracionCandidaturas' => $configuracionCandidaturas,
            'canEdit' => auth()->user()->can('settings.edit'),
        ]);
    }

    /**
     * Actualizar la configuración
     */
    public function update(Request $request)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('settings.edit'), 403, 'No tienes permisos para editar la configuración');
        
        $request->validate([
            'logo_display' => 'required|in:logo_text,logo_only',
            'logo_text' => 'required|string|max:50',
            'logo_file' => 'nullable|file|mimes:jpg,jpeg,png,svg|max:2048', // 2MB max
            'remove_logo' => 'boolean',
        ]);

        $datos = [
            'logo_display' => $request->logo_display,
            'logo_text' => $request->logo_text,
        ];

        // Manejar eliminación de logo
        if ($request->boolean('remove_logo')) {
            // Eliminar el archivo físico si existe
            $configuracionActual = ConfiguracionService::obtenerConfiguracionLogo();
            if ($configuracionActual['logo_file']) {
                ConfiguracionService::eliminarLogoAnterior($configuracionActual['logo_file']);
            }
            $datos['logo_file'] = null;
        }
        // Manejar upload de logo personalizado
        elseif ($request->hasFile('logo_file')) {
            $logoPath = ConfiguracionService::manejarUploadLogo($request->file('logo_file'));
            if ($logoPath) {
                $datos['logo_file'] = $logoPath;
            }
        }

        // Guardar configuración en base de datos
        ConfiguracionService::configurarLogo($datos);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }

    /**
     * Actualizar configuración de control de candidaturas
     */
    public function updateCandidaturas(Request $request)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('settings.edit'), 403, 'No tienes permisos para editar la configuración');
        
        $request->validate([
            'bloqueo_activo' => 'required|boolean',
            'bloqueo_titulo' => 'required|string|max:255',
            'bloqueo_mensaje' => 'required|string|max:1000',
        ]);

        // Guardar configuración de candidaturas
        ConfiguracionService::configurarControlCandidaturas([
            'bloqueo_activo' => $request->bloqueo_activo,
            'bloqueo_titulo' => $request->bloqueo_titulo,
            'bloqueo_mensaje' => $request->bloqueo_mensaje,
        ]);

        return back()->with('success', 'Configuración de candidaturas actualizada correctamente.');
    }
}