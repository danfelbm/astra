<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Models\User;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Services\ContratoService;
use Modules\Proyectos\Repositories\ContratoRepository;
use Modules\Proyectos\Http\Requests\Admin\StoreContratoRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateContratoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class ContratoController extends AdminController
{
    public function __construct(
        private ContratoService $service,
        private ContratoRepository $repository
    ) {}

    /**
     * Muestra el listado de contratos.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.view'), 403, 'No tienes permisos para ver contratos');

        $proyecto = null;
        if ($request->proyecto_id) {
            $proyecto = Proyecto::find($request->proyecto_id);
        }

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Index', [
            'contratos' => $this->repository->getAllPaginated($request),
            'filters' => $request->only(['search', 'proyecto_id', 'estado', 'tipo', 'responsable_id', 'vencidos', 'proximos_vencer']),
            'proyecto' => $proyecto,
            'estadisticas' => $this->repository->getEstadisticas($request->proyecto_id),
            'canCreate' => auth()->user()->can('contratos.create'),
            'canEdit' => auth()->user()->can('contratos.edit'),
            'canDelete' => auth()->user()->can('contratos.delete'),
            'canChangeStatus' => auth()->user()->can('contratos.change_status'),
            'canExport' => auth()->user()->can('contratos.export'),
            'canDownload' => auth()->user()->can('contratos.download'),
        ]);
    }

    /**
     * Muestra el formulario para crear un contrato.
     */
    public function create(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.create'), 403, 'No tienes permisos para crear contratos');

        $proyectoId = $request->proyecto_id;
        $proyecto = null;

        if ($proyectoId) {
            $proyecto = Proyecto::findOrFail($proyectoId);
        }

        $proyectos = Proyecto::activos()->orderBy('nombre')->get();
        $camposPersonalizados = CampoPersonalizado::paraContratos()->activos()->ordenado()->get();

        // Buscar borrador existente del usuario para recuperarlo
        $borrador = Contrato::where('created_by', auth()->id())
            ->where('estado', 'borrador')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->first();

        // Solo cargar el responsable si existe en el borrador
        $responsable = null;
        if ($borrador && $borrador->responsable_id) {
            $responsable = User::select('id', 'name', 'email')->find($borrador->responsable_id);
        }

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Create', [
            'proyecto' => $proyecto,
            'proyectos' => $proyectos,
            'camposPersonalizados' => $camposPersonalizados,
            'responsable' => $responsable, // Solo el responsable específico, no todos los usuarios
            'borrador' => $borrador, // Pasar borrador para recuperación
            'canManageFields' => auth()->user()->can('proyectos.manage_fields'),
        ]);
    }

    /**
     * Almacena un nuevo contrato.
     */
    public function store(StoreContratoRequest $request): RedirectResponse
    {
        // Verificar permisos - Ya verificado en FormRequest pero agregamos por consistencia
        abort_unless(auth()->user()->can('contratos.create'), 403, 'No tienes permisos para crear contratos');

        $resultado = $this->service->create($request->validated());

        if (!$resultado['success']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()
            ->route('admin.contratos.show', $resultado['contrato'])
            ->with('success', $resultado['message']);
    }

    /**
     * Muestra un contrato específico.
     */
    public function show(Contrato $contrato): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.view'), 403, 'No tienes permisos para ver contratos');

        $contrato = $this->repository->findWithRelations($contrato->id);

        if (!$contrato) {
            abort(404);
        }

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Show', [
            'contrato' => $contrato,
            'can' => [
                'edit' => auth()->user()->can('contratos.edit'),
                'delete' => auth()->user()->can('contratos.delete'),
                'change_status' => auth()->user()->can('contratos.change_status'),
            ],
        ]);
    }

    /**
     * Muestra el formulario para editar un contrato.
     */
    public function edit(Contrato $contrato): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.edit'), 403, 'No tienes permisos para editar contratos');

        $contrato = $this->repository->findWithRelations($contrato->id);

        if (!$contrato) {
            abort(404);
        }

        // Cargar relaciones adicionales para contraparte, participantes y responsable
        $contrato->load(['contraparteUser', 'participantes', 'responsable']);

        $proyectos = Proyecto::activos()->orderBy('nombre')->get();
        $camposPersonalizados = CampoPersonalizado::paraContratos()->activos()->ordenado()->get();

        // Preparar valores de campos personalizados
        $valoresCampos = [];
        foreach ($contrato->camposPersonalizados as $valor) {
            $valoresCampos[$valor->campo_personalizado_contrato_id] = $valor->valor;
        }

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Edit', [
            'contrato' => $contrato,
            'proyectos' => $proyectos,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCampos' => $valoresCampos,
            'canChangeStatus' => auth()->user()->can('contratos.change_status'),
            'canDelete' => auth()->user()->can('contratos.delete'),
            'canDownload' => auth()->user()->can('contratos.download'),
            'canManageFields' => auth()->user()->can('proyectos.manage_fields'),
        ]);
    }

    /**
     * Actualiza un contrato.
     */
    public function update(UpdateContratoRequest $request, Contrato $contrato): RedirectResponse
    {
        // Verificar permisos - Ya verificado en FormRequest pero agregamos por consistencia
        abort_unless(auth()->user()->can('contratos.edit'), 403, 'No tienes permisos para editar contratos');

        $resultado = $this->service->update($contrato, $request->validated());

        if (!$resultado['success']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()
            ->route('admin.contratos.show', $contrato)
            ->with('success', $resultado['message']);
    }

    /**
     * Elimina un contrato.
     */
    public function destroy(Contrato $contrato): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.delete'), 403, 'No tienes permisos para eliminar contratos');

        $resultado = $this->service->delete($contrato);

        if (!$resultado['success']) {
            return redirect()->back()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()
            ->route('admin.contratos.index')
            ->with('success', $resultado['message']);
    }

    /**
     * Cambia el estado de un contrato.
     */
    public function cambiarEstado(Request $request, Contrato $contrato): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.change_status'), 403, 'No tienes permisos para cambiar el estado de contratos');

        $request->validate([
            'estado' => 'required|in:borrador,activo,finalizado,cancelado'
        ]);

        $resultado = $this->service->cambiarEstado($contrato, $request->estado);

        if (!$resultado['success']) {
            return redirect()->back()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()->back()
            ->with('success', $resultado['message']);
    }

    /**
     * Duplica un contrato.
     */
    public function duplicar(Contrato $contrato): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.create'), 403, 'No tienes permisos para duplicar contratos');

        $resultado = $this->service->duplicarContrato($contrato);

        if (!$resultado['success']) {
            return redirect()->back()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()
            ->route('admin.contratos.edit', $resultado['contrato'])
            ->with('success', $resultado['message']);
    }

    /**
     * Obtiene contratos próximos a vencer.
     */
    public function proximosVencer(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.view'), 403, 'No tienes permisos para ver contratos');

        $contratos = $this->repository->getVencidosProximos(30);

        return Inertia::render('Modules/Proyectos/Admin/Contratos/ProximosVencer', [
            'contratos' => $contratos,
            'canEdit' => auth()->user()->can('contratos.edit'),
            'canChangeStatus' => auth()->user()->can('contratos.change_status'),
            'canExport' => auth()->user()->can('contratos.export'),
        ]);
    }

    /**
     * Obtiene contratos vencidos.
     */
    public function vencidos(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.view'), 403, 'No tienes permisos para ver contratos');

        $contratos = $this->repository->getVencidos();

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Vencidos', [
            'contratos' => $contratos,
            'canEdit' => auth()->user()->can('contratos.edit'),
            'canChangeStatus' => auth()->user()->can('contratos.change_status'),
            'canExport' => auth()->user()->can('contratos.export'),
        ]);
    }

    /**
     * Elimina un archivo específico del contrato.
     */
    public function eliminarArchivo(Contrato $contrato, int $indice): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.edit'), 403, 'No tienes permisos para editar contratos');

        $resultado = $this->service->eliminarArchivo($contrato, $indice);

        if (!$resultado['success']) {
            return redirect()->back()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()->back()
            ->with('success', $resultado['message']);
    }

    /**
     * Autoguardado de borrador de contrato (para Create).
     * Los datos vienen en formato: { formulario_data: {...}, respuestas: {...} }
     */
    public function autosave(Request $request)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.create'), 403,
            'No tienes permisos para crear contratos');

        try {
            // Extraer datos del formato del composable useAutoSave
            $formData = $request->input('formulario_data', []);

            // Si no hay datos, retornar error
            if (empty($formData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos para guardar'
                ], 400);
            }

            // Buscar borrador existente del usuario
            $contrato = Contrato::where('created_by', auth()->id())
                ->where('estado', 'borrador')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->latest()
                ->first();

            // Si no existe borrador, crear uno nuevo
            if (!$contrato) {
                $contrato = new Contrato();
                $contrato->estado = 'borrador';
                $contrato->tenant_id = auth()->user()->tenant_id;
                $contrato->created_by = auth()->id();
            }

            // Actualizar con los datos recibidos (solo valores NO vacíos)
            $camposPermitidos = [
                'proyecto_id', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin',
                'tipo', 'monto_total', 'moneda', 'responsable_id',
                'contraparte_user_id', 'contraparte_nombre', 'contraparte_identificacion',
                'contraparte_email', 'contraparte_telefono', 'observaciones',
                'archivos_paths', 'archivos_nombres', 'tipos_archivos'
            ];

            foreach ($camposPermitidos as $campo) {
                if (array_key_exists($campo, $formData) && $formData[$campo] !== null && $formData[$campo] !== '') {
                    // Caso especial: responsable_id 'none' debe ser null
                    if ($campo === 'responsable_id' && $formData[$campo] === 'none') {
                        $contrato->$campo = null;
                    } else {
                        $contrato->$campo = $formData[$campo];
                    }
                }
            }

            // Setear valores por defecto para campos requeridos si están vacíos
            if (empty($contrato->nombre)) {
                $contrato->nombre = 'Borrador sin nombre';
            }
            if (empty($contrato->fecha_inicio)) {
                $contrato->fecha_inicio = now()->format('Y-m-d');
            }
            if (empty($contrato->tipo)) {
                $contrato->tipo = 'servicio';
            }

            // Guardar sin disparar eventos ni validación estricta
            $contrato->saveQuietly();

            return response()->json([
                'success' => true,
                'contrato_id' => $contrato->id,
                'message' => 'Borrador guardado',
                'timestamp' => now()->toTimeString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en autosave de contrato: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Autoguardado de contrato existente (para Edit).
     * Los datos vienen en formato: { formulario_data: {...}, respuestas: {...} }
     */
    public function autosaveExisting(Request $request, Contrato $contrato)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.edit'), 403,
            'No tienes permisos para editar contratos');

        try {
            // Extraer datos del formato del composable useAutoSave
            $formData = $request->input('formulario_data', []);

            // Si no hay datos, retornar error
            if (empty($formData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos para guardar'
                ], 400);
            }

            // Verificar tenant (seguridad)
            if ($contrato->tenant_id !== auth()->user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Solo permitir autoguardado en estados editables
            if (!in_array($contrato->estado, ['borrador', 'activo'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El contrato no se puede editar en su estado actual'
                ], 400);
            }

            // Actualizar con los datos recibidos (solo valores NO vacíos)
            $camposPermitidos = [
                'proyecto_id', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin',
                'tipo', 'monto_total', 'moneda', 'responsable_id',
                'contraparte_user_id', 'contraparte_nombre', 'contraparte_identificacion',
                'contraparte_email', 'contraparte_telefono', 'observaciones',
                'archivos_paths', 'archivos_nombres', 'tipos_archivos'
            ];

            foreach ($camposPermitidos as $campo) {
                // Solo actualizar si el valor NO es null ni string vacío
                if (array_key_exists($campo, $formData) && $formData[$campo] !== null && $formData[$campo] !== '') {
                    // Caso especial: responsable_id 'none' debe ser null
                    if ($campo === 'responsable_id' && $formData[$campo] === 'none') {
                        $contrato->$campo = null;
                    } else {
                        $contrato->$campo = $formData[$campo];
                    }
                }
            }

            // Actualizar campos de auditoría
            $contrato->updated_by = auth()->id();

            // Guardar sin disparar eventos
            $contrato->saveQuietly();

            // Sincronizar participantes si se enviaron
            if (isset($formData['participantes']) && is_array($formData['participantes'])) {
                $participantes = collect($formData['participantes'])
                    ->mapWithKeys(function ($p) {
                        return [$p['user_id'] => [
                            'rol' => $p['rol'] ?? 'testigo',
                            'notas' => $p['notas'] ?? null
                        ]];
                    });

                $contrato->participantes()->sync($participantes);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados',
                'timestamp' => now()->toTimeString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en autosave existing de contrato: ' . $e->getMessage(), [
                'contrato_id' => $contrato->id,
                'user_id' => auth()->id(),
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cambios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina el borrador del usuario autenticado.
     */
    public function eliminarBorrador()
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('contratos.create'), 403,
            'No tienes permisos para gestionar borradores');

        try {
            // Buscar y eliminar borrador del usuario
            $borrador = Contrato::where('created_by', auth()->id())
                ->where('estado', 'borrador')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->latest()
                ->first();

            if (!$borrador) {
                return redirect()->back()->with('error', 'No hay borrador para eliminar');
            }

            // Eliminar archivos físicos si existen
            if ($borrador->archivos_paths && is_array($borrador->archivos_paths)) {
                foreach ($borrador->archivos_paths as $path) {
                    if (\Storage::disk('public')->exists($path)) {
                        \Storage::disk('public')->delete($path);
                    }
                }
            }

            // Eliminar borrador
            $borrador->delete();

            activity()
                ->causedBy(auth()->user())
                ->log("Borrador de contrato eliminado (ID: {$borrador->id})");

            return redirect()->route('admin.contratos.create')
                ->with('success', 'Borrador eliminado exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar borrador: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al eliminar borrador');
        }
    }
}