<?php

namespace App\Http\Controllers\Core\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $redirectRoute = $request->user()->getPreferredRedirectRoute();
        
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route($redirectRoute, absolute: false))
                    : Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
