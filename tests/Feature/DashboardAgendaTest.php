<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_endpoint_returns_rendered_panel_for_month_week_and_day_views(): void
    {
        [, $user] = $this->createTenantUser();

        foreach (['month', 'week', 'day'] as $view) {
            $this->actingAs($user)
                ->getJson('/dashboard/agenda?view='.$view.'&date=2026-07-15')
                ->assertOk()
                ->assertJsonStructure(['view', 'date', 'title', 'panel'])
                ->assertJson(['view' => $view]);
        }
    }

    public function test_agenda_endpoint_defaults_to_month_view_for_invalid_view(): void
    {
        [, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->getJson('/dashboard/agenda?view=invalid&date=2026-07-15')
            ->assertOk()
            ->assertJson(['view' => 'month']);
    }

    public function test_dashboard_renders_appointment_edit_and_delete_actions(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Marina Costa',
            'phone' => '(11) 98888-1000',
        ]);
        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Técnica',
            'slug' => 'lavagem-tecnica-dashboard-actions',
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
            'scheduled_at' => now()->setTime(9, 0),
            'ends_at' => now()->setTime(10, 30),
        ]);
        $appointment->serviceItems()->create([
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => 1,
            'duration_minutes' => $service->duration_minutes,
            'unit_price' => $service->price,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee('Excluir')
            ->assertSee(route('appointments.update', $appointment), false)
            ->assertSee(route('appointments.destroy', $appointment), false);
    }

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
