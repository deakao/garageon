<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_a_quick_service(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->postJson(route('services.quick-store'), [
                'name' => 'Higienização Interna',
                'price' => 249.90,
                'description' => 'Limpeza completa do interior.',
            ])
            ->assertCreated()
            ->assertJsonPath('service.name', 'Higienização Interna')
            ->assertJsonPath('service.duration_minutes', 60);

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Higienização Interna',
            'description' => 'Limpeza completa do interior.',
            'price' => 249.90,
            'duration_minutes' => 60,
            'category' => 'Sem categoria',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('tenant_service_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Sem categoria',
        ]);
    }

    public function test_quick_service_modal_is_available_in_quotes_and_appointments(): void
    {
        [, $user] = $this->createTenantUser();

        foreach ([route('dashboard'), route('quotes.index')] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('Cadastro rápido')
                ->assertSee(route('services.quick-store'), false);
        }
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

        $user = User::factory()->create(['is_platform_admin' => false]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return [$tenant, $user];
    }
}
