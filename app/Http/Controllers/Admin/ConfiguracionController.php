<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConfiguracionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    /**
     * Mostrar la página de configuración
     */
    public function index(): Response
    {
        $configuracion = ConfiguracionService::obtenerConfiguracionLogo();

        return Inertia::render('Admin/Configuracion', [
            'configuracion' => $configuracion,
        ]);
    }

    /**
     * Actualizar la configuración
     */
    public function update(Request $request)
    {
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
}