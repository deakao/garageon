<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_has_link_to_edit_store_landing_page(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Configurações da loja', false)
            ->assertSee('Landing page')
            ->assertSee(route('settings.landing'), false);
    }

    public function test_tenant_user_can_update_store_landing_page(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->put(route('settings.landing.update'), [
                'eyebrow' => 'Estética automotiva premium',
                'headline' => 'Proteção premium para carros exigentes',
                'subheadline' => 'Agendamento online para lavagem técnica, vitrificação e manutenção.',
                'hero_badge_title' => 'CARBON',
                'hero_badge_body' => 'Proteção, brilho e retorno programado.',
                'cta_label' => 'Reservar agora',
                'seo_title' => 'Estética automotiva premium em São Paulo',
                'seo_description' => 'Lavagem técnica, vitrificação e proteção premium com agendamento online.',
                'seo_keywords' => 'estética automotiva, vitrificação, lavagem técnica',
                'analytics_head' => '<script>window.analyticsLoaded = true;</script>',
                'conversion_pixel' => '<noscript>pixel ativo</noscript>',
                'custom_javascript' => '<script>window.landingCustom = true;</script>',
                'testimonials' => [
                    [
                        'name' => 'Marcos T.',
                        'role' => 'Cliente desde 2023',
                        'quote' => 'O carro saiu com brilho de showroom.',
                        'rating' => 5,
                    ],
                    [
                        'name' => 'Renata C.',
                        'role' => 'SUV premium',
                        'quote' => 'Agendei pela landing e o resultado ficou impecável.',
                        'rating' => 4,
                    ],
                    [
                        'name' => '',
                        'role' => 'Ignorado',
                        'quote' => 'Sem nome não deve aparecer.',
                        'rating' => 5,
                    ],
                ],
                'published' => '1',
            ])->assertRedirect();

        $this->assertDatabaseHas('landing_pages', [
            'tenant_id' => $tenant->id,
            'headline' => 'Proteção premium para carros exigentes',
            'seo_title' => 'Estética automotiva premium em São Paulo',
            'cta_label' => 'Reservar agora',
        ]);

        $landing = LandingPage::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $savedTestimonials = $landing->testimonials;

        $this->assertCount(2, $savedTestimonials);
        $this->assertSame('Marcos T.', $savedTestimonials[0]['name']);
        $this->assertSame(4, $savedTestimonials[1]['rating']);

        $tenant->serviceCategories()->createMany([
            ['name' => 'Pacotes', 'slug' => 'pacotes'],
            ['name' => 'Serviços automotivos', 'slug' => 'servicos-automotivos'],
            ['name' => 'Serviços Residenciais', 'slug' => 'servicos-residenciais'],
        ]);

        $tenant->services()->createMany([
            [
                'name' => 'Pacote Ouro',
                'slug' => 'pacote-ouro',
                'description' => "Higienização interna\nCorreção de pintura\nProteção de 3 anos",
                'duration_minutes' => 480,
                'price' => 1990,
                'lifecycle_days' => 120,
                'category' => 'Pacotes',
                'is_active' => true,
            ],
            [
                'name' => 'Lavagem Técnica Premium',
                'slug' => 'lavagem-tecnica-premium',
                'description' => 'Pré-lavagem e acabamento premium.',
                'duration_minutes' => 90,
                'price' => 149,
                'lifecycle_days' => 30,
                'category' => 'Serviços automotivos',
                'is_active' => true,
            ],
            [
                'name' => 'Limpeza e Higienização de Sofá',
                'slug' => 'limpeza-higienizacao-sofa',
                'description' => 'Higienização residencial com cuidado técnico.',
                'duration_minutes' => 180,
                'price' => 349,
                'lifecycle_days' => 90,
                'category' => 'Serviços Residenciais',
                'is_active' => true,
            ],
            [
                'name' => 'Serviço Inativo',
                'slug' => 'servico-inativo',
                'description' => 'Não deve aparecer na landing.',
                'duration_minutes' => 60,
                'price' => 99,
                'category' => 'Serviços automotivos',
                'is_active' => false,
            ],
        ]);

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertSee('<title>Estética automotiva premium em São Paulo</title>', false)
            ->assertSee('name="description" content="Lavagem técnica, vitrificação e proteção premium com agendamento online."', false)
            ->assertSee('window.analyticsLoaded = true', false)
            ->assertSee('pixel ativo', false)
            ->assertSee('window.landingCustom = true', false)
            ->assertSee('Proteção premium para carros exigentes')
            ->assertSee('CARBON')
            ->assertSee('Reservar agora')
            ->assertSee('PACOTES')
            ->assertSee('Pacote Ouro')
            ->assertSee('Serviços automotivos')
            ->assertSee('Lavagem Técnica Premium')
            ->assertSee('Serviços Residenciais')
            ->assertSee('Limpeza e Higienização de Sofá')
            ->assertDontSee('Serviço Inativo')
            ->assertSee('Depoimentos')
            ->assertSee('O que nossos clientes dizem')
            ->assertSee('Marcos T.')
            ->assertSee('O carro saiu com brilho de showroom.')
            ->assertSee('Renata C.')
            ->assertDontSee('Sem nome não deve aparecer.');
    }

    public function test_tenant_user_can_upload_store_landing_hero_image(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->createTenantUser();

        $this->actingAs($user)
            ->put(route('settings.landing.update'), [
                'headline' => 'Proteção premium para carros exigentes',
                'subheadline' => 'Agendamento online para lavagem técnica, vitrificação e manutenção.',
                'cta_label' => 'Reservar agora',
                'hero_image_file' => UploadedFile::fake()->image('hero.jpg', 1400, 800),
            ])->assertRedirect();

        $heroImage = LandingPage::query()->where('tenant_id', $tenant->id)->value('hero_image');

        $this->assertStringStartsWith('/storage/tenants/'.$tenant->id.'/landing/', $heroImage);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $heroImage));

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertSee($heroImage, false);
    }

    public function test_storefront_booking_modal_can_create_public_appointment(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');

        try {
            [$tenant] = $this->createTenantUser();
            $tenant->serviceCategories()->create([
                'name' => 'Serviços automotivos',
                'slug' => 'servicos-automotivos',
            ]);
            $tenant->operatingHours()->create([
                'day_of_week' => 2,
                'opens_at' => '10:00',
                'closes_at' => '12:00',
                'is_closed' => false,
            ]);
            $service = $tenant->services()->create([
                'name' => 'Lavagem Técnica Premium',
                'slug' => 'lavagem-tecnica-premium',
                'description' => 'Pré-lavagem e acabamento premium.',
                'duration_minutes' => 60,
                'price' => 149,
                'lifecycle_days' => 30,
                'category' => 'Serviços automotivos',
                'is_active' => true,
            ]);

            $this->get(route('storefront', $tenant))
                ->assertOk()
                ->assertSee('data-booking-modal', false)
                ->assertSee('data-booking-open="'.$service->id.'"', false)
                ->assertSee('10h00')
                ->assertSee('11h00');

            $this->post(route('storefront.booking.store', $tenant), [
                'service_id' => $service->id,
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '10:00',
                'customer_name' => 'Rafael Nogueira',
                'customer_phone' => '+55 11 97777-1001',
                'customer_email' => 'rafael@example.com',
                'vehicle_plate' => 'abc-1d23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
            ])->assertRedirect(route('storefront', $tenant));

            $appointment = Appointment::firstOrFail();

            $this->assertSame('landing-page', $appointment->source);
            $this->assertSame($service->id, $appointment->service_id);
            $this->assertSame('rafael@example.com', $appointment->customer->email);
            $this->assertSame('2026-07-07 10:00:00', $appointment->scheduled_at->format('Y-m-d H:i:s'));
            $this->assertSame('2026-07-07 11:00:00', $appointment->ends_at->format('Y-m-d H:i:s'));

            $this->post(route('storefront.booking.store', $tenant), [
                'service_id' => $service->id,
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '10:00',
                'customer_name' => 'Marina Costa',
                'customer_phone' => '+55 11 98888-1000',
                'customer_email' => 'marina@example.com',
            ])->assertSessionHasErrors('scheduled_time', null, 'booking');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_storefront_booking_identifies_customer_by_plate_and_email(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');

        try {
            [$tenant] = $this->createTenantUser();
            $tenant->operatingHours()->create([
                'day_of_week' => 2,
                'opens_at' => '10:00',
                'closes_at' => '13:00',
                'is_closed' => false,
            ]);
            $service = $tenant->services()->create([
                'name' => 'Lavagem Técnica Premium',
                'slug' => 'lavagem-tecnica-premium',
                'description' => 'Pré-lavagem e acabamento premium.',
                'duration_minutes' => 60,
                'price' => 149,
                'category' => 'Serviços automotivos',
                'is_active' => true,
            ]);
            $identifiedCustomer = $tenant->customers()->create([
                'name' => 'Rafael Nogueira',
                'phone' => '+55 11 97777-1001',
                'email' => 'rafael@example.com',
            ]);
            $tenant->vehicles()->create([
                'customer_id' => $identifiedCustomer->id,
                'plate' => 'ABC1D23',
                'brand' => 'Toyota',
                'model' => 'Corolla Cross',
            ]);

            $this->post(route('storefront.booking.store', $tenant), [
                'service_id' => $service->id,
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '10:00',
                'customer_name' => 'Rafael Atualizado',
                'customer_phone' => '+55 11 90000-0000',
                'customer_email' => 'rafael@example.com',
                'vehicle_plate' => 'abc-1d23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
            ])->assertRedirect(route('storefront', $tenant));

            $firstAppointment = Appointment::query()->where('scheduled_at', '2026-07-07 10:00:00')->firstOrFail();

            $this->assertSame($identifiedCustomer->id, $firstAppointment->customer_id);
            $this->assertDatabaseCount('customers', 1);

            $this->post(route('storefront.booking.store', $tenant), [
                'service_id' => $service->id,
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '11:00',
                'customer_name' => 'Marina Costa',
                'customer_phone' => '+55 11 98888-1000',
                'customer_email' => 'marina@example.com',
                'vehicle_plate' => 'abc-1d23',
                'vehicle_brand' => 'Toyota',
                'vehicle_model' => 'Corolla Cross',
            ])->assertRedirect(route('storefront', $tenant));

            $secondAppointment = Appointment::query()->where('scheduled_at', '2026-07-07 11:00:00')->firstOrFail();

            $this->assertNotSame($identifiedCustomer->id, $secondAppointment->customer_id);
            $this->assertDatabaseHas('customers', [
                'tenant_id' => $tenant->id,
                'name' => 'Marina Costa',
                'email' => 'marina@example.com',
            ]);
            $this->assertDatabaseCount('customers', 2);
            $this->assertDatabaseCount('vehicles', 2);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_custom_domain_opens_tenant_landing_page_and_accepts_booking(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');

        try {
            [$tenant] = $this->createTenantUser();
            $tenant->update(['primary_domain' => 'www.carbonstudio.test']);
            LandingPage::create([
                'tenant_id' => $tenant->id,
                'headline' => 'Landing exclusiva Carbon',
                'subheadline' => 'Agendamento direto pelo domínio da loja.',
                'cta_label' => 'Agendar agora',
                'published_at' => now(),
            ]);
            $tenant->operatingHours()->create([
                'day_of_week' => 2,
                'opens_at' => '10:00',
                'closes_at' => '12:00',
                'is_closed' => false,
            ]);
            $service = $tenant->services()->create([
                'name' => 'Lavagem Técnica Premium',
                'slug' => 'lavagem-tecnica-premium',
                'description' => 'Pré-lavagem e acabamento premium.',
                'duration_minutes' => 60,
                'price' => 149,
                'category' => 'Serviços automotivos',
                'is_active' => true,
            ]);

            $this->get('http://www.carbonstudio.test/')
                ->assertOk()
                ->assertSee('Landing exclusiva Carbon')
                ->assertSee(route('storefront.custom.booking.store'), false);

            $this->post('http://www.carbonstudio.test/agendar', [
                'service_id' => $service->id,
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '10:00',
                'customer_name' => 'Rafael Nogueira',
                'customer_phone' => '+55 11 97777-1001',
                'customer_email' => 'rafael@example.com',
            ])->assertRedirect('http://www.carbonstudio.test');

            $this->assertDatabaseHas('appointments', [
                'tenant_id' => $tenant->id,
                'customer_id' => $tenant->customers()->firstOrFail()->id,
                'service_id' => $service->id,
                'source' => 'landing-page',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_storefront_whatsapp_fab_captures_lead_before_redirect(): void
    {
        [$tenant] = $this->createTenantUser();
        $tenant->update(['whatsapp_phone' => '(11) 98888-4400']);

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertSee('data-whatsapp-lead', false)
            ->assertSee('data-whatsapp-fab', false)
            ->assertSee(route('storefront.whatsapp-lead.store', $tenant), false);

        $response = $this->postJson(route('storefront.whatsapp-lead.store', $tenant), [
            'name' => 'Marina Costa',
            'email' => 'marina@example.com',
            'phone' => '(11) 97777-2002',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('whatsapp_url', 'https://wa.me/5511988884400?text='.rawurlencode('Olá! Vim pela landing page da Carbon Studio Detail. Meu nome é Marina Costa.'));

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'name' => 'Marina Costa',
            'email' => 'marina@example.com',
            'phone' => '(11) 97777-2002',
        ]);

        $customer = $tenant->customers()->where('email', 'marina@example.com')->firstOrFail();

        $this->assertContains('lead', $customer->tags);
        $this->assertContains('landing-whatsapp', $customer->tags);
    }

    public function test_storefront_whatsapp_fab_is_hidden_without_store_phone(): void
    {
        [$tenant] = $this->createTenantUser();
        $tenant->update(['whatsapp_phone' => null]);

        $this->get(route('storefront', $tenant))
            ->assertOk()
            ->assertDontSee('data-whatsapp-fab', false);
    }

    public function test_custom_domain_whatsapp_lead_creates_customer(): void
    {
        [$tenant] = $this->createTenantUser();
        $tenant->update([
            'primary_domain' => 'www.carbonstudio.test',
            'whatsapp_phone' => '+55 11 98888-4400',
        ]);

        $this->postJson('http://www.carbonstudio.test/whatsapp-lead', [
            'name' => 'Igor Mendes',
            'email' => 'igor@example.com',
            'phone' => '11966667777',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonFragment(['whatsapp_url' => 'https://wa.me/5511988884400?text='.rawurlencode('Olá! Vim pela landing page da Carbon Studio Detail. Meu nome é Igor Mendes.')]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'name' => 'Igor Mendes',
            'email' => 'igor@example.com',
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
