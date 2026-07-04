<?php

namespace Tests\Feature;

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

        $this->actingAs($user)
            ->post(route('sales.store'), [
                '_form' => 'sale',
                'customer_name' => 'Marina Souza',
                'customer_phone' => '(11) 98888-1234',
                'service_id' => $service->id,
                'vehicle_plate' => 'ABC1D23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla',
                'vehicle_year' => 2022,
                'vehicle_color' => 'Prata',
                'sold_date' => now()->toDateString(),
                'sold_time' => '14:30',
                'amount' => 189.90,
                'payment_method' => 'pix',
                'notes' => 'Venda registrada no balcão.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotes', [
            'tenant_id' => $tenant->id,
            'status' => 'approved',
            'total' => 189.90,
            'payment_method' => 'pix',
        ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'name' => 'Marina Souza',
            'phone' => '(11) 98888-1234',
        ]);
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
            'category' => 'Lavagem',
            'is_active' => true,
        ]);

        return [$tenant, $user, $service];
    }
}
