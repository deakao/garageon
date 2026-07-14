<?php

namespace Tests\Feature;

use App\Ai\Tools\ScheduleFollowUp;
use App\Jobs\RespondWithAttendant;
use App\Models\Tenant;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ScheduleFollowUpToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedules_attendant_to_resume_the_conversation_at_requested_time(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');
        Queue::fake();

        [$attendant, $conversation] = $this->scenario();
        $scheduledAt = Carbon::parse('2026-07-20T12:00:00+00:00');

        $result = json_decode((string) (new ScheduleFollowUp($attendant, $conversation))->handle(new Request([
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'reason' => 'O cliente vai pensar na vitrificação e pediu retorno em uma semana.',
        ])), true);

        $this->assertTrue($result['scheduled']);
        Queue::assertPushed(RespondWithAttendant::class, function (RespondWithAttendant $job) use ($conversation, $scheduledAt) {
            return $job->conversation->is($conversation)
                && str_contains($job->incomingText, 'vitrificação')
                && $job->delay?->equalTo($scheduledAt);
        });
    }

    public function test_rejects_past_follow_up(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');
        Queue::fake();

        [$attendant, $conversation] = $this->scenario();

        $result = (string) (new ScheduleFollowUp($attendant, $conversation))->handle(new Request([
            'scheduled_at' => '2026-07-12T12:00:00+00:00',
            'reason' => 'Retomar orçamento.',
        ]));

        $this->assertStringContainsString('data e hora futuras', $result);
        Queue::assertNothingPushed();
    }

    public function test_rejects_conversation_from_another_tenant(): void
    {
        Queue::fake();

        [$attendant] = $this->scenario();
        $otherTenant = Tenant::create(['name' => 'Outra loja', 'slug' => 'outra-loja']);
        $conversation = WhatsappConversation::create([
            'tenant_id' => $otherTenant->id,
            'contact_phone' => '5511988881001',
            'status' => 'open',
        ]);

        $result = (string) (new ScheduleFollowUp($attendant, $conversation))->handle(new Request([
            'scheduled_at' => now()->addWeek()->toIso8601String(),
            'reason' => 'Retomar orçamento.',
        ]));

        $this->assertStringContainsString('Não foi possível', $result);
        Queue::assertNothingPushed();
    }

    /**
     * @return array{VirtualAttendant, WhatsappConversation}
     */
    private function scenario(): array
    {
        $tenant = Tenant::create(['name' => 'Carbon', 'slug' => 'carbon']);
        $attendant = VirtualAttendant::create([
            'tenant_id' => $tenant->id,
            'name' => 'Duda',
            'tone' => 'friendly',
            'provider' => 'openai',
            'is_active' => true,
        ]);
        $conversation = WhatsappConversation::create([
            'tenant_id' => $tenant->id,
            'contact_phone' => '5511977771001',
            'status' => 'open',
        ]);

        return [$attendant, $conversation];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
