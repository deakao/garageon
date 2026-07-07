<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_open_whatsapp_chat(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Marina Azevedo',
            'phone' => '+55 11 96666-2002',
        ]);

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Atendimento em tempo real')
            ->assertSee('Conectar')
            ->assertDontSee('Webhook')
            ->assertDontSee('Nome da instância')
            ->assertSee('Marina Azevedo');
    }

    public function test_chat_does_not_render_saved_qrcode_without_instance(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'status' => 'qrcode',
            'qrcode' => 'data:image/png;base64,stale-qr-code',
            'qrcode_code' => 'stale-code',
        ]);

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertDontSee('data:image/png;base64,stale-qr-code');

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'unconfigured',
            'qrcode' => null,
            'qrcode_code' => null,
        ]);
    }

    public function test_connect_button_creates_instance_without_saving_qrcode(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        [$tenant, $user] = $this->createTenantUser();

        Http::fake([
            'https://evolution.test/instance/all' => Http::response([
                'data' => [],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/create' => Http::response([
                'data' => [
                    'id' => 'instance-1',
                    'name' => 'garageon-'.$tenant->id,
                    'token' => 'instance-token',
                    'connected' => false,
                ],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response([
                'data' => [
                    'webhookUrl' => 'https://garageon.test/webhook',
                ],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,qr-code',
                    'Code' => 'qr-text',
                ],
                'message' => 'success',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('chat.connect'))
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-'.$tenant->id,
            'instance_id' => 'instance-1',
            'status' => 'qrcode',
            'qrcode' => null,
            'qrcode_code' => null,
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/create'
            && $request['name'] === 'garageon-'.$tenant->id
            && filled($request['token'])
            && $request['advancedSettings']['alwaysOnline'] === true
            && $request['advancedSettings']['ignoreGroups'] === true);
        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/connect'
            && $request->hasHeader('apikey', 'instance-token')
            && $request->hasHeader('instanceId', 'instance-1')
            && $request['immediate'] === true
            && in_array('QRCODE', $request['subscribe'], true));
        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/qr'
            && $request->hasHeader('apikey', 'instance-token')
            && $request->hasHeader('instanceId', 'instance-1'));
    }

    public function test_connect_returns_json_state_for_async_ui(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        [$tenant, $user] = $this->createTenantUser();

        Http::fake([
            'https://evolution.test/instance/all' => Http::response([
                'data' => [],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/create' => Http::response([
                'data' => [
                    'id' => 'instance-1',
                    'name' => 'garageon-'.$tenant->id,
                    'token' => 'instance-token',
                    'connected' => false,
                ],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response(['message' => 'success']),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,async-qr-code',
                ],
                'message' => 'success',
            ]),
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.connect'))
            ->assertOk()
            ->assertJson([
                'status' => 'qrcode',
                'connected' => false,
                'qrcode' => 'data:image/png;base64,async-qr-code',
                'message' => 'Escaneie o QR Code no WhatsApp para deixar o atendimento ON.',
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'qrcode',
            'qrcode' => null,
            'qrcode_code' => null,
        ]);
    }

    public function test_connect_button_reuses_remote_instance_for_qrcode(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        [$tenant, $user] = $this->createTenantUser();

        Http::fake([
            'https://evolution.test/instance/all' => Http::response([
                'data' => [[
                    'id' => 'instance-1',
                    'name' => 'garageon-'.$tenant->id,
                    'token' => 'instance-token',
                    'connected' => false,
                ]],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response(['message' => 'success']),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,remote-qr-code',
                ],
                'message' => 'success',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('chat.connect'))
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'instance_id' => 'instance-1',
            'status' => 'qrcode',
            'qrcode' => null,
        ]);

        Http::assertNotSent(fn ($request) => $request->url() === 'https://evolution.test/instance/create');
    }

    public function test_connect_button_reuses_existing_instance_for_qrcode(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/all' => Http::response([
                'data' => [[
                    'id' => 'instance-1',
                    'name' => 'garageon-carbon',
                    'token' => 'instance-token',
                    'connected' => false,
                ]],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response(['message' => 'success']),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,new-qr-code',
                ],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'disconnected',
        ]);

        $this->actingAs($user)
            ->post(route('chat.connect'))
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'instance_id' => 'instance-1',
            'status' => 'qrcode',
            'qrcode' => null,
        ]);

        Http::assertNotSent(fn ($request) => $request->url() === 'https://evolution.test/instance/create');
    }

    public function test_connect_button_recovers_token_for_existing_instance(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/all' => Http::response([
                'data' => [[
                    'id' => 'instance-1',
                    'name' => 'garageon-carbon',
                    'token' => 'instance-token',
                    'connected' => false,
                ]],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response(['message' => 'success']),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,recovered-token-qr-code',
                ],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'status' => 'disconnected',
        ]);

        $this->actingAs($user)
            ->post(route('chat.connect'))
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'instance_id' => 'instance-1',
            'status' => 'qrcode',
            'qrcode' => null,
        ]);

        $this->assertSame('instance-token', $tenant->whatsappConnection()->first()->instance_token);
        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/connect'
            && $request->hasHeader('apikey', 'instance-token')
            && $request->hasHeader('instanceId', 'instance-1'));
    }

    public function test_connect_button_recreates_missing_remote_instance(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        [$tenant, $user] = $this->createTenantUser();

        Http::fake([
            'https://evolution.test/instance/all' => Http::response([
                'data' => [],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/create' => Http::response([
                'data' => [
                    'id' => 'instance-new',
                    'name' => 'garageon-'.$tenant->id,
                    'token' => 'new-instance-token',
                    'connected' => false,
                ],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response(['message' => 'success']),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,new-instance-qr-code',
                ],
                'message' => 'success',
            ]),
        ]);

        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-old-slug',
            'instance_id' => 'missing-instance',
            'instance_token' => 'stale-token',
            'status' => 'error',
        ]);

        $this->actingAs($user)
            ->post(route('chat.connect'))
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-'.$tenant->id,
            'instance_id' => 'instance-new',
            'status' => 'qrcode',
            'qrcode' => null,
        ]);

        $this->assertSame('new-instance-token', $tenant->whatsappConnection()->first()->instance_token);
        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/connect'
            && $request->hasHeader('apikey', 'new-instance-token')
            && $request->hasHeader('instanceId', 'instance-new'));
    }

    public function test_sync_returns_json_connected_status(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/status' => Http::response([
                'data' => ['Connected' => true],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'qrcode',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertOk()
            ->assertJson([
                'status' => 'connected',
                'connected' => true,
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'connected',
        ]);
    }

    public function test_sync_without_instance_clears_saved_qrcode(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'status' => 'qrcode',
            'qrcode' => 'data:image/png;base64,stale-qr-code',
            'qrcode_code' => 'stale-code',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertStatus(422)
            ->assertJson([
                'status' => 'unconfigured',
                'connected' => false,
                'qrcode' => null,
                'message' => 'Crie a instância antes de consultar o status.',
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'unconfigured',
            'qrcode' => null,
            'qrcode_code' => null,
        ]);
    }

    public function test_sync_marks_connected_instance_as_disconnected_when_remote_is_off(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/status' => Http::response([
                'data' => ['Connected' => false],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertOk()
            ->assertJson([
                'status' => 'disconnected',
                'connected' => false,
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'disconnected',
        ]);
    }

    public function test_sync_requires_logged_in_when_evolution_reports_connected(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/status' => Http::response([
                'data' => ['Connected' => true, 'LoggedIn' => false],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/qr' => Http::response(['message' => 'no qr'], 404),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertOk()
            ->assertJson([
                'status' => 'disconnected',
                'connected' => false,
            ]);
    }

    public function test_sync_marks_instance_as_disconnected_when_status_is_not_authorized(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/status' => Http::response(['error' => 'not authorized'], 401),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'stale-token',
            'status' => 'connected',
            'qrcode' => 'data:image/png;base64,old-qr-code',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertOk()
            ->assertJson([
                'status' => 'disconnected',
                'connected' => false,
                'qrcode' => null,
                'message' => 'not authorized',
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'disconnected',
            'qrcode' => null,
            'last_error' => 'not authorized',
        ]);
    }

    public function test_sync_does_not_treat_string_false_as_connected(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/status' => Http::response([
                'data' => ['Connected' => 'false'],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'qrcode',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertOk()
            ->assertJson([
                'status' => 'qrcode',
                'connected' => false,
                'qrcode' => null,
            ]);

        Http::assertNotSent(fn ($request) => $request->url() === 'https://evolution.test/instance/qr');
    }

    public function test_sync_does_not_request_new_qrcode(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/status' => Http::response([
                'data' => ['Connected' => false],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'qrcode',
            'qrcode' => 'data:image/png;base64,current-qr-code',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.sync'))
            ->assertOk()
            ->assertJson([
                'status' => 'qrcode',
                'connected' => false,
                'qrcode' => null,
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'qrcode',
            'qrcode' => null,
        ]);

        Http::assertNotSent(fn ($request) => $request->url() === 'https://evolution.test/instance/qr');
    }

    public function test_qr_can_be_renewed_on_demand(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/qr' => Http::response([
                'data' => [
                    'Qrcode' => 'data:image/png;base64,renewed-qr-code',
                    'Code' => 'renewed-code',
                ],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'qrcode',
            'qrcode' => 'data:image/png;base64,old-qr-code',
        ]);

        $this->actingAs($user)
            ->postJson(route('chat.qr.renew'))
            ->assertOk()
            ->assertJson([
                'status' => 'qrcode',
                'connected' => false,
                'qrcode' => 'data:image/png;base64,renewed-qr-code',
                'message' => 'QR Code renovado.',
            ]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'qrcode',
            'qrcode' => null,
            'qrcode_code' => null,
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/qr'
            && $request->hasHeader('apikey', 'instance-token')
            && $request->hasHeader('instanceId', 'instance-1'));
    }

    public function test_connected_instance_can_be_disconnected_and_deleted(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/delete/instance-1' => Http::response(['message' => 'success']),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
            'qrcode' => 'data:image/png;base64,old-qr-code',
            'qrcode_code' => 'old-code',
            'connected_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Desconectar')
            ->assertDontSee('data:image/png;base64,old-qr-code');

        $this->actingAs($user)
            ->delete(route('chat.disconnect'))
            ->assertRedirect(route('chat.index'));

        $connection = $tenant->whatsappConnection()->first();

        $this->assertNull($connection->instance_id);
        $this->assertNull($connection->instance_token);
        $this->assertNull($connection->qrcode);
        $this->assertSame('unconfigured', $connection->status);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'https://evolution.test/instance/delete/instance-1'
            && $request->hasHeader('apikey', 'test-key'));
    }

    public function test_disconnect_returns_json_state_for_async_ui(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/delete/instance-1' => Http::response(['message' => 'success']),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
            'qrcode' => 'data:image/png;base64,old-qr-code',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('chat.disconnect'))
            ->assertOk()
            ->assertJson([
                'status' => 'unconfigured',
                'connected' => false,
                'qrcode' => null,
                'message' => 'WhatsApp desconectado.',
            ]);
    }

    public function test_disconnect_clears_local_state_even_when_remote_delete_fails(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/instance/delete/instance-1' => Http::response(['error' => 'not authorized'], 401),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('chat.disconnect'))
            ->assertOk()
            ->assertJson([
                'status' => 'unconfigured',
                'connected' => false,
                'qrcode' => null,
            ]);

        $connection = $tenant->whatsappConnection()->first();

        $this->assertNull($connection->instance_id);
        $this->assertNull($connection->instance_token);
        $this->assertSame('unconfigured', $connection->status);
        $this->assertSame('not authorized', $connection->last_error);
    }

    public function test_tenant_user_can_send_whatsapp_message_through_evolution_go(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/send/text' => Http::response([
                'data' => [
                    'Info' => [
                        'ID' => 'MSG-1',
                        'Timestamp' => now()->toIso8601String(),
                    ],
                ],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bruno Cardoso',
            'phone' => '+55 11 95555-3003',
        ]);

        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $this->actingAs($user)
            ->post(route('chat.messages.store'), [
                'customer_id' => $customer->id,
                'body' => 'Bruno, quer reservar um horario para avaliarmos o Q3?',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'contact_phone' => '5511955553003',
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $tenant->id,
            'external_id' => 'MSG-1',
            'direction' => 'outbound',
            'status' => 'sent',
        ]);

        Http::assertSent(fn ($request) => $request->hasHeader('apikey', 'instance-token')
            && $request->hasHeader('instanceId', 'instance-1')
            && $request['number'] === '5511955553003'
            && $request['text'] === 'Bruno, quer reservar um horario para avaliarmos o Q3?');
    }

    public function test_evolution_webhook_persists_inbound_message_for_tenant(): void
    {
        [$tenant] = $this->createTenantUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rafael Nogueira',
            'phone' => '+55 11 97777-1001',
        ]);

        $connection = WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $this->postJson(route('evolution.webhook', $connection->webhook_secret), [
            'event' => 'Message',
            'instanceId' => 'instance-1',
            'data' => [
                'Info' => [
                    'Chat' => '5511977771001@s.whatsapp.net',
                    'Sender' => '5511977771001:38@s.whatsapp.net',
                    'IsFromMe' => false,
                    'ID' => 'IN-1',
                    'Type' => 'text',
                    'PushName' => 'Rafael',
                    'Timestamp' => now()->toIso8601String(),
                ],
                'Message' => [
                    'conversation' => 'Quero agendar a manutencao da vitrificacao.',
                ],
            ],
        ])->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'contact_phone' => '5511977771001',
            'unread_count' => 1,
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $tenant->id,
            'external_id' => 'IN-1',
            'direction' => 'inbound',
            'body' => 'Quero agendar a manutencao da vitrificacao.',
        ]);
    }

    public function test_evolution_qrcode_webhook_does_not_save_qrcode(): void
    {
        [$tenant] = $this->createTenantUser();

        $connection = WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connecting',
        ]);

        $this->postJson(route('evolution.webhook', $connection->webhook_secret), [
            'event' => 'QRCode',
            'instanceId' => 'instance-1',
            'data' => [
                'qrcode' => 'data:image/png;base64,webhook-qr-code',
                'code' => 'webhook-code',
            ],
        ])->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('whatsapp_connections', [
            'tenant_id' => $tenant->id,
            'status' => 'qrcode',
            'qrcode' => null,
            'qrcode_code' => null,
        ]);
    }

    public function test_connect_uses_internal_webhook_base_url_when_configured(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
            'services.evolution_go.webhook_base_url' => 'http://nginx',
        ]);

        [$tenant, $user] = $this->createTenantUser();

        Http::fake([
            'https://evolution.test/instance/all' => Http::response(['data' => [], 'message' => 'success']),
            'https://evolution.test/instance/create' => Http::response([
                'data' => [
                    'id' => 'instance-1',
                    'name' => 'garageon-'.$tenant->id,
                    'token' => 'instance-token',
                    'connected' => false,
                ],
                'message' => 'success',
            ]),
            'https://evolution.test/instance/connect' => Http::response(['data' => [], 'message' => 'success']),
            'https://evolution.test/instance/qr' => Http::response([
                'data' => ['qrcode' => 'data:image/png;base64,qr-code', 'code' => 'qr-text'],
                'message' => 'success',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('chat.connect'))
            ->assertRedirect(route('chat.index'));

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/instance/connect'
            && str_starts_with((string) $request['webhookUrl'], 'http://nginx/')
            && str_contains((string) $request['webhookUrl'], '/api/evolution/webhook/'));
    }

    public function test_message_send_normalizes_local_phone_to_brazil_e164(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/send/text' => Http::response([
                'data' => ['Info' => ['ID' => 'MSG-LOCAL']],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user] = $this->createTenantUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bruno Local',
            'phone' => '(11) 95555-3003',
        ]);

        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $this->actingAs($user)
            ->post(route('chat.messages.store'), [
                'customer_id' => $customer->id,
                'body' => 'Ola Bruno!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'contact_phone' => '5511955553003',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/send/text'
            && $request['number'] === '5511955553003');
    }

    public function test_chat_stream_returns_rendered_list_messages_and_stats(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Marina Azevedo',
            'phone' => '+55 11 96666-2002',
        ]);

        $conversation = $tenant->whatsappConversations()->create([
            'customer_id' => $customer->id,
            'contact_phone' => '5511966662002',
            'contact_name' => 'Marina Azevedo',
            'status' => 'open',
            'last_message' => 'Bom dia!',
            'last_message_at' => now(),
            'unread_count' => 2,
        ]);

        $conversation->messages()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'direction' => 'inbound',
            'type' => 'text',
            'body' => 'Bom dia!',
            'status' => 'received',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('chat.stream', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonStructure(['list', 'messages', 'stats' => ['total_conversations', 'unread', 'messages_today'], 'selected']);

        $this->assertStringContainsString('Marina Azevedo', $response->json('list'));
        $this->assertStringContainsString('Bom dia!', $response->json('messages'));
        $this->assertSame($conversation->id, $response->json('selected'));

        // Abrir a conversa via stream zera as nao lidas.
        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $conversation->id,
            'unread_count' => 0,
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
