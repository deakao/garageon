<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isPlatformAdmin()) {
                return redirect()->route('admin');
            }

            $tenant = $user->tenants()->first();

            if ($tenant?->needsOnboarding()) {
                $step = $tenant->onboarding_step ?: Tenant::ONBOARDING_STEPS[0];

                return redirect()->route('onboarding.show', ['step' => $step]);
            }

            return redirect()->route('dashboard');
        }

        return view('garageon.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = $request->user();

            if ($user->isPlatformAdmin()) {
                return redirect()->intended(route('admin'));
            }

            $tenant = $user->tenants()->first();

            if ($tenant?->needsOnboarding()) {
                $step = $tenant->onboarding_step ?: Tenant::ONBOARDING_STEPS[0];

                return redirect()->route('onboarding.show', ['step' => $step]);
            }

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'Credenciais inválidas para acessar a plataforma.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
