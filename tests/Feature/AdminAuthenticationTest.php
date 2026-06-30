<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_redirects_guests_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }

    public function test_platform_admin_can_login_and_access_admin_panel(): void
    {
        User::factory()->create([
            'email' => 'admin@garageon.test',
            'password' => 'password',
            'is_platform_admin' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'admin@garageon.test',
            'password' => 'password',
        ])->assertRedirect(route('admin'));

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee('Gestão de lojas, planos e mensalidades');
    }

    public function test_tenant_user_can_login_and_access_client_dashboard(): void
    {
        $plan = Plan::create([
            'name' => 'Performance',
            'slug' => 'performance',
            'monthly_price' => 497,
            'locations_limit' => 1,
        ]);

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'name' => 'Carbon Studio Detail',
            'slug' => 'carbon-studio',
        ]);

        $user = User::factory()->create([
            'email' => 'gestor@garageon.test',
            'password' => 'password',
            'is_platform_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        $this->post(route('login'), [
            'email' => 'gestor@garageon.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Carbon Studio Detail já está ON');
    }

    public function test_authenticated_non_platform_admin_gets_forbidden(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertForbidden();
    }

    public function test_password_recovery_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar senha');
    }

    public function test_platform_admin_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'admin@garageon.test',
            'is_platform_admin' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => 'admin@garageon.test',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_non_platform_user_does_not_receive_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'gestor@garageon.test',
            'is_platform_admin' => false,
        ]);

        $this->post(route('password.email'), [
            'email' => 'gestor@garageon.test',
        ])->assertSessionHas('status');

        Notification::assertNothingSentTo($user);
    }

    public function test_platform_admin_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@garageon.test',
            'password' => 'password',
            'is_platform_admin' => true,
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin@garageon.test',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }
}
