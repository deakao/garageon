<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_onboarding_redirects_dashboard_to_wizard(): void
    {
        [$tenant, $user] = $this->createOnboardingTenant();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.show', ['step' => 'hours']));

        $this->assertTrue($tenant->fresh()->needsOnboarding());
    }

    public function test_skip_step_advances_without_persisting_hours(): void
    {
        [$tenant, $user] = $this->createOnboardingTenant();

        $this->actingAs($user)
            ->post(route('onboarding.skip-step', ['step' => 'hours']))
            ->assertRedirect(route('onboarding.show', ['step' => 'services']));

        $this->assertSame(0, $tenant->operatingHours()->count());
        $this->assertSame('services', $tenant->fresh()->onboarding_step);
    }

    public function test_dismiss_marks_completed_and_opens_dashboard(): void
    {
        [$tenant, $user] = $this->createOnboardingTenant();

        $this->actingAs($user)
            ->get(route('onboarding.skip'))
            ->assertOk()
            ->assertSee('Tudo bem configurar depois');

        $this->actingAs($user)
            ->post(route('onboarding.dismiss'))
            ->assertRedirect(route('dashboard'));

        $tenant->refresh();
        $this->assertFalse($tenant->needsOnboarding());
        $this->assertNotNull($tenant->onboarding_completed_at);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_completed_tenant_is_not_forced_into_onboarding(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->assertFalse($tenant->needsOnboarding());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('onboarding.show', ['step' => 'hours']))
            ->assertRedirect(route('dashboard'));
    }

    public function test_advancing_all_steps_completes_onboarding(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->createOnboardingTenant();

        $hours = [];
        for ($day = 0; $day < 7; $day++) {
            $hours[$day] = [
                'opens_at' => '08:00',
                'closes_at' => '18:00',
                'is_closed' => $day === 0 ? '1' : null,
            ];
        }

        $this->actingAs($user)
            ->put(route('onboarding.hours.update'), ['hours' => $hours])
            ->assertRedirect(route('onboarding.show', ['step' => 'services']));

        $this->assertSame(7, $tenant->operatingHours()->count());

        $category = $tenant->serviceCategories()->firstOrFail();

        $this->actingAs($user)
            ->post(route('onboarding.services.store'), [
                'name' => 'Lavagem técnica',
                'category' => $category->name,
                'duration_minutes' => 60,
                'price' => 120,
                'continue' => '1',
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 'logo']));

        $this->actingAs($user)
            ->put(route('onboarding.logo.update'), [
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 'attendant']));

        $this->actingAs($user)
            ->put(route('onboarding.attendant.update'), [
                'name' => 'Piloto',
                'tone' => 'friendly',
                'context' => 'Loja premium de detailing.',
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 'landing']));

        $this->actingAs($user)
            ->put(route('onboarding.landing.update'), [
                'headline' => 'Bem vindos à '.$tenant->name,
                'subheadline' => 'Detail com padrão de entrega.',
                'cta_label' => 'Agendar',
                'published' => '1',
            ])
            ->assertRedirect(route('dashboard'));

        $tenant->refresh();
        $this->assertFalse($tenant->needsOnboarding());
        $this->assertNotNull($tenant->onboarding_completed_at);
        $this->assertNotNull($tenant->logo_path);
        $this->assertNotNull($tenant->landingPage);
        $this->assertNotNull($tenant->virtualAttendant);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function createOnboardingTenant(): array
    {
        $tenant = Tenant::create([
            'name' => 'Carbon Onboarding',
            'slug' => 'carbon-onboarding',
            'onboarding_step' => 'hours',
            'onboarding_completed_at' => null,
        ]);

        $tenant->serviceCategories()->createMany([
            ['name' => 'Lavagem', 'slug' => 'lavagem'],
            ['name' => 'Proteção', 'slug' => 'protecao'],
            ['name' => 'Pintura', 'slug' => 'pintura'],
        ]);

        $user = User::factory()->create([
            'is_platform_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return [$tenant, $user];
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function createTenantUser(): array
    {
        $tenant = Tenant::create([
            'name' => 'Carbon Studio Detail',
            'slug' => 'carbon-studio',
        ]);

        $user = User::factory()->create([
            'is_platform_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return [$tenant, $user];
    }
}
