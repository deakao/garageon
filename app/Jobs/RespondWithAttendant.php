<?php

namespace App\Jobs;

use App\Ai\Agents\StoreAttendant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\AttendantUsage;
use App\Services\EvolutionGoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Enums\Lab;
use Throwable;

/**
 * Gera e envia a resposta do atendente virtual para uma conversa do WhatsApp.
 *
 * Roda em fila para não travar o webhook da Evolution nem estourar timeout com
 * a latência do LLM. A API key do tenant é injetada na config do provider apenas
 * durante a execução deste job (processo isolado, sem vazar entre tenants).
 */
class RespondWithAttendant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public readonly WhatsappConversation $conversation,
        public readonly string $incomingText,
    ) {}

    public function handle(EvolutionGoClient $evolution, AttendantUsage $usage): void
    {
        $conversation = $this->conversation->fresh(['tenant.plan', 'tenant.virtualAttendant', 'tenant.whatsappConnection']);
        $attendant = $conversation?->tenant?->virtualAttendant;
        $connection = $conversation?->tenant?->whatsappConnection;

        if (! $attendant?->isOperational() || ! $connection?->instance_id) {
            return;
        }

        // Limite só se aplica quando usamos a chave da plataforma (custo nosso).
        // Com chave própria do tenant, o custo é dele: sem cota.
        $enforceLimit = ! $attendant->usesOwnKey();

        if ($enforceLimit && $usage->hasReachedLimit($conversation->tenant)) {
            return;
        }

        if ($attendant->usesOwnKey()) {
            config(["ai.providers.{$attendant->provider->value}.key" => $attendant->api_key]);
        }

        $reply = trim((string) (new StoreAttendant($attendant, $conversation))->prompt(
            $this->incomingText,
            provider: Lab::from($attendant->provider->value),
            model: $attendant->modelName(),
        )->text);

        if ($reply === '') {
            return;
        }

        if ($enforceLimit) {
            $usage->record($conversation->tenant);
        }

        $result = $evolution->sendText($connection, $conversation->contact_phone, $reply);

        WhatsappMessage::query()->create([
            'tenant_id' => $conversation->tenant_id,
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'external_id' => data_get($result['payload'] ?? [], 'data.Info.ID'),
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $reply,
            'status' => $result['successful'] ? 'sent' : 'failed',
            'payload' => $result['payload'] ?? ['error' => $result['message'] ?? null],
            'occurred_at' => now(),
        ]);

        $conversation->forceFill([
            'last_message' => $reply,
            'last_message_at' => now(),
        ])->save();
    }

    public function failed(Throwable $e): void
    {
        report($e);
    }
}
