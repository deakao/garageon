<?php

namespace Tests\Feature;

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
