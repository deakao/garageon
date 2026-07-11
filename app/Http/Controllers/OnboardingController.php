<?php

namespace App\Http\Controllers;

use App\Enums\AttendantProvider;
use App\Enums\AttendantTone;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\TenantOperatingHour;
use App\Models\VirtualAttendant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * @return array<string, string>
     */
    public static function stepLabels(): array
    {
        return [
            'hours' => 'Horários',
            'services' => 'Serviços',
            'logo' => 'Logo',
            'attendant' => 'Atendente',
            'landing' => 'Landing',
        ];
    }

    public function show(string $step): View|RedirectResponse
    {
        $tenant = $this->tenant();

        if (! $tenant->needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        if (! in_array($step, Tenant::ONBOARDING_STEPS, true)) {
            abort(404);
        }

        $tenant->markOnboardingStep($step);

        return match ($step) {
            'hours' => view('garageon.onboarding.hours', $this->baseViewData($tenant, $step) + [
                'hours' => $tenant->operatingHours()->orderBy('day_of_week')->get()->keyBy('day_of_week'),
            ]),
            'services' => view('garageon.onboarding.services', $this->baseViewData($tenant, $step) + [
                'services' => $tenant->services()->where('is_active', true)->orderBy('name')->get(),
                'categories' => $tenant->serviceCategories()->orderBy('name')->get(),
            ]),
            'logo' => view('garageon.onboarding.logo', $this->baseViewData($tenant, $step)),
            'attendant' => view('garageon.onboarding.attendant', $this->baseViewData($tenant, $step) + [
                'attendant' => VirtualAttendant::query()->firstOrNew(['tenant_id' => $tenant->id]),
                'toneOptions' => AttendantTone::options(),
            ]),
            'landing' => view('garageon.onboarding.landing', $this->baseViewData($tenant, $step) + [
                'landingPage' => $tenant->landingPage,
            ]),
        };
    }

    public function updateHours(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['hours'] as $day => $hour) {
            $isClosed = (bool) ($hour['is_closed'] ?? false);

            TenantOperatingHour::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day_of_week' => (int) $day],
                [
                    'opens_at' => $isClosed ? null : ($hour['opens_at'] ?? '08:00'),
                    'closes_at' => $isClosed ? null : ($hour['closes_at'] ?? '18:00'),
                    'is_closed' => $isClosed,
                ],
            );
        }

        return $this->advance($tenant, 'hours');
    }

    public function storeService(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'category' => ['required', 'string', 'max:80', Rule::exists('tenant_service_categories', 'name')->where('tenant_id', $tenant->id)],
        ]);

        $tenant->services()->create([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'loyalty_points' => 0,
            'is_active' => true,
        ]);

        if ($request->boolean('continue')) {
            return $this->advance($tenant, 'services');
        }

        return redirect()
            ->route('onboarding.show', ['step' => 'services'])
            ->with('status', 'Serviço cadastrado. Adicione outro ou continue.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $tenant->update([
            'logo_path' => $request->file('logo')->store("tenants/{$tenant->id}/logo", 'public'),
        ]);

        return $this->advance($tenant, 'logo');
    }

    public function updateAttendant(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tone' => ['required', Rule::enum(AttendantTone::class)],
            'context' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $attendant = VirtualAttendant::query()->firstOrNew(['tenant_id' => $tenant->id]);
        $wantsActive = $request->boolean('is_active');

        $attendant->fill([
            'name' => $validated['name'],
            'tone' => $validated['tone'],
            'context' => ($validated['context'] ?? null) ?: null,
            'is_active' => false,
        ]);

        if (! $attendant->exists) {
            $attendant->provider = $attendant->provider ?? AttendantProvider::OpenAI;
        }

        // No onboarding não exigimos API key: só liga se já houver chave utilizável.
        if ($wantsActive && filled($attendant->resolveApiKey())) {
            $attendant->is_active = true;
        }

        $attendant->save();

        return $this->advance($tenant, 'attendant');
    }

    public function updateLanding(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['required', 'string', 'max:255'],
            'cta_label' => ['required', 'string', 'max:80'],
            'published' => ['nullable', 'boolean'],
        ]);

        LandingPage::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'eyebrow' => 'Estética automotiva premium',
                'headline' => $validated['headline'],
                'subheadline' => $validated['subheadline'],
                'hero_image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1400&q=80',
                'hero_badge_title' => $tenant->name,
                'hero_badge_body' => 'Detail premium com padrão de entrega visual.',
                'cta_label' => $validated['cta_label'],
                'published_at' => $request->boolean('published') ? now() : null,
            ],
        );

        return $this->advance($tenant, 'landing');
    }

    public function skipStep(string $step): RedirectResponse
    {
        $tenant = $this->tenant();

        if (! in_array($step, Tenant::ONBOARDING_STEPS, true)) {
            abort(404);
        }

        return $this->advance($tenant, $step);
    }

    public function showSkip(): View|RedirectResponse
    {
        $tenant = $this->tenant();

        if (! $tenant->needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        $labels = self::stepLabels();
        $checklist = $tenant->onboardingChecklist();

        return view('garageon.onboarding.skip', [
            'tenant' => $tenant,
            'currentStep' => $tenant->onboarding_step ?: Tenant::ONBOARDING_STEPS[0],
            'steps' => $labels,
            'checklist' => $checklist,
            'pendingCount' => collect($checklist)->filter(fn (bool $done) => ! $done)->count(),
        ]);
    }

    public function dismiss(): RedirectResponse
    {
        $tenant = $this->tenant();
        $tenant->completeOnboarding();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Você pode concluir a configuração a qualquer momento em Configurações.');
    }

    private function tenant(): Tenant
    {
        $user = auth()->user();
        abort_if($user->isPlatformAdmin(), 403);

        return $user->tenants()->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function baseViewData(Tenant $tenant, string $step): array
    {
        return [
            'tenant' => $tenant,
            'currentStep' => $step,
            'steps' => self::stepLabels(),
            'previousStep' => $tenant->previousOnboardingStep($step),
            'nextStep' => $tenant->nextOnboardingStep($step),
        ];
    }

    private function advance(Tenant $tenant, string $currentStep): RedirectResponse
    {
        $next = $tenant->nextOnboardingStep($currentStep);

        if ($next === null) {
            $tenant->completeOnboarding();

            return redirect()
                ->route('dashboard')
                ->with('status', $tenant->name.' já está ON. Configuração inicial concluída.');
        }

        $tenant->markOnboardingStep($next);

        return redirect()->route('onboarding.show', ['step' => $next]);
    }
}
