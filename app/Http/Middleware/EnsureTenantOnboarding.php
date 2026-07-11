<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isPlatformAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('onboarding.*', 'logout', 'login')) {
            return $next($request);
        }

        $tenant = $user->tenants()->first();

        if (! $tenant || ! $tenant->needsOnboarding()) {
            return $next($request);
        }

        $step = $tenant->onboarding_step ?: Tenant::ONBOARDING_STEPS[0];

        if (! in_array($step, Tenant::ONBOARDING_STEPS, true)) {
            $step = Tenant::ONBOARDING_STEPS[0];
        }

        return redirect()->route('onboarding.show', ['step' => $step]);
    }
}
