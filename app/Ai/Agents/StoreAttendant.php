<?php

namespace App\Ai\Agents;

use App\Ai\Tools\BookAppointment;
use App\Ai\Tools\CheckAvailability;
use App\Ai\Tools\ListServices;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConversation;
use App\Services\AttendantPromptBuilder;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Atendente virtual da loja: instruções do PromptBuilder, histórico da conversa
 * e as tools de agendamento. Provider/model/key são passados no prompt() em runtime.
 */
#[MaxSteps(6)]
class StoreAttendant implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(
        private readonly VirtualAttendant $attendant,
        private readonly WhatsappConversation $conversation,
    ) {}

    public function instructions(): Stringable|string
    {
        return app(AttendantPromptBuilder::class)->build($this->attendant);
    }

    /**
     * Histórico recente da conversa (exclui a última inbound, que vai como prompt).
     *
     * @return iterable<Message>
     */
    public function messages(): iterable
    {
        return $this->conversation->messages()
            ->latest('occurred_at')
            ->latest('id')
            ->limit(21)
            ->get()
            ->reverse()
            ->slice(0, -1)
            ->filter(fn ($message) => filled($message->body))
            ->map(fn ($message) => new Message(
                $message->direction === 'outbound' ? 'assistant' : 'user',
                (string) $message->body,
            ))
            ->values()
            ->all();
    }

    /**
     * @return iterable<Tool>
     */
    public function tools(): iterable
    {
        return [
            new ListServices($this->attendant->tenant),
            new CheckAvailability($this->attendant->tenant),
            new BookAppointment($this->attendant, $this->conversation),
        ];
    }
}
