<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_quote_with_multiple_services_and_view_page(): void
    {
        [$tenant, $user, $serviceA, $serviceB] = $this->createTenantWithServices();

        $response = $this->actingAs($user)
            ->post(route('quotes.store'), [
                '_form' => 'quote',
                'customer_name' => 'Marina Souza',
                'customer_phone' => '(11) 98888-1234',
                'vehicle_plate' => 'ABC1D23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla',
                'vehicle_year' => 2022,
                'vehicle_color' => 'Prata',
                'quoted_date' => now()->toDateString(),
                'quoted_time' => '10:30',
                'services' => [
                    ['service_id' => $serviceA->id, 'quantity' => 1],
                    ['service_id' => $serviceB->id, 'quantity' => 2],
                ],
                'notes' => 'Pacote completo com vitrificação.',
            ]);

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $response
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotes', [
            'tenant_id' => $tenant->id,
            'status' => 'sent',
            'total' => 149 + (89 * 2),
            'channel' => 'cockpit',
        ]);

        $this->assertDatabaseCount('quote_items', 2);

        $this->assertNotNull($quote->public_token);

        $this->actingAs($user)
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertSee('Compartilhar orçamento')
            ->assertSee('Marina Souza')
            ->assertSee('ABC1D23')
            ->assertSee('Lavagem Premium')
            ->assertSee('Polimento Técnico')
            ->assertSee($quote->publicUrl());
    }

    public function test_quote_can_be_viewed_publicly_via_share_token(): void
    {
        [$tenant, $user, $serviceA] = $this->createTenantWithServices();

        $this->actingAs($user)->post(route('quotes.store'), [
            '_form' => 'quote',
            'customer_name' => 'Rafael Lima',
            'customer_phone' => '(11) 97777-0000',
            'vehicle_plate' => 'XYZ9K88',
            'vehicle_brand' => 'Honda',
            'vehicle_model' => 'Civic',
            'quoted_date' => now()->toDateString(),
            'quoted_time' => '09:00',
            'services' => [
                ['service_id' => $serviceA->id, 'quantity' => 1],
            ],
        ]);

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->get(route('quotes.public', $quote->public_token))
            ->assertOk()
            ->assertSee('Rafael Lima')
            ->assertSee('XYZ9K88')
            ->assertSee('Lavagem Premium');

        $this->get(route('quotes.public', 'token-inexistente'))
            ->assertNotFound();
    }

    public function test_tenant_cannot_view_quote_from_another_tenant(): void
    {
        [$tenantA, $userA] = $this->createTenantWithUser('carbon-studio-a');
        [$tenantB, , $service] = $this->createTenantWithServices('carbon-studio-b');

        $quote = Quote::create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $tenantB->customers()->create([
                'name' => 'Cliente B',
                'phone' => '11999990000',
            ])->id,
            'status' => 'sent',
            'total' => $service->price,
            'channel' => 'cockpit',
        ]);

        $this->actingAs($userA)
            ->get(route('quotes.show', $quote))
            ->assertNotFound();
    }

    /**
     * @return array{Tenant, User, Service, Service}
     */
    private function createTenantWithServices(string $slug = 'carbon-studio'): array
    {
        [$tenant, $user] = $this->createTenantWithUser($slug);

        $serviceA = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'slug' => 'lavagem-premium',
            'description' => 'Lavagem técnica completa.',
            'duration_minutes' => 90,
            'price' => 149,
            'category' => 'Lavagem',
            'is_active' => true,
        ]);

        $serviceB = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Polimento Técnico',
            'slug' => 'polimento-tecnico',
            'description' => 'Correção leve de pintura.',
            'duration_minutes' => 180,
            'price' => 89,
            'category' => 'Estética',
            'is_active' => true,
        ]);

        return [$tenant, $user, $serviceA, $serviceB];
    }

    /**
     * @return array{Tenant, User}
     */
    private function createTenantWithUser(string $slug = 'carbon-studio'): array
    {
        $tenant = Tenant::create([
            'name' => 'Carbon Studio Detail',
            'slug' => $slug,
        ]);

        $user = User::factory()->create([
            'is_platform_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return [$tenant, $user];
    }
}
