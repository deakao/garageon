<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_password_and_is_redirected_to_dashboard_logged_in(): void
    {
        $this->post(route('signup.store'), [
            'owner_name' => 'Dono Carbon',
            'business_name' => 'Carbon Detail',
            'email' => 'dono@carbon.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'whatsapp_phone' => '(11) 99999-9999',
            'business_type' => 'Detailing',
            'monthly_leads' => '51 a 150',
            'main_challenge' => 'Organizar agenda',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'dono@carbon.test')->firstOrFail();
        $tenant = Tenant::where('slug', 'carbon-detail')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertTrue($tenant->users()->whereKey($user->id)->exists());
        $this->assertSame(3, $tenant->serviceCategories()->count());
        $this->assertDatabaseHas('signup_requests', [
            'email' => 'dono@carbon.test',
            'business_name' => 'Carbon Detail',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Carbon Detail já está ON.');
    }

    public function test_signup_requires_a_confirmed_password(): void
    {
        $this->post(route('signup.store'), [
            'owner_name' => 'Dono Carbon',
            'business_name' => 'Carbon Detail',
            'email' => 'dono@carbon.test',
            'password' => 'secure-password',
            'password_confirmation' => 'outra-senha',
            'whatsapp_phone' => '(11) 99999-9999',
            'business_type' => 'Detailing',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tenants', 0);
    }
}
