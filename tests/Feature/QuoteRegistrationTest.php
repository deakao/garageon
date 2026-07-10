<?php

namespace Tests\Feature;

use App\Mail\QuoteSharedMail;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_quote_with_multiple_services_and_view_page(): void
    {
        [$tenant, $user, $serviceA, $serviceB] = $this->createTenantWithServices();

        $response = $this->actingAs($user)
            ->post(route('quotes.store'), [
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
                    ['service_id' => $serviceA->id, 'quantity' => 1],
                    ['service_id' => $serviceB->id, 'quantity' => 2],
                ],
                'notes' => 'Pacote completo com vitrificação.',
            ]);

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $response
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotes', [
            'tenant_id' => $tenant->id,
            'status' => 'sent',
            'total' => 149 + (89 * 2),
            'channel' => 'cockpit',
        ]);

        $this->assertDatabaseCount('quote_items', 2);

        $this->assertNotNull($quote->public_token);

        $this->actingAs($user)
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertSee('Compartilhar orçamento')
            ->assertSee('Marina Souza')
            ->assertSee('ABC1D23')
            ->assertSee('Lavagem Premium')
            ->assertSee('Polimento Técnico')
            ->assertSee($quote->publicUrl());
    }

    public function test_quote_can_be_viewed_publicly_via_share_token(): void
    {
        [$tenant, $user, $serviceA] = $this->createTenantWithServices();

        $this->actingAs($user)->post(route('quotes.store'), [
            '_form' => 'quote',
            'customer_name' => 'Rafael Lima',
            'customer_phone' => '(11) 97777-0000',
            'vehicle_plate' => 'XYZ9K88',
            'vehicle_brand' => 'Honda',
            'vehicle_model' => 'Civic',
            'quoted_date' => now()->toDateString(),
            'quoted_time' => '09:00',
            'services' => [
                ['service_id' => $serviceA->id, 'quantity' => 1],
            ],
        ]);

        $quote = Quote::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->get(route('quotes.public', $quote->public_token))
            ->assertOk()
            ->assertSee('Rafael Lima')
            ->assertSee('XYZ9K88')
            ->assertSee('Lavagem Premium');

        $this->get(route('quotes.public', 'token-inexistente'))
            ->assertNotFound();
    }

    public function test_connected_evolution_sends_quote_whatsapp_from_show_page(): void
    {
        config([
            'services.evolution_go.url' => 'https://evolution.test',
            'services.evolution_go.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://evolution.test/send/text' => Http::response([
                'data' => ['Info' => ['ID' => 'QUOTE-MSG-1']],
                'message' => 'success',
            ]),
        ]);

        [$tenant, $user, $serviceA] = $this->createTenantWithServices();

        $this->actingAs($user)->post(route('quotes.store'), [
            '_form' => 'quote',
            'customer_name' => 'Marina Souza',
            'customer_phone' => '(11) 98888-1234',
            'vehicle_plate' => 'ABC1D23',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'quoted_date' => now()->toDateString(),
            'quoted_time' => '10:30',
            'services' => [
                ['service_id' => $serviceA->id, 'quantity' => 1],
            ],
        ]);

        $quote = Quote::query()->where('tenant_id', $tenant->id)->with('customer')->firstOrFail();

        WhatsappConnection::create([
            'tenant_id' => $tenant->id,
            'instance_name' => 'garageon-carbon',
            'instance_id' => 'instance-1',
            'instance_token' => 'instance-token',
            'status' => 'connected',
        ]);

        $message = "Olá {$quote->customer->name}! Segue o orçamento da {$tenant->name}: {$quote->publicUrl()}";

        $this->actingAs($user)
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertSee('Enviar WhatsApp')
            ->assertSee('name="return_to" value="back"', false)
            ->assertDontSee('https://wa.me/?text', false);

        $this->actingAs($user)
            ->from(route('quotes.show', $quote))
            ->post(route('chat.messages.store'), [
                'customer_id' => $quote->customer_id,
                'body' => $message,
                'return_to' => 'back',
            ])
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('status', 'Mensagem enviada pelo WhatsApp.');

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $tenant->id,
            'external_id' => 'QUOTE-MSG-1',
            'body' => $message,
            'status' => 'sent',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://evolution.test/send/text'
            && $request->hasHeader('apikey', 'instance-token')
            && $request->hasHeader('instanceId', 'instance-1')
            && $request['number'] === '5511988881234'
            && $request['text'] === $message);
    }

    public function test_tenant_user_can_send_quote_by_email(): void
    {
        Mail::fake();

        [$tenant, $user, $serviceA] = $this->createTenantWithServices();

        $this->actingAs($user)->post(route('quotes.store'), [
            '_form' => 'quote',
            'customer_name' => 'Marina Souza',
            'customer_phone' => '(11) 98888-1234',
            'vehicle_plate' => 'ABC1D23',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'quoted_date' => now()->toDateString(),
            'quoted_time' => '10:30',
            'services' => [
                ['service_id' => $serviceA->id, 'quantity' => 1],
            ],
        ]);

        $quote = Quote::query()->where('tenant_id', $tenant->id)->with('customer')->firstOrFail();
        $quote->customer->update(['email' => 'marina@example.com']);

        $this->actingAs($user)
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertSee('Enviar e-mail')
            ->assertSee(route('quotes.email', $quote), false);

        $this->actingAs($user)
            ->from(route('quotes.show', $quote))
            ->post(route('quotes.email', $quote))
            ->assertRedirect(route('quotes.show', $quote))
            ->assertSessionHas('status', 'Orçamento enviado por e-mail.');

        Mail::assertSent(QuoteSharedMail::class, function (QuoteSharedMail $mail) use ($quote, $tenant) {
            $mail->assertSeeInHtml($quote->publicUrl());

            return $mail->hasTo('marina@example.com')
                && $mail->hasSubject('Orçamento #'.str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT).' - '.$tenant->name);
        });
    }

    public function test_tenant_cannot_view_quote_from_another_tenant(): void
    {
        [$tenantA, $userA] = $this->createTenantWithUser('carbon-studio-a');
        [$tenantB, , $service] = $this->createTenantWithServices('carbon-studio-b');

        $quote = Quote::create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $tenantB->customers()->create([
                'name' => 'Cliente B',
                'phone' => '11999990000',
            ])->id,
            'status' => 'sent',
            'total' => $service->price,
            'channel' => 'cockpit',
        ]);

        $this->actingAs($userA)
            ->get(route('quotes.show', $quote))
            ->assertNotFound();
    }

    /**
     * @return array{Tenant, User, Service, Service}
     */
    private function createTenantWithServices(string $slug = 'carbon-studio'): array
    {
        [$tenant, $user] = $this->createTenantWithUser($slug);

        $serviceA = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'slug' => 'lavagem-premium',
            'description' => 'Lavagem técnica completa.',
            'duration_minutes' => 90,
            'price' => 149,
            'category' => 'Lavagem',
            'is_active' => true,
        ]);

        $serviceB = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Polimento Técnico',
            'slug' => 'polimento-tecnico',
            'description' => 'Correção leve de pintura.',
            'duration_minutes' => 180,
            'price' => 89,
            'category' => 'Estética',
            'is_active' => true,
        ]);

        return [$tenant, $user, $serviceA, $serviceB];
    }

    /**
     * @return array{Tenant, User}
     */
    private function createTenantWithUser(string $slug = 'carbon-studio'): array
    {
        $tenant = Tenant::create([
            'name' => 'Carbon Studio Detail',
            'slug' => $slug,
        ]);

        $user = User::factory()->create([
            'is_platform_admin' => false,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return [$tenant, $user];
    }
}
