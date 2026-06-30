<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_has_link_to_edit_store_landing_page(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Editar landing page da loja')
            ->assertSee(route('settings.landing'), false);
    }

    public function test_tenant_user_can_update_store_landing_page(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->put(route('settings.landing.update'), [
                'headline' => 'Proteção premium para carros exigentes',
                'subheadline' => 'Agendamento online para lavagem técnica, vitrificação e manutenção.',
                'cta_label' => 'Reservar agora',
                'sections' => [
                    ['title' => 'Brilho de showroom', 'body' => 'Processos profissionais para entregar acabamento premium.'],
                    ['title' => 'Retorno programado', 'body' => 'A loja chama o cliente de volta na hora certa.'],
                ],
                'published' => '1',
            ])->assertRedirect();

        $this->assertDatabaseHas('landing_pages', [
            'tenant_id' => $tenant->id,
            'headline' => 'Proteção premium para carros exigentes',
            'cta_label' => 'Reservar agora',
        ]);

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertSee('Proteção premium para carros exigentes')
            ->assertSee('Reservar agora');
    }

    /**
     * @return array{Tenant, User}
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
