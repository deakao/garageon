<?php

namespace Tests\Feature;

use App\Ai\Agents\StoreAttendant;
use App\Jobs\RespondWithAttendant;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConversation;
use App\Services\AttendantUsage;
use App\Services\EvolutionGoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Ai;
use Tests\TestCase;

class AttendantAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_message_dispatches_job_when_attendant_operational(): void
    {
        Queue::fake();

        [$tenant, $connection] = $this->tenantWithConnection();
        $this->makeAttendant($tenant, active: true);

        $this->postJson(route('evolution.webhook', $connection->webhook_secret), $this->inboundPayload('Quero agendar'))
            ->assertOk();

        Queue::assertPushed(RespondWithAttendant::class, fn ($job) => $job->incomingText === 'Quero agendar');
    }

    public function test_inbound_message_does_not_dispatch_when_attendant_off(): void
    {
        Queue::fake();

        [$tenant, $connection] = $this->tenantWithConnection();
        $this->makeAttendant($tenant, active: false);

        $this->postJson(route('evolution.webhook', $connection->webhook_secret), $this->inboundPayload('Oi'))
            ->assertOk();

        Queue::assertNotPushed(RespondWithAttendant::class);
    }

    public function test_job_generates_reply_and_sends_via_evolution(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/send/text' => Http::response([
                'data' => ['Info' => ['ID' => 'OUT-1']],
                'message' => 'success',
            ]),
        ]);

        Ai::fakeAgent(StoreAttendant::class, ['Olá! Posso agendar sua vitrificação.']);

        [$tenant, $connection] = $this->tenantWithConnection();
        $this->makeAttendant($tenant, active: true);

        $conversation = WhatsappConversation::create([
            'tenant_id' => $tenant->id,
            'contact_phone' => '5511977771001',
            'contact_name' => 'Rafael',
            'status' => 'open',
        ]);

        (new RespondWithAttendant($conversation, 'Quero agendar vitrificação'))
            ->handle(app(EvolutionGoClient::class), app(AttendantUsage::class));

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/send/text'
            && $request['number'] === '5511977771001'
            && $request['text'] === 'Olá! Posso agendar sua vitrificação.');

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $tenant->id,
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'status' => 'sent',
            'body' => 'Olá! Posso agendar sua vitrificação.',
        ]);
    }

    public function test_job_is_noop_when_attendant_not_operational(): void
    {
        Http::fake();

        [$tenant] = $this->tenantWithConnection();
        $this->makeAttendant($tenant, active: false);

        $conversation = WhatsappConversation::create([
            'tenant_id' => $tenant->id,
            'contact_phone' => '5511977771001',
            'status' => 'open',
        ]);

        (new RespondWithAttendant($conversation, 'Oi'))
            ->handle(app(EvolutionGoClient::class), app(AttendantUsage::class));

        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_job_does_not_respond_when_daily_limit_reached_on_platform_key(): void
    {
        Http::fake();
        config(['ai.providers.openai.key' => 'platform-key']);
        Ai::fakeAgent(StoreAttendant::class, ['resposta']);

        $plan = Plan::create([
            'name' => 'Mini', 'slug' => 'mini', 'monthly_price' => 10,
            'ai_daily_message_limit' => 2,
        ]);
        [$tenant] = $this->tenantWithConnection($plan->id);
        $this->makeAttendant($tenant, active: true, ownKey: null); // usa chave da plataforma

        $conversation = WhatsappConversation::create([
            'tenant_id' => $tenant->id,
            'contact_phone' => '5511977771001',
            'status' => 'open',
        ]);

        $usage = app(AttendantUsage::class);
        $usage->record($tenant->fresh('plan'));
        $usage->record($tenant->fresh('plan'));

        (new RespondWithAttendant($conversation, 'Quero agendar'))
            ->handle(app(EvolutionGoClient::class), $usage);

        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_own_key_tenant_has_no_daily_limit(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);
        Http::fake(['https://evolution.test/send/text' => Http::response(['data' => ['Info' => ['ID' => 'OUT-9']], 'message' => 'success'])]);
        Ai::fakeAgent(StoreAttendant::class, ['resposta com chave própria']);

        $plan = Plan::create([
            'name' => 'Mini', 'slug' => 'mini', 'monthly_price' => 10,
            'ai_daily_message_limit' => 2,
        ]);
        [$tenant] = $this->tenantWithConnection($plan->id);
        $this->makeAttendant($tenant, active: true, ownKey: 'sk-tenant-own');

        $conversation = WhatsappConversation::create([
            'tenant_id' => $tenant->id,
            'contact_phone' => '5511977771001',
            'status' => 'open',
        ]);

        $usage = app(AttendantUsage::class);
        // Já ultrapassou a cota do plano, mas a chave é própria: não deve limitar.
        $usage->record($tenant->fresh('plan'));
        $usage->record($tenant->fresh('plan'));
        $usage->record($tenant->fresh('plan'));

        (new RespondWithAttendant($conversation, 'Quero agendar'))
            ->handle(app(EvolutionGoClient::class), $usage);

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/send/text');
        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $tenant->id,
            'direction' => 'outbound',
            'body' => 'resposta com chave própria',
        ]);
    }

    /**
     * @return array{Tenant, WhatsappConnection}
     */
    private function tenantWithConnection(?int $planId = null): array
    {
        $tenant = Tenant::create(['name' => 'Carbon Studio', 'slug' => 'carbon-studio', 'plan_id' => $planId]);
        $connection = WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        return [$tenant, $connection];
    }

    private function makeAttendant(Tenant $tenant, bool $active, ?string $ownKey = 'sk-test'): VirtualAttendant
    {
        return VirtualAttendant::create([
            'tenant_id' => $tenant->id,
            'name' => 'Duda',
            'tone' => 'friendly',
            'provider' => 'openai',
            'api_key' => $active ? $ownKey : null,
            'is_active' => $active,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function inboundPayload(string $body): array
    {
        return [
            'event' => 'Message',
            'instanceId' => 'instance-1',
            'data' => [
                'Info' => [
                    'Chat' => '5511977771001@s.whatsapp.net',
                    'Sender' => '5511977771001:1@s.whatsapp.net',
                    'IsFromMe' => false,
                    'ID' => 'IN-'.uniqid(),
                    'Type' => 'text',
                    'PushName' => 'Rafael',
                    'Timestamp' => now()->toIso8601String(),
                ],
                'Message' => ['conversation' => $body],
            ],
        ];
    }
}
