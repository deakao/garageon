<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_register_sale_from_cockpit(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();
        $secondService = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Higienização Interna',
            'slug' => 'higienizacao-interna',
            'description' => 'Limpeza técnica do interior.',
            'duration_minutes' => 120,
            'price' => 220,
            'loyalty_points' => 30,
            'category' => 'Interior',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('sales.store'), [
                '_form' => 'sale',
                'customer_name' => 'Marina Souza',
                'customer_phone' => '(11) 98888-1234',
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 1],
                    ['service_id' => $secondService->id, 'quantity' => 2],
                ],
                'vehicle_plate' => 'ABC1D23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla',
                'vehicle_year' => 2022,
                'vehicle_color' => 'Prata',
                'sold_date' => now()->toDateString(),
                'sold_time' => '14:30',
                'amount' => 589,
                'payment_method' => 'pix',
                'notes' => 'Venda registrada no balcão.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotes', [
            'tenant_id' => $tenant->id,
            'status' => 'approved',
            'total' => 589,
            'payment_method' => 'pix',
        ]);

        $quote = Quote::firstOrFail();

        $this->assertSame('ABC1D23', $quote->vehicle->plate);
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'unit_price' => 149,
        ]);
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote->id,
            'service_id' => $secondService->id,
            'quantity' => 2,
            'unit_price' => 220,
        ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'name' => 'Marina Souza',
            'phone' => '(11) 98888-1234',
        ]);

        $this->assertDatabaseHas('loyalty_ledger', [
            'tenant_id' => $tenant->id,
            'type' => 'earn',
            'points' => 75,
        ]);

        $this->get(route('customers.index'))->assertOk()->assertSee('75 pts');
        $this->get(route('dashboard'))->assertOk()->assertSee('75 pts');
    }

    public function test_sale_can_debit_customer_loyalty_points(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();
        $customer = $tenant->customers()->create([
            'name' => 'Marina Souza',
            'phone' => '(11) 98888-1234',
        ]);
        $customer->loyaltyLedger()->create([
            'tenant_id' => $tenant->id,
            'type' => 'earn',
            'points' => 100,
            'reason' => 'Saldo inicial',
        ]);

        $this->actingAs($user)
            ->post(route('sales.store'), [
                '_form' => 'sale',
                'customer_name' => 'Marina Souza',
                'customer_phone' => '(11) 98888-1234',
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 1],
                ],
                'vehicle_plate' => 'NEW1D23',
                'vehicle_brand' => 'Honda',
                'vehicle_model' => 'Civic',
                'sold_date' => now()->toDateString(),
                'sold_time' => '15:00',
                'amount' => 149,
                'loyalty_points_to_debit' => 40,
                'payment_method' => 'pix',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $quote = Quote::query()->latest()->firstOrFail();

        $this->assertSame($customer->id, $quote->customer_id);
        $this->assertSame(75, (int) $customer->loyaltyLedger()->sum('points'));
        $this->assertDatabaseHas('loyalty_ledger', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'type' => 'redeem',
            'points' => -40,
        ]);
    }

    public function test_sale_does_not_debit_more_points_than_customer_has(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();
        $customer = $tenant->customers()->create([
            'name' => 'Marina Souza',
            'phone' => '(11) 98888-1234',
        ]);
        $customer->loyaltyLedger()->create([
            'tenant_id' => $tenant->id,
            'type' => 'earn',
            'points' => 10,
            'reason' => 'Saldo inicial',
        ]);

        $this->actingAs($user)
            ->post(route('sales.store'), [
                '_form' => 'sale',
                'customer_name' => 'Marina Souza',
                'customer_phone' => '(11) 98888-1234',
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 1],
                ],
                'vehicle_plate' => 'NEW1D23',
                'vehicle_brand' => 'Honda',
                'vehicle_model' => 'Civic',
                'sold_date' => now()->toDateString(),
                'sold_time' => '15:00',
                'amount' => 149,
                'loyalty_points_to_debit' => 40,
                'payment_method' => 'pix',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('loyalty_points_to_debit');

        $this->assertDatabaseCount('quotes', 0);
        $this->assertSame(10, (int) $customer->loyaltyLedger()->sum('points'));
    }

    /**
     * @return array{Tenant, User, Service}
     */
    private function createTenantWithService(): array
    {
        $tenant = Tenant::create([
            'name' => 'Carbon Studio Detail',
            'slug' => 'carbon-studio',
        ]);

        $user = User::factory()->create([
            'is_platform_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'slug' => 'lavagem-premium',
            'description' => 'Lavagem técnica completa.',
            'duration_minutes' => 90,
            'price' => 149,
            'loyalty_points' => 15,
            'category' => 'Lavagem',
            'is_active' => true,
        ]);

        return [$tenant, $user, $service];
    }
}
