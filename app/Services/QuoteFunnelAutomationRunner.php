<?php

namespace App\Services;

use App\Jobs\RunQuoteFunnelAutomation;
use App\Mail\QuoteFunnelAutomationMail;
use App\Models\Quote;
use App\Models\QuoteFunnelAutomation;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Support\WhatsappPhone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class QuoteFunnelAutomationRunner
{
    public function __construct(private EvolutionGoClient $evolution) {}

    /**
     * Dispara as automações ativas da etapa, respeitando o atraso configurado.
     */
    public function dispatchForStage(Quote $quote, string $stage): void
    {
        $quote->loadMissing(['tenant', 'customer', 'vehicle']);

        $automations = QuoteFunnelAutomation::query()
            ->where('tenant_id', $quote->tenant_id)
            ->where('stage', $stage)
            ->where('is_active', true)
            ->get();

        foreach ($automations as $automation) {
            $job = new RunQuoteFunnelAutomation($quote, $automation);
            $delayMinutes = $automation->delayInMinutes();

            if ($delayMinutes > 0) {
                dispatch($job)->delay(now()->addMinutes($delayMinutes));
            } else {
                dispatch($job);
            }
        }
    }

    public function run(Quote $quote, QuoteFunnelAutomation $automation): void
    {
        $quote = $quote->fresh(['tenant', 'customer', 'vehicle']) ?? $quote;
        $automation = $automation->fresh() ?? $automation;

        if (! $automation->is_active || $quote->status !== $automation->stage || $quote->status === 'approved') {
            return;
        }

        if ($automation->channel === 'whatsapp') {
            $this->sendWhatsapp($quote, $automation);

            return;
        }

        if ($automation->channel === 'email') {
            $this->sendEmail($quote, $automation);
        }
    }

    private function sendWhatsapp(Quote $quote, QuoteFunnelAutomation $automation): void
    {
        $connection = $quote->tenant->whatsappConnection()->first();

        if (! $connection?->instance_id || $connection->status !== 'connected') {
            Log::info('Automação de funil WhatsApp ignorada: conexão indisponível.', [
                'quote_id' => $quote->id,
                'tenant_id' => $quote->tenant_id,
                'stage' => $automation->stage,
            ]);

            return;
        }

        $phone = WhatsappPhone::normalize($quote->customer->phone);

        if ($phone === '') {
            Log::info('Automação de funil WhatsApp ignorada: telefone inválido.', [
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
            ]);

            return;
        }

        $body = $automation->renderTemplate($quote);

        try {
            $conversation = WhatsappConversation::query()->firstOrCreate([
                'tenant_id' => $quote->tenant_id,
                'contact_phone' => $phone,
            ], [
                'customer_id' => $quote->customer_id,
                'contact_name' => $quote->customer->name,
                'status' => 'open',
            ]);

            $result = $this->evolution->sendText($connection, $conversation->contact_phone, $body);
            $payload = $result['payload'] ?? [];
            $externalId = data_get($payload, 'data.Info.ID') ?: data_get($payload, 'messageId');
            $status = $result['successful'] ? 'sent' : 'failed';

            $messageData = [
                'tenant_id' => $quote->tenant_id,
                'whatsapp_conversation_id' => $conversation->id,
                'customer_id' => $quote->customer_id,
                'external_id' => $externalId,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $body,
                'status' => $status,
                'payload' => $payload ?: ['error' => $result['message'] ?? null, 'source' => 'quote_funnel_automation'],
                'occurred_at' => now(),
            ];

            if ($externalId) {
                WhatsappMessage::query()->updateOrCreate([
                    'tenant_id' => $quote->tenant_id,
                    'external_id' => $externalId,
                ], $messageData);
            } else {
                WhatsappMessage::query()->create($messageData);
            }

            $conversation->forceFill([
                'customer_id' => $quote->customer_id,
                'contact_name' => $quote->customer->name,
                'last_message' => $body,
                'last_message_at' => now(),
            ])->save();

            $quote->forceFill(['last_follow_up_at' => now()])->save();
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar automação WhatsApp do funil.', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendEmail(Quote $quote, QuoteFunnelAutomation $automation): void
    {
        if (blank($quote->customer->email)) {
            Log::info('Automação de funil e-mail ignorada: cliente sem e-mail.', [
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
            ]);

            return;
        }

        try {
            $body = $automation->renderTemplate($quote);
            $subject = $automation->renderSubject($quote);

            Mail::mailer(config('mail.default'))
                ->to($quote->customer->email)
                ->send(new QuoteFunnelAutomationMail($quote, $automation, $body, $subject));

            $quote->forceFill(['last_follow_up_at' => now()])->save();
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar automação e-mail do funil.', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
