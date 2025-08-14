<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cargo;
use App\Traits\HasAdvancedFilters;
use App\Traits\AuthorizesActions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use HasAdvancedFilters, AuthorizesActions;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Verificación de permisos adicional como respaldo
        $this->authorizeAction('users.view');
        
        $query = User::with(['territorio', 'departamento', 'municipio', 'localidad', 'cargo']);

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
            //     'options' => \App\Models\Role::all()->map(fn($r) => [
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
        $this->authorizeAction('users.create');
        
        $cargos = Cargo::orderBy('nombre')->get();
        
        // Obtener el tenant actual del usuario autenticado
        $currentTenantId = auth()->user()->tenant_id;
        
        // Obtener roles disponibles para el tenant actual
        // Incluye roles del sistema (excepto super_admin) y roles personalizados del tenant
        $roles = \App\Models\Role::where(function($query) use ($currentTenantId) {
            $query->where('tenant_id', $currentTenantId)
                  ->orWhere(function($q) {
                      $q->whereNull('tenant_id')
                        ->where('is_system', true)
                        ->where('name', '!=', 'super_admin'); // super_admin solo puede ser asignado por otro super_admin
                  });
        })
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
            'canAssignRoles' => auth()->user()->hasPermission('users.assign_roles'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verificación de permisos adicional como respaldo
        $this->authorizeAction('users.create');
        
        // Validación condicional del role_id basada en el permiso
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cargo_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== 'none' && !\App\Models\Cargo::find($value)) {
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

        // Solo requerir role_id si el usuario tiene permiso para asignar roles
        if (auth()->user()->hasPermission('users.assign_roles')) {
            $rules['role_id'] = 'required|exists:roles,id';
        } else {
            $rules['role_id'] = 'nullable|exists:roles,id';
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

        // Determinar el role_id basado en permisos
        if (auth()->user()->hasPermission('users.assign_roles')) {
            // El usuario tiene permiso para asignar roles, usar el rol seleccionado
            $roleId = $validated['role_id'];
        } else {
            // El usuario NO tiene permiso, usar el rol por defecto
            $roleId = config('app.default_user_role_id', 4);
        }
        
        // Remover role_id del array de datos validados
        unset($validated['role_id']);
        
        // Crear el usuario
        $user = User::create($validated);
        
        // Asignar el rol al usuario
        $user->roles()->attach($roleId, [
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $usuario)
    {
        // Verificación de permisos adicional como respaldo
        $this->authorizeAction('users.edit');
        
        $usuario->load(['territorio', 'departamento', 'municipio', 'localidad', 'cargo', 'roles']);
        $cargos = Cargo::orderBy('nombre')->get();
        
        // Obtener el tenant actual del usuario autenticado
        $currentTenantId = auth()->user()->tenant_id;
        
        // Obtener roles disponibles para el tenant actual
        $roles = \App\Models\Role::where(function($query) use ($currentTenantId) {
            $query->where('tenant_id', $currentTenantId)
                  ->orWhere(function($q) {
                      $q->whereNull('tenant_id')
                        ->where('is_system', true)
                        ->where('name', '!=', 'super_admin'); // super_admin solo puede ser asignado por otro super_admin
                  });
        })
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
        
        // Agregar el ID del rol actual del usuario
        $usuario->role_id = $usuario->roles->first()?->id;
        
        return Inertia::render('Admin/Usuarios/Edit', [
            'user' => $usuario,
            'cargos' => $cargos,
            'roles' => $roles,
            'canAssignRoles' => auth()->user()->hasPermission('users.assign_roles'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $usuario)
    {
        // Verificación de permisos adicional como respaldo
        $this->authorizeAction('users.edit');
        
        // Validación condicional del role_id basada en el permiso
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'cargo_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== 'none' && !\App\Models\Cargo::find($value)) {
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

        // Solo requerir role_id si el usuario tiene permiso para asignar roles
        if (auth()->user()->hasPermission('users.assign_roles')) {
            $rules['role_id'] = 'required|exists:roles,id';
        } else {
            // Si no tiene permiso, no validar role_id (será ignorado)
            $rules['role_id'] = 'nullable|exists:roles,id';
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

        // Manejar actualización de rol basado en permisos
        $roleId = null;
        if (auth()->user()->hasPermission('users.assign_roles')) {
            // El usuario tiene permiso para cambiar roles
            $roleId = $validated['role_id'] ?? null;
        }
        // Si no tiene permiso, el roleId permanece null y no se actualiza el rol
        
        // Remover role_id del array de datos validados
        unset($validated['role_id']);
        
        // Actualizar el usuario
        $usuario->update($validated);
        
        // Solo actualizar el rol si el usuario tiene permiso y se proporcionó un role_id
        if ($roleId !== null && auth()->user()->hasPermission('users.assign_roles')) {
            // Remover roles anteriores y asignar el nuevo
            $usuario->roles()->sync([$roleId => [
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]]);
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
        $this->authorizeAction('users.delete');
        
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
        $this->authorizeAction('users.edit');
        
        // No permitir desactivar el propio usuario
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $usuario->update(['activo' => !$usuario->activo]);

        $status = $usuario->activo ? 'activado' : 'desactivado';
        
        return back()->with('success', "Usuario {$status} exitosamente.");
    }
}