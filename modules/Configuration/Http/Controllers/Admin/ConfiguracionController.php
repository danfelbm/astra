<?php

namespace Modules\Configuration\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Services\ConfiguracionService;
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
        $configuracionLogin = ConfiguracionService::obtenerConfiguracionLogin();
        $configuracionDashboard = ConfiguracionService::obtenerConfiguracionDashboardUser();
        $configuracionWelcome = ConfiguracionService::obtenerConfiguracionWelcome();

        return Inertia::render('Modules/Configuration/Admin/Configuracion', [
            'configuracion' => $configuracionLogo,
            'configuracionCandidaturas' => $configuracionCandidaturas,
            'configuracionLogin' => $configuracionLogin,
            'configuracionDashboard' => $configuracionDashboard,
            'configuracionWelcome' => $configuracionWelcome,
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

    /**
     * Actualizar configuración de mensaje de login
     */
    public function updateLogin(Request $request)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('settings.edit'), 403, 'No tienes permisos para editar la configuración');

        $request->validate([
            'mensaje_html' => 'required|string|max:2000',
        ]);

        // Guardar configuración de mensaje de login
        ConfiguracionService::configurarMensajeLogin([
            'mensaje_html' => $request->mensaje_html,
        ]);

        return back()->with('success', 'Configuración del mensaje de login actualizada correctamente.');
    }

    /**
     * Actualizar configuración del dashboard de usuarios
     */
    public function updateDashboardUser(Request $request)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('settings.edit'), 403, 'No tienes permisos para editar la configuración');

        $validatedData = $request->validate([
            'hero_html' => 'required|string|max:5000',
            'cards' => 'required|array',
            'cards.*.id' => 'required|string',
            'cards.*.enabled' => 'required|boolean',
            'cards.*.order' => 'required|integer|min:0',
            'cards.*.color' => 'required|string|in:blue,green,red,purple,orange,yellow,pink,indigo,teal,gray',
            'cards.*.icon' => 'required|string|in:Vote,Users,FileText,Calendar,CheckCircle,AlertCircle,Settings,Mail,Phone,MapPin,Clock,Upload,Download,Eye,Edit,Trash,Plus,Minus,ArrowRight,ArrowLeft',
            'cards.*.title' => 'required|string|max:100',
            'cards.*.description' => 'required|string|max:300',
            'cards.*.buttonText' => 'required|string|max:50',
            'cards.*.buttonLink' => 'required|string|max:500',
        ]);

        // Convertir valores booleanos correctamente (igual que en updateWelcome)
        $validatedData['cards'] = array_map(function ($card) {
            // Convertir enabled a boolean (manejar true/false/1/0/"1"/"0")
            if (is_bool($card['enabled'])) {
                $card['enabled'] = $card['enabled'];
            } elseif ($card['enabled'] === '1' || $card['enabled'] === 1 || $card['enabled'] === 'true') {
                $card['enabled'] = true;
            } else {
                $card['enabled'] = false;
            }

            $card['order'] = (int) $card['order'];

            return $card;
        }, $validatedData['cards']);

        // Guardar configuración del dashboard
        ConfiguracionService::configurarDashboardUser([
            'hero_html' => $validatedData['hero_html'],
            'cards' => $validatedData['cards'],
        ]);

        return back()->with('success', 'Configuración del dashboard de usuarios actualizada correctamente.');
    }

    /**
     * Actualizar configuración de la página principal (Welcome)
     */
    public function updateWelcome(Request $request)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('settings.edit'), 403, 'No tienes permisos para editar la configuración');

        $validatedData = $request->validate([
            'header.logo_file' => 'nullable|file|mimes:jpg,jpeg,png,svg|max:2048',
            'header.remove_logo' => 'boolean',
            'header.logo_text' => 'required|string|max:100',
            'hero.title_html' => 'required|string|max:1000',
            'hero.description_html' => 'required|string|max:2000',
            'background_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'remove_background' => 'boolean',
            'links' => 'required|array',
            'links.*.id' => 'required|string',
            'links.*.enabled' => 'required',
            'links.*.order' => 'required|integer|min:0',
            'links.*.text' => 'required|string|max:100',
            'links.*.url' => 'required|string|max:500',
            'links.*.visibility' => 'required|in:always,logged_in,logged_out',
            'links.*.is_primary' => 'required',
        ]);

        // Convertir valores booleanos correctamente
        $validatedData['links'] = array_map(function ($link) {
            // Convertir a boolean (manejar true/false/1/0/"1"/"0")
            if (is_bool($link['enabled'])) {
                $link['enabled'] = $link['enabled'];
            } elseif ($link['enabled'] === '1' || $link['enabled'] === 1) {
                $link['enabled'] = true;
            } else {
                $link['enabled'] = false;
            }

            if (is_bool($link['is_primary'])) {
                $link['is_primary'] = $link['is_primary'];
            } elseif ($link['is_primary'] === '1' || $link['is_primary'] === 1) {
                $link['is_primary'] = true;
            } else {
                $link['is_primary'] = false;
            }

            $link['order'] = (int) $link['order'];

            return $link;
        }, $validatedData['links']);

        // Manejar eliminación de logo
        if ($request->input('header.remove_logo')) {
            $validatedData['header']['logo_url'] = null;
        }
        // Manejar upload de logo personalizado
        elseif ($request->hasFile('header.logo_file')) {
            $logoPath = ConfiguracionService::manejarUploadLogoWelcome($request->file('header.logo_file'));
            if ($logoPath) {
                $validatedData['header']['logo_url'] = $logoPath;
            }
        } else {
            // Mantener logo actual si no hay cambios
            $configuracionActual = ConfiguracionService::obtenerConfiguracionWelcome();
            $validatedData['header']['logo_url'] = $configuracionActual['header']['logo_url'];
        }

        // Manejar eliminación de background
        if ($request->input('remove_background')) {
            $validatedData['background_url'] = null;
        }
        // Manejar upload de background personalizado
        elseif ($request->hasFile('background_file')) {
            $backgroundPath = ConfiguracionService::manejarUploadBackgroundWelcome($request->file('background_file'));
            if ($backgroundPath) {
                $validatedData['background_url'] = $backgroundPath;
            }
        } else {
            // Mantener background actual si no hay cambios
            $configuracionActual = ConfiguracionService::obtenerConfiguracionWelcome();
            $validatedData['background_url'] = $configuracionActual['background_url'];
        }

        // Guardar configuración de la página principal
        ConfiguracionService::configurarWelcome($validatedData);

        return back()->with('success', 'Configuración de la página principal actualizada correctamente.');
    }
}