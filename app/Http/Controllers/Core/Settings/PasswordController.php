<?php

namespace App\Http\Controllers\Core\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(Request $request): Response
    {
        // Verificar permisos de usuario para acceder a configuración de contraseña
        abort_unless(auth()->user()->can('profile.change_password'), 403, 'No tienes permisos para cambiar tu contraseña');
        
        return Inertia::render('User/Settings/Password', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            // Props de permisos de usuario
            'canChangePassword' => auth()->user()->can('profile.change_password'),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Verificar permisos de usuario para cambiar contraseña
        abort_unless(auth()->user()->can('profile.change_password'), 403, 'No tienes permisos para cambiar tu contraseña');
        
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
