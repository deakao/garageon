<?php

namespace Tests\Feature;

use App\Jobs\RunQuoteFunnelAutomation;
use App\Mail\QuoteFunnelAutomationMail;
use App\Models\Quote;
use App\Models\QuoteFunnelAutomation;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConnection;
use App\Services\QuoteFunnelAutomationRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuoteFunnelAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_crud_funnel_automations(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->get(route('settings.quote-funnel'))
            ->assertOk()
            ->assertSee('Automações do funil')
            ->assertSee('Nova automação');

        $this->actingAs($user)
            ->post(route('settings.quote-funnel.store'), [
                'name' => 'Follow-up WhatsApp em 1 dia',
                'stage' => 'pending',
                'channel' => 'whatsapp',
                'delay_value' => 1,
                'delay_unit' => 'days',
                'message_template' => 'Lembrete {{cliente}} {{link}}',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.quote-funnel'))
            ->assertSessionHas('status');

        $automation = QuoteFunnelAutomation::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame('Follow-up WhatsApp em 1 dia', $automation->name);
        $this->assertSame('pending', $automation->stage);
        $this->assertSame('whatsapp', $automation->channel);
        $this->assertSame(1, $automation->delay_value);
        $this->assertSame('days', $automation->delay_unit);
        $this->assertSame(1440, $automation->delayInMinutes());
        $this->assertTrue($automation->is_active);

        $this->actingAs($user)
            ->put(route('settings.quote-funnel.update', $automation), [
                'name' => 'Follow-up e-mail em 2 horas',
                'stage' => 'pending',
                'channel' => 'email',
                'delay_value' => 2,
                'delay_unit' => 'hours',
                'subject' => 'Lembrete {{orcamento}}',
                'message_template' => 'Olá {{cliente}}, ainda aguardamos retorno.',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.quote-funnel'));

        $automation->refresh();

        $this->assertSame('email', $automation->channel);
        $this->assertSame(2, $automation->delay_value);
        $this->assertSame('hours', $automation->delay_unit);
        $this->assertSame(120, $automation->delayInMinutes());
        $this->assertSame('Lembrete {{orcamento}}', $automation->subject);

        $this->actingAs($user)
            ->delete(route('settings.quote-funnel.destroy', $automation))
            ->assertRedirect(route('settings.quote-funnel'));

        $this->assertDatabaseMissing('quote_funnel_automations', ['id' => $automation->id]);
    }

    public function test_tenant_can_create_multiple_automations_for_same_stage(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        foreach (['Imediato', 'Após 1 dia', 'Após 3 dias'] as $index => $name) {
            $this->actingAs($user)
                ->post(route('settings.quote-funnel.store'), [
                    'name' => $name,
                    'stage' => 'pending',
                    'channel' => 'whatsapp',
                    'delay_value' => $index === 0 ? 0 : $index,
                    'delay_unit' => $index === 0 ? 'minutes' : 'days',
                    'message_template' => "Mensagem {$name} {{cliente}}",
                    'is_active' => '1',
                ])
                ->assertRedirect();
        }

        $this->assertSame(3, QuoteFunnelAutomation::query()->where('tenant_id', $tenant->id)->where('stage', 'pending')->count());
    }

    public function test_status_change_dispatches_active_automations_with_delay(): void
    {
        Queue::fake();

        [$tenant, $user, $service] = $this->createTenantWithService();

        $automation = QuoteFunnelAutomation::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pending WhatsApp 2h',
            'stage' => 'pending',
            'channel' => 'whatsapp',
            'is_active' => true,
            'delay_value' => 2,
            'delay_unit' => 'hours',
            'message_template' => 'Oi {{cliente}}',
        ]);

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        Queue::assertNotPushed(RunQuoteFunnelAutomation::class);

        $this->actingAs($user)
            ->patchJson(route('quotes.status', $quote), ['status' => 'pending'])
            ->assertOk();

        Queue::assertPushed(RunQuoteFunnelAutomation::class, function (RunQuoteFunnelAutomation $job) use ($quote, $automation) {
            return $job->quote->is($quote)
                && $job->automation->is($automation)
                && $job->automation->delayInMinutes() === 120;
        });
    }

    public function test_email_automation_sends_mail_when_customer_has_email(): void
    {
        Mail::fake();

        [$tenant, $user, $service] = $this->createTenantWithService();

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $quote->customer->update(['email' => 'marina@example.com']);

        $automation = QuoteFunnelAutomation::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aceite por e-mail',
            'stage' => 'accepted',
            'channel' => 'email',
            'is_active' => true,
            'delay_value' => 0,
            'delay_unit' => 'minutes',
            'subject' => 'Aceito {{orcamento}}',
            'message_template' => 'Parabéns {{cliente}}, valor {{valor}}.',
        ]);

        $quote->update(['status' => 'accepted']);

        app(QuoteFunnelAutomationRunner::class)->run($quote, $automation);

        Mail::assertSent(QuoteFunnelAutomationMail::class, function (QuoteFunnelAutomationMail $mail) {
            return $mail->hasTo('marina@example.com')
                && str_contains($mail->renderedSubject, 'Aceito')
                && str_contains($mail->renderedBody, 'Marina Souza');
        });
    }

    public function test_whatsapp_automation_sends_via_evolution_when_connected(): void
    {
        Http::fake([
            '*/send/text' => Http::response([
                'data' => ['Info' => ['ID' => 'FUNNEL-MSG-1']],
            ], 200),
        ]);

        [$tenant, $user, $service] = $this->createTenantWithService();

        WhatsappConnection::query()->create([
            'tenant_id' => $tenant->id,
            'instance_id' => 'instance-funnel',
            'instance_token' => 'token-funnel',
            'status' => 'connected',
        ]);

        $this->actingAs($user)->post(route('quotes.store'), $this->quotePayload($service));

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $quote->update(['status' => 'accepted']);

        $automation = QuoteFunnelAutomation::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aceite WhatsApp',
            'stage' => 'accepted',
            'channel' => 'whatsapp',
            'is_active' => true,
            'delay_value' => 30,
            'delay_unit' => 'minutes',
            'message_template' => 'Aceito {{cliente}} {{link}}',
        ]);

        app(QuoteFunnelAutomationRunner::class)->run($quote, $automation);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/send/text'));

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $tenant->id,
            'customer_id' => $quote->customer_id,
            'direction' => 'outbound',
            'external_id' => 'FUNNEL-MSG-1',
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

    /**
     * @return array{Tenant, User, Service}
     */
    private function createTenantWithService(): array
    {
        [$tenant, $user] = $this->createTenantUser();

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'slug' => 'lavagem-premium',
            'description' => 'Lavagem técnica completa.',
            'duration_minutes' => 90,
            'price' => 149,
            'category' => 'Lavagem',
            'is_active' => true,
        ]);

        return [$tenant, $user, $service];
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(Service $service): array
    {
        return [
            '_form' => 'quote',
            'customer_name' => 'Marina Souza',
            'customer_phone' => '(11) 98888-1234',
            'vehicle_plate' => 'ABC1D23',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'vehicle_year' => 2022,
            'vehicle_color' => 'Prata',
            'quoted_date' => now()->toDateString(),
            'quoted_time' => '10:30',
            'services' => [
                ['service_id' => $service->id, 'quantity' => 1],
            ],
            'notes' => 'Proposta inicial.',
        ];
    }
}
