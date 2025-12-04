<?php

namespace Modules\Campanas\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Campanas\Models\WhatsAppGroup;
use Modules\Campanas\Services\WhatsAppGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppGroupController extends AdminController
{
    public function __construct(
        private WhatsAppGroupService $service
    ) {}

    /**
     * Listar grupos de WhatsApp
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver grupos');

        $grupos = WhatsAppGroup::query()
            ->buscar($request->input('search'))
            ->tipo($request->input('tipo'))
            ->orderByDesc('synced_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Modules/Campanas/Admin/WhatsAppGroups/Index', [
            'grupos' => $grupos,
            'filters' => $request->only(['search', 'tipo']),
            'tiposOptions' => [
                ['value' => 'all', 'label' => 'Todos'],
                ['value' => 'grupo', 'label' => 'Grupos'],
                ['value' => 'comunidad', 'label' => 'Comunidades'],
            ],
        ]);
    }

    /**
     * Ver detalles de un grupo
     */
    public function show(WhatsAppGroup $whatsappGroup): Response
    {
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver grupos');

        return Inertia::render('Modules/Campanas/Admin/WhatsAppGroups/Show', [
            'grupo' => $whatsappGroup,
        ]);
    }

    /**
     * Sincronizar todos los grupos desde Evolution API
     */
    public function sync(): RedirectResponse
    {
        abort_unless(auth()->user()->can('campanas.edit'), 403, 'No tienes permisos para sincronizar grupos');

        $result = $this->service->syncAllGroups();

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', "Sincronización completada. {$result['count']} grupos actualizados.");
    }

    /**
     * Previsualizar grupo por JID (AJAX)
     */
    public function previewByJid(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('campanas.edit'), 403);

        $request->validate([
            'group_jid' => 'required|string',
        ]);

        $groupJid = $request->input('group_jid');

        // Asegurar que termine en @g.us
        if (!str_ends_with($groupJid, '@g.us')) {
            $groupJid .= '@g.us';
        }

        $result = $this->service->previewByJid($groupJid);

        return response()->json($result);
    }

    /**
     * Añadir grupo por JID
     */
    public function addByJid(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->can('campanas.edit'), 403, 'No tienes permisos para añadir grupos');

        $request->validate([
            'group_jid' => 'required|string',
        ]);

        $groupJid = $request->input('group_jid');

        // Asegurar que termine en @g.us
        if (!str_ends_with($groupJid, '@g.us')) {
            $groupJid .= '@g.us';
        }

        $result = $this->service->findAndAddByJid($groupJid);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.whatsapp-groups.show', $result['grupo'])
            ->with('success', $result['message']);
    }

    /**
     * Obtener participantes de un grupo (AJAX)
     */
    public function getParticipants(WhatsAppGroup $whatsappGroup): JsonResponse
    {
        abort_unless(auth()->user()->can('campanas.view'), 403);

        $result = $this->service->getParticipants($whatsappGroup->group_jid);

        return response()->json($result);
    }

    /**
     * Actualizar datos de un grupo desde la API
     */
    public function refresh(WhatsAppGroup $whatsappGroup): RedirectResponse
    {
        abort_unless(auth()->user()->can('campanas.edit'), 403, 'No tienes permisos para actualizar grupos');

        $result = $this->service->refreshGroup($whatsappGroup);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', 'Datos del grupo actualizados.');
    }

    /**
     * Eliminar grupo de la base de datos local
     */
    public function destroy(WhatsAppGroup $whatsappGroup): RedirectResponse
    {
        abort_unless(auth()->user()->can('campanas.delete'), 403, 'No tienes permisos para eliminar grupos');

        // Verificar que no esté siendo usado en campañas activas
        $campanasActivas = $whatsappGroup->campanas()
            ->whereIn('estado', ['programada', 'enviando'])
            ->count();

        if ($campanasActivas > 0) {
            return back()->with('error', "No se puede eliminar. El grupo está siendo usado en {$campanasActivas} campaña(s) activa(s).");
        }

        $nombre = $whatsappGroup->nombre;
        $whatsappGroup->delete();

        return redirect()
            ->route('admin.whatsapp-groups.index')
            ->with('success', "Grupo \"{$nombre}\" eliminado de la base de datos.");
    }

    /**
     * Obtener lista de grupos para selector (AJAX)
     */
    public function list(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('campanas.view'), 403);

        $grupos = WhatsAppGroup::query()
            ->buscar($request->input('search'))
            ->orderBy('nombre')
            ->get(['id', 'group_jid', 'nombre', 'tipo', 'participantes_count', 'avatar_url']);

        return response()->json($grupos);
    }
}
