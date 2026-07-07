<?php

namespace Tests\Feature;

use App\Ai\Tools\ListServices;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ListServicesToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_services_with_price(): void
    {
        $tenant = Tenant::create(['name' => 'Carbon Studio', 'slug' => 'carbon-studio']);

        Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Vitrificação',
            'slug' => 'vitrificacao',
            'duration_minutes' => 120,
            'price' => 899.90,
            'category' => 'Proteção',
            'is_active' => true,
        ]);

        Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Serviço desativado',
            'slug' => 'servico-desativado',
            'duration_minutes' => 30,
            'price' => 50,
            'is_active' => false,
        ]);

        $result = (string) (new ListServices($tenant))->handle(new Request);
        $data = json_decode($result, true);

        $this->assertCount(1, $data['services']);
        $this->assertSame('Vitrificação', $data['services'][0]['name']);
        $this->assertSame('R$ 899,90', $data['services'][0]['price']);
        $this->assertSame(120, $data['services'][0]['duration_minutes']);
    }

    public function test_returns_message_when_no_services(): void
    {
        $tenant = Tenant::create(['name' => 'Vazia', 'slug' => 'vazia']);

        $result = (string) (new ListServices($tenant))->handle(new Request);

        $this->assertStringContainsString('não cadastrou serviços', $result);
    }
}
