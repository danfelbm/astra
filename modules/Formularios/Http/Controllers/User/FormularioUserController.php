<?php

namespace Modules\Formularios\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Formularios\Models\Formulario;
use Modules\Formularios\Models\FormularioCategoria;
use Modules\Formularios\Models\FormularioRespuesta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormularioUserController extends UserController
{
    /**
     * Mostrar lista de formularios disponibles para el usuario
     */
    public function index(Request $request): Response
    {
        // Verificar permisos de usuario para ver formularios públicos
        abort_unless(auth()->user()->can('formularios.view_public'), 403, 'No tienes permisos para ver formularios públicos');
        
        $query = Formulario::query()
            ->with(['categoria'])
            ->where('estado', 'publicado')
            ->where('activo', true);
        
        // Filtrar por categoría si se especifica
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }
        
        // Filtrar por búsqueda
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titulo', 'like', "%{$request->search}%")
                  ->orWhere('descripcion', 'like', "%{$request->search}%");
            });
        }
        
        // Solo mostrar formularios disponibles por fecha
        $query->where(function ($q) {
            $q->whereNull('fecha_inicio')
              ->orWhere('fecha_inicio', '<=', now());
        })->where(function ($q) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', now());
        });
        
        // Filtrar según permisos del usuario
        $usuario = auth()->user();
        
        // Si no es admin, aplicar filtros de visibilidad
        if (!$usuario->hasRole(['admin', 'super_admin'])) {
            $query->where(function ($q) use ($usuario) {
                // Formularios públicos
                $q->where('tipo_acceso', 'publico')
                  // O formularios que requieren autenticación (el usuario ya está autenticado)
                  ->orWhere('tipo_acceso', 'autenticado')
                  // O formularios con permiso específico (verificar si el usuario tiene permiso)
                  ->orWhere(function ($q2) {
                      $q2->where('tipo_acceso', 'con_permiso');
                      // TODO: Agregar verificación de permisos específicos cuando se implemente
                  });
            });
        }
        
        // Ordenar y paginar
        $formularios = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();
            
        // Añadir información de respuestas del usuario
        $formularios->through(function ($formulario) use ($usuario) {
            $respuesta = FormularioRespuesta::where('formulario_id', $formulario->id)
                ->where('usuario_id', $usuario->id)
                ->latest()
                ->first();
                
            $formulario->usuario_ha_respondido = $respuesta && $respuesta->estado === 'enviado';
            $formulario->tiene_borrador = $respuesta && $respuesta->estado === 'borrador';
            $formulario->puede_responder = !$formulario->usuario_ha_respondido || $formulario->limite_por_usuario > 1;
            
            // Añadir count de respuestas si no existe
            if (!isset($formulario->respuestas_count)) {
                $formulario->respuestas_count = FormularioRespuesta::where('formulario_id', $formulario->id)
                    ->where('estado', 'enviado')
                    ->count();
            }
            
            return $formulario;
        });
        
        // Obtener categorías para el filtro
        $categorias = FormularioCategoria::orderBy('nombre')->get();
        
        return Inertia::render('Modules/Formularios/User/Index', [
            'formularios' => $formularios,
            'categorias' => $categorias,
            'filters' => [
                'search' => $request->search,
                'categoria' => $request->categoria,
            ],
            'canViewPublic' => auth()->user()->can('formularios.view_public'),
            'canFillPublic' => auth()->user()->can('formularios.fill_public'),
        ]);
    }
}