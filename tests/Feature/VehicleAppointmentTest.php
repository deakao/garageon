<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VehicleAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_plate_lookup_returns_vehicle_data_from_api(): void
    {
        config([
            'services.vehicle_plate.provider' => 'apiplacas',
            'services.vehicle_plate.apiplacas_token' => 'test-token',
        ]);

        Http::fake([
            'wdapi2.com.br/consulta/ABC1D23/test-token' => Http::response([
                'marca' => 'Toyota',
                'modelo' => 'Corolla Cross',
                'anoModelo' => 2024,
                'cor' => 'Preto',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->getJson(route('vehicles.lookup', ['plate' => 'abc-1d23']))
            ->assertOk()
            ->assertJson([
                'plate' => 'ABC1D23',
                'brand' => 'Toyota',
                'model' => 'Corolla Cross',
                'year' => 2024,
                'color' => 'Preto',
                'source' => 'apiplacas',
            ]);

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_plate_lookup_returns_customer_and_vehicle_from_tenant_database(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Marina Costa',
            'phone' => '+55 11 98888-1000',
            'email' => 'marina@example.com',
        ]);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate' => 'ABC1D23',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'color' => 'Preto',
        ]);

        $this->actingAs($user)
            ->getJson(route('vehicles.lookup', ['plate' => 'abc-1d23']))
            ->assertOk()
            ->assertJson([
                'plate' => 'ABC1D23',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2022,
                'color' => 'Preto',
                'source' => 'garageon',
                'customer_name' => 'Marina Costa',
                'customer_phone' => '+55 11 98888-1000',
                'customer_email' => 'marina@example.com',
            ]);
    }

    public function test_plate_lookup_can_use_brasil_dados_provider(): void
    {
        config([
            'services.vehicle_plate.provider' => 'brasildados',
            'services.vehicle_plate.brasildados_token' => 'test-token',
        ]);

        Http::fake([
            'brasildados--consulta-veiculo-por-placa.apify.actor/check' => Http::response([
                [
                    'placaConsultada' => 'ABC1D23',
                    'encontrado' => true,
                    'veiculo' => [
                        'placa' => 'ABC1D23',
                        'marca' => 'Volkswagen',
                        'modelo' => 'Gol 1.0',
                        'anoModelo' => '2024',
                        'cor' => 'Branca',
                    ],
                ],
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->getJson(route('vehicles.lookup', ['plate' => 'ABC1D23']))
            ->assertOk()
            ->assertJson([
                'plate' => 'ABC1D23',
                'brand' => 'Volkswagen',
                'model' => 'Gol 1.0',
                'year' => 2024,
                'color' => 'Branca',
                'source' => 'brasildados',
            ]);
    }

    public function test_appointment_creates_customer_vehicle_and_links_vehicle_to_schedule(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Técnica',
            'slug' => 'lavagem-tecnica',
            'duration_minutes' => 90,
            'price' => 149,
            'category' => 'Lavagem',
        ]);
        $secondService = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Polimento Comercial',
            'slug' => 'polimento-comercial',
            'duration_minutes' => 120,
            'price' => 390,
            'category' => 'Pintura',
        ]);

        $this->actingAs($user)
            ->post(route('appointments.store'), [
                'customer_name' => 'Rafael Nogueira',
                'customer_phone' => '+55 11 97777-1001',
                'vehicle_plate' => 'abc-1d23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
                'vehicle_year' => 2024,
                'vehicle_color' => 'Preto',
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 1],
                    ['service_id' => $secondService->id, 'quantity' => 1],
                ],
                'scheduled_date' => now()->addDay()->toDateString(),
                'scheduled_time' => '09:00',
            ])->assertRedirect();

        $vehicle = Vehicle::firstOrFail();
        $appointment = Appointment::firstOrFail();

        $this->assertSame($tenant->id, $vehicle->tenant_id);
        $this->assertSame('ABC1D23', $vehicle->plate);
        $this->assertSame('Toyota', $vehicle->brand);
        $this->assertSame('Corolla Cross', $vehicle->model);
        $this->assertSame(2024, $vehicle->year);
        $this->assertSame($vehicle->id, $appointment->vehicle_id);
        $this->assertSame($appointment->customer_id, $vehicle->customer_id);
        $this->assertSame($service->id, $appointment->service_id);
        $this->assertTrue($appointment->scheduled_at->copy()->addMinutes(210)->equalTo($appointment->ends_at));
        $this->assertDatabaseHas('appointment_services', ['appointment_id' => $appointment->id, 'service_id' => $service->id]);
        $this->assertDatabaseHas('appointment_services', ['appointment_id' => $appointment->id, 'service_id' => $secondService->id]);
    }

    public function test_appointment_does_not_add_service_loyalty_points_to_customer(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Vitrificação 9H',
            'slug' => 'vitrificacao-9h',
            'duration_minutes' => 480,
            'price' => 1890,
            'loyalty_points' => 190,
            'category' => 'Proteção',
        ]);

        $this->actingAs($user)
            ->post(route('appointments.store'), [
                'customer_name' => 'Rafael Nogueira',
                'customer_phone' => '+55 11 97777-1001',
                'vehicle_plate' => 'abc-1d23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
                'services' => [
                    ['service_id' => $service->id, 'quantity' => 1],
                ],
                'scheduled_date' => now()->addDay()->toDateString(),
                'scheduled_time' => '09:00',
            ])->assertRedirect();

        $this->assertDatabaseCount('loyalty_ledger', 0);
    }

    public function test_tenant_user_can_update_appointment_from_dashboard(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rafael Nogueira',
            'phone' => '(11) 97777-1001',
        ]);
        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate' => 'ABC1D23',
            'brand' => 'Toyota',
            'model' => 'Corolla Cross',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Técnica',
            'slug' => 'lavagem-tecnica-update',
            'duration_minutes' => 90,
            'price' => 149,
            'category' => 'Lavagem',
        ]);
        $secondService = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Higienização Interna',
            'slug' => 'higienizacao-interna-update',
            'duration_minutes' => 120,
            'price' => 290,
            'category' => 'Interior',
        ]);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'vehicle_id' => $vehicle->id,
            'source' => 'manual',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
        ]);

        $this->actingAs($user)
            ->put(route('appointments.update', $appointment), [
                'customer_name' => 'Rafael Nogueira Premium',
                'customer_phone' => '(11) 98888-1001',
                'vehicle_plate' => 'xyz-9z99',
                'vehicle_brand' => 'BMW',
                'vehicle_model' => '320i',
                'vehicle_year' => 2023,
                'vehicle_color' => 'Azul',
                'services' => [
                    ['service_id' => $secondService->id, 'quantity' => 2],
                ],
                'scheduled_date' => now()->addDays(2)->toDateString(),
                'scheduled_time' => '13:00',
                'status' => 'completed',
                'notes' => 'Cliente pediu retirada no fim do dia.',
            ])->assertRedirect();

        $appointment->refresh();
        $customer->refresh();

        $this->assertSame('Rafael Nogueira Premium', $customer->name);
        $this->assertSame('(11) 98888-1001', $customer->phone);
        $this->assertSame($secondService->id, $appointment->service_id);
        $this->assertSame('completed', $appointment->status);
        $this->assertSame('Cliente pediu retirada no fim do dia.', $appointment->notes);
        $this->assertTrue($appointment->scheduled_at->copy()->addMinutes(240)->equalTo($appointment->ends_at));
        $this->assertDatabaseHas('vehicles', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate' => 'XYZ9Z99',
            'brand' => 'BMW',
            'model' => '320i',
            'year' => 2023,
            'color' => 'Azul',
        ]);
        $this->assertDatabaseHas('appointment_services', [
            'appointment_id' => $appointment->id,
            'service_id' => $secondService->id,
            'quantity' => 2,
        ]);
    }

    public function test_tenant_user_can_delete_appointment_from_dashboard(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rafael Nogueira',
            'phone' => '(11) 97777-1001',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Técnica',
            'slug' => 'lavagem-tecnica-delete',
            'duration_minutes' => 90,
            'price' => 149,
            'category' => 'Lavagem',
        ]);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'source' => 'manual',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
        ]);
        $appointment->serviceItems()->create([
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => 1,
            'duration_minutes' => $service->duration_minutes,
            'unit_price' => $service->price,
        ]);

        $this->actingAs($user)
            ->delete(route('appointments.destroy', $appointment))
            ->assertRedirect();

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('appointment_services', ['appointment_id' => $appointment->id]);
    }

    public function test_customer_crud_creates_vehicle_with_year_and_color(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'name' => 'Marina Costa',
                'phone' => '+55 11 98888-1000',
                'email' => 'marina@example.com',
                'marketing_consent' => '1',
                'vehicles' => [
                    [
                        'plate' => 'abc-1d23',
                        'brand' => 'Toyota',
                        'model' => 'Corolla',
                        'year' => '2024',
                        'color' => 'Preto',
                    ],
                ],
            ])->assertRedirect();

        $vehicle = Vehicle::firstOrFail();

        $this->assertSame($tenant->id, $vehicle->tenant_id);
        $this->assertSame('ABC1D23', $vehicle->plate);
        $this->assertSame(2024, $vehicle->year);
        $this->assertSame('Preto', $vehicle->color);
    }

    public function test_customer_crud_updates_vehicle_year_and_color(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Marina Costa',
            'phone' => '+55 11 98888-1000',
            'email' => 'marina@example.com',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plate' => 'ABC1D23',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'color' => 'Prata',
        ]);

        $this->actingAs($user)
            ->put(route('customers.update', $customer), [
                'name' => 'Marina Costa',
                'phone' => '+55 11 98888-1000',
                'email' => 'marina@example.com',
                'marketing_consent' => '1',
                'vehicles' => [
                    [
                        'id' => $vehicle->id,
                        'plate' => 'abc-1d23',
                        'brand' => 'Toyota',
                        'model' => 'Corolla',
                        'year' => '2025',
                        'color' => 'Branco',
                    ],
                ],
            ])->assertRedirect();

        $vehicle->refresh();

        $this->assertSame(2025, $vehicle->year);
        $this->assertSame('Branco', $vehicle->color);
    }

    public function test_customer_points_can_be_adjusted_from_customer_edit(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Marina Costa',
            'phone' => '+55 11 98888-1000',
            'email' => 'marina@example.com',
        ]);
        $customer->loyaltyLedger()->create([
            'tenant_id' => $tenant->id,
            'type' => 'earn',
            'points' => 50,
            'reason' => 'Saldo inicial',
        ]);

        $this->actingAs($user)
            ->put(route('customers.update', $customer), [
                'name' => 'Marina Costa',
                'phone' => '+55 11 98888-1000',
                'email' => 'marina@example.com',
                'marketing_consent' => '1',
                'loyalty_points' => 20,
            ])->assertRedirect();

        $this->assertSame(20, (int) $customer->loyaltyLedger()->sum('points'));
        $this->assertDatabaseHas('loyalty_ledger', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'type' => 'adjustment',
            'points' => -30,
        ]);
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
