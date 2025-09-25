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
        $proyecto = null;
        if ($request->proyecto_id) {
            $proyecto = Proyecto::find($request->proyecto_id);
        }

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Index', [
            'contratos' => $this->repository->getAllPaginated($request),
            'filters' => $request->only(['search', 'proyecto_id', 'estado', 'tipo', 'responsable_id', 'vencidos', 'proximos_vencer']),
            'proyecto' => $proyecto,
            'estadisticas' => $this->repository->getEstadisticas($request->proyecto_id),
        ]);
    }

    /**
     * Muestra el formulario para crear un contrato.
     */
    public function create(Request $request): Response
    {
        $proyectoId = $request->proyecto_id;
        $proyecto = null;

        if ($proyectoId) {
            $proyecto = Proyecto::findOrFail($proyectoId);
        }

        $proyectos = Proyecto::activos()->orderBy('nombre')->get();
        $camposPersonalizados = CampoPersonalizado::paraContratos()->activos()->ordenado()->get();
        $usuarios = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Create', [
            'proyecto' => $proyecto,
            'proyectos' => $proyectos,
            'camposPersonalizados' => $camposPersonalizados,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Almacena un nuevo contrato.
     */
    public function store(StoreContratoRequest $request): RedirectResponse
    {
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
        $contrato = $this->repository->findWithRelations($contrato->id);

        if (!$contrato) {
            abort(404);
        }

        // Cargar relaciones adicionales para contraparte y participantes
        $contrato->load(['contraparteUser', 'participantes']);

        $proyectos = Proyecto::activos()->orderBy('nombre')->get();
        $camposPersonalizados = CampoPersonalizado::paraContratos()->activos()->ordenado()->get();
        $usuarios = User::select('id', 'name', 'email')->orderBy('name')->get();

        // Preparar valores de campos personalizados
        $valoresCampos = [];
        foreach ($contrato->camposPersonalizados as $valor) {
            $valoresCampos[$valor->campo_personalizado_contrato_id] = $valor->valor;
        }

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Edit', [
            'contrato' => $contrato,
            'proyectos' => $proyectos,
            'camposPersonalizados' => $camposPersonalizados,
            'usuarios' => $usuarios,
            'valoresCampos' => $valoresCampos,
        ]);
    }

    /**
     * Actualiza un contrato.
     */
    public function update(UpdateContratoRequest $request, Contrato $contrato): RedirectResponse
    {
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
        $contratos = $this->repository->getVencidosProximos(30);

        return Inertia::render('Modules/Proyectos/Admin/Contratos/ProximosVencer', [
            'contratos' => $contratos,
        ]);
    }

    /**
     * Obtiene contratos vencidos.
     */
    public function vencidos(): Response
    {
        $contratos = $this->repository->getVencidos();

        return Inertia::render('Modules/Proyectos/Admin/Contratos/Vencidos', [
            'contratos' => $contratos,
        ]);
    }
}