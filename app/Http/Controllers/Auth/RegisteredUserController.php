<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'tipo_documento' => 'required|in:TI,CC,CE,PA',
            'documento_identidad' => [
                'required',
                'string',
                'max:20',
                'unique:'.User::class,
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->tipo_documento === 'PA') {
                        // Pasaporte puede ser alfanumérico
                        if (!preg_match('/^[A-Z0-9]+$/i', $value)) {
                            $fail('El pasaporte debe contener solo letras y números.');
                        }
                    } else {
                        // Otros documentos solo numéricos
                        if (!preg_match('/^[0-9]+$/', $value)) {
                            $fail('El documento debe contener solo números.');
                        }
                    }
                }
            ],
            'telefono' => 'required|string|max:20|unique:'.User::class,
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => 'Este correo ya está registrado. Ya estás inscrito, por favor ve al login.',
            'documento_identidad.unique' => 'Este documento ya está registrado. Ya estás inscrito, por favor ve al login.',
            'telefono.unique' => 'Este teléfono ya está registrado. Ya estás inscrito, por favor ve al login.',
            'tipo_documento.required' => 'Debe seleccionar un tipo de documento.',
            'tipo_documento.in' => 'El tipo de documento seleccionado no es válido.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'tipo_documento' => $request->tipo_documento,
            'documento_identidad' => $request->documento_identidad,
            'telefono' => $request->telefono,
            'territorio_id' => $request->territorio_id,
            'departamento_id' => $request->departamento_id,
            'municipio_id' => $request->municipio_id,
            'localidad_id' => $request->localidad_id,
            'password' => Hash::make($request->password),
            'es_miembro' => false,
        ]);

        // Asignar rol por defecto desde la configuración
        $defaultRoleId = config('app.default_user_role_id', 4);
        $user->roles()->attach($defaultRoleId, [
            'assigned_at' => now(),
            'assigned_by' => null, // null indica auto-registro
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
