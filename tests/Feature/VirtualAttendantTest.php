<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VirtualAttendant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualAttendantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_open_attendant_settings(): void
    {
        [, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->get(route('settings.attendant'))
            ->assertOk()
            ->assertSee('Piloto automático')
            ->assertSee('Tom de voz');
    }

    public function test_user_can_create_attendant_with_api_key(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->put(route('settings.attendant.update'), [
                'name' => 'Duda',
                'tone' => 'objective',
                'provider' => 'anthropic',
                'model' => '',
                'api_key' => 'sk-secret-123',
                'context' => 'Fechamos aos domingos.',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $attendant = $tenant->virtualAttendant()->firstOrFail();

        $this->assertSame('Duda', $attendant->name);
        $this->assertSame('objective', $attendant->tone->value);
        $this->assertSame('anthropic', $attendant->provider->value);
        $this->assertTrue($attendant->is_active);
        $this->assertSame('sk-secret-123', $attendant->api_key);

        // A chave deve ser persistida criptografada.
        $this->assertDatabaseMissing('virtual_attendants', ['api_key' => 'sk-secret-123']);
    }

    public function test_activating_without_api_key_is_blocked(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->put(route('settings.attendant.update'), [
                'name' => 'Duda',
                'tone' => 'friendly',
                'provider' => 'openai',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('api_key');

        $this->assertDatabaseMissing('virtual_attendants', ['tenant_id' => $tenant->id]);
    }

    public function test_editing_without_api_key_keeps_existing_one(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $attendant = VirtualAttendant::create([
            'tenant_id' => $tenant->id,
            'name' => 'Duda',
            'tone' => 'friendly',
            'provider' => 'openai',
            'api_key' => 'sk-original',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('settings.attendant.update'), [
                'name' => 'Duda Turbo',
                'tone' => 'enthusiastic',
                'provider' => 'openai',
                'api_key' => '',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $attendant->refresh();

        $this->assertSame('Duda Turbo', $attendant->name);
        $this->assertSame('sk-original', $attendant->api_key);
        $this->assertTrue($attendant->is_active);
    }

    public function test_byok_plan_requires_own_key_even_with_platform_key(): void
    {
        config(['ai.providers.openai.key' => 'platform-key']);

        [$tenant, $user] = $this->createTenantUser();
        $plan = Plan::create([
            'name' => 'Autonomia', 'slug' => 'autonomia', 'monthly_price' => 97,
            'ai_daily_message_limit' => 0, 'requires_own_key' => true,
        ]);
        $tenant->update(['plan_id' => $plan->id]);

        // Sem chave própria: mesmo havendo chave da plataforma, o plano BYOK bloqueia.
        $this->actingAs($user)
            ->put(route('settings.attendant.update'), [
                'name' => 'Duda', 'tone' => 'friendly', 'provider' => 'openai', 'is_active' => '1',
            ])
            ->assertSessionHasErrors('api_key');

        // Com chave própria: liga normalmente.
        $this->actingAs($user)
            ->put(route('settings.attendant.update'), [
                'name' => 'Duda', 'tone' => 'friendly', 'provider' => 'openai',
                'api_key' => 'sk-tenant', 'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($tenant->virtualAttendant()->firstOrFail()->is_active);
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
