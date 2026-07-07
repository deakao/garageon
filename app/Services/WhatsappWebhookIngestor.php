<?php

namespace App\Services;

use App\Jobs\RespondWithAttendant;
use App\Models\Customer;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Support\WhatsappPhone;
use Illuminate\Support\Carbon;

class WhatsappWebhookIngestor
{
    public function ingest(WhatsappConnection $connection, array $payload): ?WhatsappMessage
    {
        $rawEvent = (string) data_get($payload, 'event');

        return match ($this->normalizeEvent($rawEvent)) {
            'qrcode' => $this->recordQrCode($connection, $payload),
            'connected' => $this->recordConnected($connection, $payload),
            'disconnected' => $this->recordDisconnected($connection, $payload),
            'message' => $this->recordMessage($connection, $payload, $rawEvent),
            default => null,
        };
    }

    /**
     * Normaliza o nome do evento entregue pela Evolution para um canal interno.
     *
     * A Evolution pode variar entre PascalCase (Connected), UPPER (CONNECTION),
     * snake/dotted (connection.update). Reduzimos tudo a apenas letras minusculas
     * antes de casar, para que uma mudanca de formatacao nao caia no default e
     * deixe o status preso em "connecting".
     */
    private function normalizeEvent(string $event): string
    {
        $key = preg_replace('/[^a-z]/', '', strtolower($event)) ?? '';

        return match (true) {
            str_contains($key, 'qrcode') || str_contains($key, 'qr') => 'qrcode',
            str_contains($key, 'loggedout') || str_contains($key, 'disconnect') => 'disconnected',
            str_contains($key, 'connected') || str_contains($key, 'pairsuccess')
                || str_contains($key, 'offlinesynccompleted') => 'connected',
            str_contains($key, 'message') => 'message',
            default => '',
        };
    }

    private function recordQrCode(WhatsappConnection $connection, array $payload): ?WhatsappMessage
    {
        $connection->forceFill([
            'status' => 'qrcode',
            'qrcode' => null,
            'qrcode_code' => null,
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();

        return null;
    }

    private function recordConnected(WhatsappConnection $connection, array $payload): ?WhatsappMessage
    {
        $connection->forceFill([
            'status' => 'connected',
            'connected_at' => now(),
            'qrcode' => null,
            'qrcode_code' => null,
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();

        return null;
    }

    private function recordDisconnected(WhatsappConnection $connection, array $payload): ?WhatsappMessage
    {
        $connection->forceFill([
            'status' => 'disconnected',
            'last_error' => data_get($payload, 'data.Reason'),
            'last_synced_at' => now(),
        ])->save();

        return null;
    }

    private function recordMessage(WhatsappConnection $connection, array $payload, string $event): ?WhatsappMessage
    {
        $tenant = $connection->tenant;
        $data = data_get($payload, 'data', []);
        $info = data_get($data, 'Info', []);

        if (! is_array($data) || ! is_array($info)) {
            return null;
        }

        $isFromMe = (bool) data_get($info, 'IsFromMe', $event === 'SendMessage');
        $chatJid = (string) (data_get($info, 'Chat') ?: data_get($info, 'Sender'));
        $phone = WhatsappPhone::fromJid($chatJid);

        if ($phone === '') {
            return null;
        }

        $customer = $this->findCustomerByPhone($tenant->id, $phone);
        $occurredAt = $this->parseTimestamp(data_get($info, 'Timestamp'));
        $body = $this->extractBody($data);

        $conversation = WhatsappConversation::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'contact_phone' => $phone,
        ]);

        $conversation->fill([
            'customer_id' => $customer?->id,
            'contact_jid' => $chatJid,
            'contact_name' => data_get($info, 'PushName') ?: $customer?->name ?: $conversation->contact_name,
            'status' => 'open',
            'last_message' => $body,
            'last_message_at' => $occurredAt ?? now(),
        ]);

        if (! $isFromMe) {
            $conversation->unread_count = ((int) $conversation->unread_count) + 1;
        }

        $conversation->save();

        $messageData = [
            'tenant_id' => $tenant->id,
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer?->id,
            'external_id' => data_get($info, 'ID'),
            'direction' => $isFromMe ? 'outbound' : 'inbound',
            'type' => data_get($info, 'MediaType') ? 'media' : 'text',
            'body' => $body,
            'status' => $isFromMe ? 'sent' : 'received',
            'payload' => $payload,
            'occurred_at' => $occurredAt ?? now(),
        ];

        if ($messageData['external_id']) {
            $message = WhatsappMessage::query()->updateOrCreate([
                'tenant_id' => $tenant->id,
                'external_id' => $messageData['external_id'],
            ], $messageData);
        } else {
            $message = WhatsappMessage::query()->create($messageData);
        }

        if (! $isFromMe && $message->type === 'text' && filled($body)) {
            $conversation->setRelation('tenant', $tenant);
            $this->maybeAutoRespond($conversation, $body);
        }

        return $message;
    }

    /**
     * Dispara o atendente virtual quando ligado, para responder a mensagem recebida.
     */
    private function maybeAutoRespond(WhatsappConversation $conversation, string $body): void
    {
        $attendant = $conversation->tenant?->virtualAttendant;

        if ($attendant?->isOperational()) {
            RespondWithAttendant::dispatch($conversation, $body);
        }
    }

    private function findCustomerByPhone(int $tenantId, string $phone): ?Customer
    {
        return Customer::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->first(fn (Customer $customer) => WhatsappPhone::normalize($customer->phone) === $phone);
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return rescue(fn () => Carbon::parse($value), null, report: false);
    }

    private function extractBody(array $data): string
    {
        $message = data_get($data, 'Message', []);

        if (! is_array($message)) {
            return '[Mensagem recebida]';
        }

        return (string) (
            data_get($message, 'conversation')
            ?: data_get($message, 'extendedTextMessage.text')
            ?: data_get($message, 'imageMessage.caption')
            ?: data_get($message, 'videoMessage.caption')
            ?: data_get($message, 'documentMessage.caption')
            ?: (data_get($message, 'base64') ? '[Mídia recebida]' : '[Mensagem recebida]')
        );
    }
}
