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
            'documento_identidad' => 'required|string|max:20|unique:'.User::class,
            'telefono' => 'nullable|string|max:20|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => 'Este correo ya está registrado. Ya estás inscrito, por favor ve al login.',
            'documento_identidad.unique' => 'Este documento ya está registrado. Ya estás inscrito, por favor ve al login.',
            'telefono.unique' => 'Este teléfono ya está registrado. Ya estás inscrito, por favor ve al login.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'documento_identidad' => $request->documento_identidad,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'es_miembro' => false,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
