<?php

namespace Tests\Feature;

use App\Ai\Tools\BookAppointment;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantOperatingHour;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class BookAppointmentToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_scheduled_appointment_by_default(): void
    {
        [$attendant, $conversation, $service, $date, $time] = $this->scenario(requireConfirmation: false);

        $result = json_decode((string) (new BookAppointment($attendant, $conversation))->handle(new Request([
            'service_id' => $service->id,
            'date' => $date,
            'time' => $time,
            'customer_name' => 'Rafael',
        ])), true);

        $this->assertTrue($result['confirmed']);
        $this->assertFalse($result['pending_store_confirmation']);
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $attendant->tenant_id,
            'service_id' => $service->id,
            'status' => 'scheduled',
            'source' => 'whatsapp-attendant',
        ]);
    }

    public function test_creates_pending_appointment_when_manual_confirmation_on(): void
    {
        [$attendant, $conversation, $service, $date, $time] = $this->scenario(requireConfirmation: true);

        $result = json_decode((string) (new BookAppointment($attendant, $conversation))->handle(new Request([
            'service_id' => $service->id,
            'date' => $date,
            'time' => $time,
            'customer_name' => 'Rafael',
        ])), true);

        $this->assertFalse($result['confirmed']);
        $this->assertTrue($result['pending_store_confirmation']);
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $attendant->tenant_id,
            'service_id' => $service->id,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array{VirtualAttendant, WhatsappConversation, Service, string, string}
     */
    private function scenario(bool $requireConfirmation): array
    {
        Carbon::setTestNow('2026-07-06 08:00:00'); // segunda-feira

        $tenant = Tenant::create(['name' => 'Carbon', 'slug' => 'carbon']);

        // Garante loja aberta no dia do teste.
        for ($day = 0; $day <= 6; $day++) {
            TenantOperatingHour::create([
                'tenant_id' => $tenant->id,
                'day_of_week' => $day,
                'opens_at' => '08:00',
                'closes_at' => '18:00',
                'is_closed' => false,
            ]);
        }

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Vitrificação',
            'slug' => 'vitrificacao',
            'duration_minutes' => 60,
            'price' => 500,
            'is_active' => true,
        ]);

        $attendant = VirtualAttendant::create([
            'tenant_id' => $tenant->id,
            'name' => 'Duda',
            'tone' => 'friendly',
            'provider' => 'openai',
            'require_booking_confirmation' => $requireConfirmation,
            'is_active' => true,
        ]);
        $attendant->setRelation('tenant', $tenant);

        $conversation = WhatsappConversation::create([
            'tenant_id' => $tenant->id,
            'contact_phone' => '5511977771001',
            'contact_name' => 'Rafael',
            'status' => 'open',
        ]);

        // Amanhã às 10:00 (dentro do horário, futuro).
        $slot = Carbon::parse('2026-07-07 10:00:00');

        return [$attendant, $conversation, $service, $slot->toDateString(), $slot->format('H:i')];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
