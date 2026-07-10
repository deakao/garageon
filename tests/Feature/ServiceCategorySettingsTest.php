<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceCategorySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_manage_service_categories_on_services_screen(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->get(route('settings.services'))
            ->assertOk()
            ->assertSee('Categorias')
            ->assertSee('Nenhuma categoria cadastrada');

        $this->post(route('settings.service-categories.store'), [
            'name' => 'Lavagem Técnica',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenant_service_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Técnica',
        ]);
    }

    public function test_services_use_registered_categories_and_follow_category_renames(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->createTenantUser();

        $category = TenantServiceCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem',
            'slug' => 'lavagem',
        ]);

        $this->actingAs($user)
            ->post(route('settings.services.store'), [
                'name' => 'Lavagem Premium',
                'description' => 'Processo técnico completo.',
                'thumbnail' => UploadedFile::fake()->image('lavagem.jpg', 900, 600),
                'duration_minutes' => 90,
                'price' => 149,
                'loyalty_points' => 15,
                'lifecycle_days' => 30,
                'category' => 'Lavagem',
                'is_active' => '1',
            ])->assertRedirect();

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'category' => 'Lavagem',
            'loyalty_points' => 15,
        ]);

        $service = $tenant->services()->where('name', 'Lavagem Premium')->firstOrFail();
        $originalThumbnailPath = $service->thumbnail_path;

        $this->assertNotNull($originalThumbnailPath);
        Storage::disk('public')->assertExists($originalThumbnailPath);

        $this->put(route('settings.service-categories.update', $category), [
            'name' => 'Lavagem Técnica',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenant_service_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Técnica',
        ]);

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'category' => 'Lavagem Técnica',
        ]);

        $this->put(route('settings.services.update', $service), [
            'name' => 'Lavagem Premium Black',
            'description' => 'Processo técnico completo com acabamento premium.',
            'thumbnail' => UploadedFile::fake()->image('lavagem-black.png', 900, 600),
            'duration_minutes' => 120,
            'price' => 199,
            'loyalty_points' => 25,
            'lifecycle_days' => 45,
            'category' => 'Lavagem Técnica',
            'is_active' => '1',
        ])->assertRedirect();

        $service->refresh();

        Storage::disk('public')->assertMissing($originalThumbnailPath);
        Storage::disk('public')->assertExists($service->thumbnail_path);
        $this->assertSame(25, $service->loyalty_points);

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertSee('Lavagem Premium Black')
            ->assertSee($service->thumbnailUrl());
    }

    public function test_category_with_services_cannot_be_deleted(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $category = TenantServiceCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Proteção',
            'slug' => 'protecao',
        ]);

        $tenant->services()->create([
            'name' => 'Vitrificação 9H',
            'slug' => 'vitrificacao-9h',
            'description' => 'Proteção cerâmica.',
            'duration_minutes' => 480,
            'price' => 1890,
            'lifecycle_days' => 120,
            'category' => 'Proteção',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('settings.service-categories.destroy', $category))
            ->assertSessionHasErrors('delete', null, 'categories');

        $this->assertDatabaseHas('tenant_service_categories', [
            'id' => $category->id,
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
