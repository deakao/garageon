<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_list_quotes(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $this->actingAs($user)
            ->get(route('quotes.index'))
            ->assertOk()
            ->assertSee('Funil de orçamentos')
            ->assertSee('Enviado')
            ->assertSee('Aguardando')
            ->assertSee('Aceito')
            ->assertSee('Expirado')
            ->assertSee('Marina Souza')
            ->assertSee('ABC1D23');
    }

    public function test_tenant_user_can_update_quote_status_from_kanban(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('quotes.status', $quote), ['status' => 'pending'])
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
            ]);

        $this->assertSame('pending', $quote->fresh()->status);

        $this->actingAs($user)
            ->patchJson(route('quotes.status', $quote), ['status' => 'accepted'])
            ->assertOk()
            ->assertJson([
                'status' => 'accepted',
            ]);

        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_tenant_user_can_update_quote(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->put(route('quotes.update', $quote), [
                'status' => 'accepted',
                'customer_name' => 'Marina Atualizada',
                'customer_phone' => '(11) 98888-1234',
                'vehicle_plate' => 'ABC1D23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
                'vehicle_year' => 2023,
                'vehicle_color' => 'Branco',
                'quoted_date' => now()->toDateString(),
                'quoted_time' => '11:00',
                'valid_until' => now()->addDays(10)->toDateString(),
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 2],
                ],
                'notes' => 'Cliente aprovou condições.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $quote->refresh();

        $this->assertSame('accepted', $quote->status);
        $this->assertSame(298.0, (float) $quote->total);
        $this->assertSame('Marina Atualizada', $quote->customer->name);
        $this->assertSame('Corolla Cross', $quote->vehicle->model);
        $this->assertCount(1, $quote->items);
        $this->assertSame(2, $quote->items->first()->quantity);
    }

    public function test_tenant_user_can_delete_quote(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('quotes.destroy', $quote))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
        $this->assertDatabaseCount('quote_items', 0);
    }

    public function test_approved_sales_are_not_listed_as_quotes(): void
    {
        [$tenant, $user, $service] = $this->createTenantWithService();

        $customer = $tenant->customers()->create([
            'name' => 'Cliente Venda',
            'phone' => '11999991111',
        ]);

        Quote::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'status' => 'approved',
            'total' => 149,
            'channel' => 'cockpit',
            'payment_method' => 'pix',
        ]);

        $this->actingAs($user)
            ->get(route('quotes.index'))
            ->assertOk()
            ->assertDontSee('Cliente Venda');
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

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(Service $service): array
    {
        return [
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
                ['service_id' => $service->id, 'quantity' => 1],
            ],
            'notes' => 'Proposta inicial.',
        ];
    }
}
