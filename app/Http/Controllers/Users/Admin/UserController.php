<?php

namespace App\Http\Controllers\Users\Admin;

use App\Http\Controllers\Core\AdminController;
use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Elecciones\Cargo;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class UserController extends AdminController
{
    use HasAdvancedFilters;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Verificación de permisos
        abort_unless(auth()->user()->can('users.view'), 403, 'No tienes permisos para ver usuarios');
        
        $query = User::with(['territorio', 'departamento', 'municipio', 'localidad', 'cargo', 'roles']);

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'name', 'email', 'documento_identidad', 'role', 'activo',
            'territorio_id', 'departamento_id', 'municipio_id', 'localidad_id',
            'cargo_id', 'telefono', 'direccion', 'created_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['name', 'email', 'documento_identidad', 'telefono'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes (método sobrescrito abajo)
        $this->applySimpleFilters($query, $request, $allowedFields);

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Usuarios/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'territorio_id', 'departamento_id', 'advanced_filters']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            'canImport' => auth()->user()->can('users.import'),
            'canCreate' => auth()->user()->can('users.create'),
            'canEdit' => auth()->user()->can('users.edit'),
            'canDelete' => auth()->user()->can('users.delete'),
            'canExport' => auth()->user()->can('users.export'),
            'canViewUpdateRequests' => auth()->user()->can('users.update_requests'),
        ]);
    }
    
    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    protected function applySimpleFilters($query, $request, $allowedFields)
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            // Filtro por rol
            if ($request->filled('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Filtro por territorio
            if ($request->filled('territorio_id')) {
                $query->where('territorio_id', $request->territorio_id);
            }

            // Filtro por departamento
            if ($request->filled('departamento_id')) {
                $query->where('departamento_id', $request->departamento_id);
            }
        }
    }
    
    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar datos necesarios para los selects
        $cargos = Cargo::orderBy('nombre')->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->nombre
        ]);
        
        return [
            [
                'name' => 'name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'text',
            ],
            [
                'name' => 'documento_identidad',
                'label' => 'Documento de Identidad',
                'type' => 'text',
            ],
            [
                'name' => 'telefono',
                'label' => 'Teléfono',
                'type' => 'text',
            ],
            [
                'name' => 'direccion',
                'label' => 'Dirección',
                'type' => 'text',
            ],
            // Temporalmente deshabilitado - el campo 'role' ya no existe
            // TODO: Implementar filtrado por roles usando la relación roles
            // [
            //     'name' => 'roles.name',
            //     'label' => 'Rol',
            //     'type' => 'select',
            //     'options' => Role::all()->map(fn($r) => [
            //         'value' => $r->name,
            //         'label' => $r->display_name ?? $r->name
            //     ])->toArray(),
            // ],
            [
                'name' => 'activo',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Activo'],
                    ['value' => 0, 'label' => 'Inactivo'],
                ],
            ],
            [
                'name' => 'cargo_id',
                'label' => 'Cargo',
                'type' => 'select',
                'options' => $cargos->toArray(),
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Registro',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Verificación de permisos adicional como respaldo
        abort_unless(auth()->user()->can('users.create'), 403, 'No tienes permisos para crear usuarios');
        
        $cargos = Cargo::orderBy('nombre')->get();
        
        // Obtener TODOS los roles del sistema
        // Si el usuario es super_admin, mostrar todos los roles
        // Si no, excluir super_admin
        $rolesQuery = Role::query();
        
        if (!auth()->user()->hasRole('super_admin')) {
            $rolesQuery->where('name', '!=', 'super_admin');
        }
        
        $roles = $rolesQuery
            ->orderBy('display_name')
            ->get()
            ->map(function($role) {
                return [
                    'value' => $role->id,
                    'label' => $role->display_name,
                    'name' => $role->name,
                    'is_system' => $role->is_system,
                    'description' => $role->description,
                ];
            });
        
        return Inertia::render('Admin/Usuarios/Create', [
            'cargos' => $cargos,
            'roles' => $roles,
            'canAssignRoles' => auth()->user()->can('users.assign_roles'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verificación de permisos adicional como respaldo
        abort_unless(auth()->user()->can('users.create'), 403, 'No tienes permisos para crear usuarios');
        
        // Validación condicional del role_id basada en el permiso
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cargo_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== 'none' && !\App\Models\Elecciones\Cargo::find($value)) {
                    $fail('El cargo seleccionado no es válido.');
                }
            }],
            'documento_identidad' => 'nullable|string|max:20|unique:users,documento_identidad',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'activo' => 'boolean',
        ];

        // Solo validar role_ids si el usuario tiene permiso para asignar roles
        if (auth()->user()->can('users.assign_roles')) {
            $rules['role_ids'] = 'required|array|min:1';
            $rules['role_ids.*'] = 'exists:roles,id';
        } else {
            $rules['role_ids'] = 'nullable|array';
            $rules['role_ids.*'] = 'exists:roles,id';
        }

        $validated = $request->validate($rules);

        // Validar coherencia geográfica
        if ($validated['localidad_id']) {
            $localidad = DB::table('localidades')->find($validated['localidad_id']);
            $validated['municipio_id'] = $localidad->municipio_id;
        }
        
        if ($validated['municipio_id']) {
            $municipio = DB::table('municipios')->find($validated['municipio_id']);
            $validated['departamento_id'] = $municipio->departamento_id;
        }
        
        if ($validated['departamento_id']) {
            $departamento = DB::table('departamentos')->find($validated['departamento_id']);
            $validated['territorio_id'] = $departamento->territorio_id;
        }

        // Convertir 'none' a null para cargo_id
        if (isset($validated['cargo_id']) && $validated['cargo_id'] === 'none') {
            $validated['cargo_id'] = null;
        }
        
        $validated['password'] = Hash::make($validated['password']);
        $validated['activo'] = $validated['activo'] ?? true;

        // Determinar los roles basado en permisos
        $roleIds = [];
        if (auth()->user()->can('users.assign_roles') && isset($validated['role_ids'])) {
            // El usuario tiene permiso para asignar roles, usar los roles seleccionados
            $roleIds = $validated['role_ids'];
        } else {
            // El usuario NO tiene permiso, usar el rol por defecto
            $defaultRoleId = config('app.default_user_role_id', 4);
            $roleIds = [$defaultRoleId];
        }
        
        // Remover role_ids del array de datos validados
        unset($validated['role_ids']);
        
        // Crear el usuario
        $user = User::create($validated);
        
        // Asignar los roles al usuario usando Spatie
        if (count($roleIds) > 0) {
            $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
            if (count($roleNames) > 0) {
                $user->syncRoles($roleNames);
            }
        }

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $usuario)
    {
        // Verificación de permisos adicional como respaldo
        abort_unless(auth()->user()->can('users.edit'), 403, 'No tienes permisos para editar usuarios');
        
        $usuario->load(['territorio', 'departamento', 'municipio', 'localidad', 'cargo', 'roles']);
        $cargos = Cargo::orderBy('nombre')->get();
        
        // Obtener TODOS los roles del sistema
        // Si el usuario es super_admin, mostrar todos los roles
        // Si no, excluir super_admin
        $rolesQuery = Role::query();
        
        if (!auth()->user()->hasRole('super_admin')) {
            $rolesQuery->where('name', '!=', 'super_admin');
        }
        
        $roles = $rolesQuery
            ->orderBy('display_name')
            ->get()
            ->map(function($role) {
                return [
                    'value' => $role->id,
                    'label' => $role->display_name,
                    'name' => $role->name,
                    'is_system' => $role->is_system,
                    'description' => $role->description,
                ];
            });
        
        // Ya no necesitamos role_id individual, enviamos todos los roles del usuario
        
        return Inertia::render('Admin/Usuarios/Edit', [
            'user' => $usuario,
            'cargos' => $cargos,
            'roles' => $roles,
            'canAssignRoles' => auth()->user()->can('users.assign_roles'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $usuario)
    {
        // Verificación de permisos adicional como respaldo
        abort_unless(auth()->user()->can('users.edit'), 403, 'No tienes permisos para editar usuarios');
        
        // Validación condicional del role_id basada en el permiso
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'cargo_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== 'none' && !\App\Models\Elecciones\Cargo::find($value)) {
                    $fail('El cargo seleccionado no es válido.');
                }
            }],
            'documento_identidad' => 'nullable|string|max:20|unique:users,documento_identidad,' . $usuario->id,
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'activo' => 'boolean',
        ];

        // Solo validar role_ids si el usuario tiene permiso para asignar roles
        if (auth()->user()->can('users.assign_roles')) {
            $rules['role_ids'] = 'required|array|min:1';
            $rules['role_ids.*'] = 'exists:roles,id';
        } else {
            // Si no tiene permiso, no validar role_ids (será ignorado)
            $rules['role_ids'] = 'nullable|array';
            $rules['role_ids.*'] = 'exists:roles,id';
        }

        $validated = $request->validate($rules);

        // Validar coherencia geográfica
        if ($validated['localidad_id']) {
            $localidad = DB::table('localidades')->find($validated['localidad_id']);
            $validated['municipio_id'] = $localidad->municipio_id;
        }
        
        if ($validated['municipio_id']) {
            $municipio = DB::table('municipios')->find($validated['municipio_id']);
            $validated['departamento_id'] = $municipio->departamento_id;
        }
        
        if ($validated['departamento_id']) {
            $departamento = DB::table('departamentos')->find($validated['departamento_id']);
            $validated['territorio_id'] = $departamento->territorio_id;
        }

        // Convertir 'none' a null para cargo_id
        if (isset($validated['cargo_id']) && $validated['cargo_id'] === 'none') {
            $validated['cargo_id'] = null;
        }
        
        // Solo actualizar password si se proporciona
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Manejar actualización de roles basado en permisos
        $roleIds = null;
        if (auth()->user()->can('users.assign_roles') && isset($validated['role_ids'])) {
            // El usuario tiene permiso para cambiar roles
            $roleIds = $validated['role_ids'];
        }
        // Si no tiene permiso, los roleIds permanecen null y no se actualizan los roles
        
        // Remover role_ids del array de datos validados
        unset($validated['role_ids']);
        
        // Actualizar el usuario
        $usuario->update($validated);
        
        // Solo actualizar los roles si el usuario tiene permiso y se proporcionaron role_ids
        if ($roleIds !== null && auth()->user()->can('users.assign_roles')) {
            // Obtener los nombres de los roles a partir de sus IDs
            $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
            
            // Sincronizar roles usando Spatie (remueve anteriores y asigna los nuevos)
            if (count($roleNames) > 0) {
                $usuario->syncRoles($roleNames);
            }
        }

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario)
    {
        // Verificación de permisos adicional como respaldo
        abort_unless(auth()->user()->can('users.delete'), 403, 'No tienes permisos para eliminar usuarios');
        
        // No permitir eliminar el propio usuario
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        // No permitir eliminar el último admin
        if ($usuario->hasRole('admin') || $usuario->hasRole('super_admin')) {
            $adminCount = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'super_admin']);
            })->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'No se puede eliminar el último administrador del sistema.');
            }
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(User $usuario)
    {
        // Verificación de permisos adicional como respaldo
        abort_unless(auth()->user()->can('users.edit'), 403, 'No tienes permisos para editar usuarios');
        
        // No permitir desactivar el propio usuario
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $usuario->update(['activo' => !$usuario->activo]);

        $status = $usuario->activo ? 'activado' : 'desactivado';
        
        return back()->with('success', "Usuario {$status} exitosamente.");
    }
}