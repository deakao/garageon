<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\DigitalSellerAlert;
use App\Models\LandingPage;
use App\Models\OrderBump;
use App\Models\Plan;
use App\Models\PostSaleAutomation;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $growth = Plan::create([
                'name' => 'Performance',
                'slug' => 'performance',
                'monthly_price' => 497,
                'locations_limit' => 3,
                'features' => [
                    'Auto-agendamento 24/7',
                    'Chatbot WhatsApp',
                    'Vendedor digital',
                    'Clube de assinatura',
                    'Landing pages',
                ],
            ]);

            Plan::create([
                'name' => 'Starter',
                'slug' => 'starter',
                'monthly_price' => 197,
                'locations_limit' => 1,
                'features' => ['Agenda online', 'CRM básico', 'Landing page'],
            ]);

            Plan::create([
                'name' => 'Scale',
                'slug' => 'scale',
                'monthly_price' => 897,
                'locations_limit' => 10,
                'features' => ['Multi-lojas', 'Automação avançada', 'Relatórios executivos'],
            ]);

            $tenant = Tenant::create([
                'plan_id' => $growth->id,
                'name' => 'Carbon Studio Detail',
                'slug' => 'carbon-studio',
                'legal_name' => 'Carbon Studio Detail LTDA',
                'document' => '12.345.678/0001-90',
                'whatsapp_phone' => '+55 11 98888-4400',
                'primary_domain' => 'carbon.garageon.test',
                'brand_colors' => ['primary' => '#050505', 'accent' => '#facc15', 'surface' => '#ffffff'],
                'trial_ends_at' => now()->addDays(14),
            ]);

            $tenant->serviceCategories()->createMany([
                ['name' => 'Lavagem', 'slug' => 'lavagem'],
                ['name' => 'Proteção', 'slug' => 'protecao'],
                ['name' => 'Pintura', 'slug' => 'pintura'],
            ]);

            $owner = User::create([
                'name' => 'Gestor Carbon',
                'email' => 'gestor@garageon.test',
                'password' => Hash::make('password'),
            ]);

            User::create([
                'name' => 'Administrador GarageON',
                'email' => 'admin@garageon.test',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
            ]);

            $tenant->users()->attach($owner->id, ['role' => 'owner']);

            $lavagem = Service::create([
                'tenant_id' => $tenant->id,
                'name' => 'Lavagem Técnica Premium',
                'slug' => 'lavagem-tecnica-premium',
                'description' => 'Pré-lavagem, descontaminação leve e acabamento com proteção rápida.',
                'duration_minutes' => 90,
                'price' => 149,
                'lifecycle_days' => 30,
                'category' => 'Lavagem',
            ]);

            $vitrificacao = Service::create([
                'tenant_id' => $tenant->id,
                'name' => 'Vitrificação 9H',
                'slug' => 'vitrificacao-9h',
                'description' => 'Correção de pintura, preparação e coating cerâmico com plano de manutenção.',
                'duration_minutes' => 480,
                'price' => 1890,
                'lifecycle_days' => 120,
                'category' => 'Proteção',
            ]);

            $polimento = Service::create([
                'tenant_id' => $tenant->id,
                'name' => 'Polimento Comercial',
                'slug' => 'polimento-comercial',
                'description' => 'Realce de brilho para veículos de venda ou eventos.',
                'duration_minutes' => 240,
                'price' => 690,
                'lifecycle_days' => 90,
                'category' => 'Pintura',
            ]);

            $customers = collect([
                ['name' => 'Rafael Nogueira', 'phone' => '+55 11 97777-1001', 'last_visit_at' => now()->subDays(164), 'tags' => ['SUV', 'alto ticket']],
                ['name' => 'Marina Azevedo', 'phone' => '+55 11 96666-2002', 'last_visit_at' => now()->subDays(18), 'tags' => ['assinante']],
                ['name' => 'Bruno Cardoso', 'phone' => '+55 11 95555-3003', 'last_visit_at' => null, 'tags' => ['orçamento aberto']],
            ])->map(fn (array $customer) => Customer::create([
                'tenant_id' => $tenant->id,
                ...$customer,
            ]));

            Appointment::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customers[1]->id,
                'service_id' => $lavagem->id,
                'source' => 'chatbot',
                'status' => 'scheduled',
                'scheduled_at' => Carbon::tomorrow()->setTime(10, 30),
                'ends_at' => Carbon::tomorrow()->setTime(12, 0),
                'notes' => 'Agendado automaticamente pelo WhatsApp.',
            ]);

            Appointment::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customers[0]->id,
                'service_id' => $vitrificacao->id,
                'source' => 'external_link',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(3)->setTime(8, 0),
                'ends_at' => now()->addDays(3)->setTime(16, 0),
                'notes' => 'Cliente veio pelo link da bio.',
            ]);

            $quote = Quote::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customers[2]->id,
                'status' => 'sent',
                'total' => 2580,
                'valid_until' => now()->addDays(5),
                'last_follow_up_at' => now()->subDays(4),
                'notes' => 'Pacote de vitrificação com polimento e higienização.',
            ]);

            $quote->items()->createMany([
                ['service_id' => $vitrificacao->id, 'name' => $vitrificacao->name, 'quantity' => 1, 'unit_price' => 1890],
                ['service_id' => $polimento->id, 'name' => $polimento->name, 'quantity' => 1, 'unit_price' => 690],
            ]);

            OrderBump::create([
                'tenant_id' => $tenant->id,
                'trigger_service_id' => $lavagem->id,
                'name' => 'Cristalização de Para-brisas',
                'description' => 'Oferta rápida para melhorar visibilidade em chuva.',
                'price' => 69,
                'conversion_hint' => 'Inclua por menos de 50% do valor avulso.',
            ]);

            PostSaleAutomation::create([
                'tenant_id' => $tenant->id,
                'service_id' => $vitrificacao->id,
                'title' => 'Manutenção obrigatória da vitrificação',
                'message_template' => 'Olá {{cliente}}, sua vitrificação está chegando no prazo de manutenção. Quer reservar a lavagem técnica da semana?',
                'trigger_after_days' => 120,
            ]);

            Subscription::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customers[1]->id,
                'name' => 'Clube Black Wash',
                'recurrence_rule' => 'monthly',
                'amount' => 349,
                'billing_gateway' => 'card_recurring',
                'next_charge_at' => now()->addDays(9),
            ]);

            DB::table('loyalty_programs')->insert([
                'tenant_id' => $tenant->id,
                'name' => 'Detail Points',
                'points_per_currency' => 1,
                'reward_description' => 'A cada 1.000 pontos, R$ 100 de crédito em serviços premium.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DigitalSellerAlert::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customers[2]->id,
                'quote_id' => $quote->id,
                'type' => 'stale_quote',
                'priority' => 'high',
                'title' => 'Orçamento alto sem resposta há 4 dias',
                'suggested_message' => 'Bruno, consigo segurar a condição do pacote até amanhã. Quer que eu reserve um horário para deixar seu carro pronto no fim de semana?',
                'detected_at' => now(),
            ]);

            DigitalSellerAlert::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customers[0]->id,
                'type' => 'inactive_customer',
                'priority' => 'medium',
                'title' => 'Cliente alto ticket sem visita há 164 dias',
                'suggested_message' => 'Rafael, já faz um tempo desde a última proteção do seu SUV. Posso te mandar uma avaliação rápida de manutenção?',
                'detected_at' => now(),
            ]);

            LandingPage::create([
                'tenant_id' => $tenant->id,
                'headline' => 'Seu carro tratado como máquina de pista',
                'subheadline' => 'Lavagem técnica, vitrificação e proteção premium com agendamento online 24/7.',
                'cta_label' => 'Agendar pelo WhatsApp',
                'sections' => [
                    ['title' => 'Acabamento de showroom', 'body' => 'Processos padronizados e produtos profissionais.'],
                    ['title' => 'Manutenção programada', 'body' => 'O GarageON chama o cliente de volta na hora certa.'],
                ],
                'published_at' => now(),
            ]);
        });
    }
}
