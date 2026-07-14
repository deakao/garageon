<?php

namespace App\Ai\Tools;

use App\Jobs\RespondWithAttendant;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConversation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ScheduleFollowUp implements Tool
{
    public function __construct(
        private readonly VirtualAttendant $attendant,
        private readonly WhatsappConversation $conversation,
    ) {}

    public function description(): Stringable|string
    {
        return 'Agenda o atendente para retomar esta conversa na data e hora pedidas pelo cliente.';
    }

    public function handle(Request $request): Stringable|string
    {
        if ($this->attendant->tenant_id !== $this->conversation->tenant_id) {
            return 'Não foi possível agendar o follow-up desta conversa.';
        }

        $scheduledAtInput = trim((string) ($request['scheduled_at'] ?? ''));
        $scheduledAt = $scheduledAtInput === '' ? null : rescue(
            fn () => Carbon::parse($scheduledAtInput),
            report: false,
        );
        $reason = trim((string) ($request['reason'] ?? ''));

        if (! $scheduledAt || $scheduledAt->isPast() || $reason === '') {
            return 'Informe uma data e hora futuras e o motivo combinado com o cliente.';
        }

        $instruction = "Faça agora o follow-up combinado com o cliente. Contexto: {$reason}. "
            .'Retome a conversa de forma natural, sem dizer que esta é uma mensagem automática.';

        RespondWithAttendant::dispatch($this->conversation, $instruction)->delay($scheduledAt);

        return json_encode([
            'scheduled' => true,
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'scheduled_at' => $schema->string()
                ->description('Data e hora futuras em ISO 8601, com fuso horário. Exemplo: 2026-07-20T14:00:00-03:00.')
                ->required(),
            'reason' => $schema->string()
                ->description('Resumo do que foi combinado e do assunto que deve ser retomado.')
                ->required(),
        ];
    }
}
