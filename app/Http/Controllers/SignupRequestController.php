<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SignupRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SignupRequestController extends Controller
{
    public function create(Request $request): View
    {
        $selectedPlan = Plan::where('active', true)
            ->where('slug', $request->query('plano'))
            ->first();

        return view('garageon.signup', [
            'selectedPlan' => $selectedPlan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'owner_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'whatsapp_phone' => ['required', 'string', 'max:30'],
            'business_type' => ['required', 'string', 'max:80'],
            'monthly_leads' => ['nullable', 'string', 'max:60'],
            'main_challenge' => ['nullable', 'string', 'max:120'],
            'plan' => ['nullable', 'string', 'exists:plans,slug'],
        ]);

        $plan = ! empty($validated['plan'])
            ? Plan::where('slug', $validated['plan'])->where('active', true)->first()
            : null;

        $user = DB::transaction(function () use ($request, $validated, $plan): User {
            SignupRequest::create([
                ...collect($validated)->except(['password', 'plan'])->all(),
                'metadata' => [
                    'source' => 'home_signup',
                    'plan' => $plan?->slug,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            $tenant = Tenant::create([
                'plan_id' => $plan?->id,
                'name' => $validated['business_name'],
                'slug' => $this->uniqueTenantSlug($validated['business_name']),
                'whatsapp_phone' => $validated['whatsapp_phone'],
                'brand_colors' => ['primary' => '#050505', 'accent' => '#facc15', 'surface' => '#ffffff'],
                'trial_ends_at' => now()->addDays(14),
            ]);

            $tenant->serviceCategories()->createMany([
                ['name' => 'Lavagem', 'slug' => 'lavagem'],
                ['name' => 'Proteção', 'slug' => 'protecao'],
                ['name' => 'Pintura', 'slug' => 'pintura'],
            ]);

            $user = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $tenant->users()->attach($user->id, ['role' => 'owner']);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function uniqueTenantSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'loja';
        $slug = $baseSlug;
        $counter = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
