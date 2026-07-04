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

        $this->actingAs($user)
            ->post(route('appointments.store'), [
                'customer_name' => 'Rafael Nogueira',
                'customer_phone' => '+55 11 97777-1001',
                'vehicle_plate' => 'abc-1d23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
                'vehicle_year' => 2024,
                'vehicle_color' => 'Preto',
                'service_id' => $service->id,
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
