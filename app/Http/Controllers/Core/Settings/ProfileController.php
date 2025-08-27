<?php

namespace App\Http\Controllers\Core\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        // Verificar permisos de usuario para ver perfil propio
        abort_unless(auth()->user()->can('profile.view'), 403, 'No tienes permisos para ver tu perfil');
        
        return Inertia::render('User/Settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            // Props de permisos de usuario
            'canEdit' => auth()->user()->can('profile.edit'),
            'canChangePassword' => auth()->user()->can('profile.change_password'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Verificar permisos de usuario para editar perfil propio
        abort_unless(auth()->user()->can('profile.edit'), 403, 'No tienes permisos para editar tu perfil');
        
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Verificar permisos de usuario para eliminar perfil propio
        abort_unless(auth()->user()->can('profile.delete'), 403, 'No tienes permisos para eliminar tu perfil');
        
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
