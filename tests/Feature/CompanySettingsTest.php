<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_upload_store_logo(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->put(route('settings.company.update'), [
                'name' => 'Carbon Studio Detail',
                'legal_name' => 'Carbon Studio Detail LTDA',
                'document' => '12.345.678/0001-90',
                'whatsapp_phone' => '+55 11 98888-4400',
                'primary_domain' => 'carbon.garageon.test',
                'logo' => UploadedFile::fake()->image('logo.png', 320, 160),
            ])->assertRedirect();

        $tenant->refresh();

        $this->assertNotNull($tenant->logo_path);
        Storage::disk('public')->assertExists($tenant->logo_path);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Logo da Carbon Studio Detail');

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertSee('Logo da Carbon Studio Detail');
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
