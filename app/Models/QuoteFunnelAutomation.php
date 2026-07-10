<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteFunnelAutomation extends Model
{
    public const STAGES = [
        'sent' => 'Enviado',
        'pending' => 'Aguardando',
        'accepted' => 'Aceito',
        'expired' => 'Expirado',
    ];

    public const CHANNELS = [
        'whatsapp' => 'WhatsApp',
        'email' => 'E-mail',
    ];

    public const DELAY_UNITS = [
        'minutes' => 'Minutos',
        'hours' => 'Horas',
        'days' => 'Dias',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'stage',
        'channel',
        'is_active',
        'delay_value',
        'delay_unit',
        'subject',
        'message_template',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'delay_value' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function delayInMinutes(): int
    {
        $value = max(0, (int) $this->delay_value);

        return match ($this->delay_unit) {
            'hours' => $value * 60,
            'days' => $value * 1440,
            default => $value,
        };
    }

    public function delayLabel(): string
    {
        $value = (int) $this->delay_value;

        if ($value === 0) {
            return 'Imediato';
        }

        $unit = match ($this->delay_unit) {
            'hours' => $value === 1 ? 'hora' : 'horas',
            'days' => $value === 1 ? 'dia' : 'dias',
            default => $value === 1 ? 'minuto' : 'minutos',
        };

        return "Após {$value} {$unit}";
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }

    public function channelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }

    /**
     * @return list<array{name: string, stage: string, channel: string, delay_value: int, delay_unit: string, subject: ?string, message_template: string, is_active: bool}>
     */
    public static function seedBlueprints(): array
    {
        return [
            [
                'name' => 'Envio imediato no WhatsApp',
                'stage' => 'sent',
                'channel' => 'whatsapp',
                'delay_value' => 0,
                'delay_unit' => 'minutes',
                'subject' => null,
                'message_template' => "Olá {{cliente}}! Segue o orçamento da {{loja}}: {{link}}\nValor: {{valor}}",
                'is_active' => false,
            ],
            [
                'name' => 'Follow-up WhatsApp em 1 dia',
                'stage' => 'pending',
                'channel' => 'whatsapp',
                'delay_value' => 1,
                'delay_unit' => 'days',
                'subject' => null,
                'message_template' => "Oi {{cliente}}, passando para lembrar do orçamento {{orcamento}} da {{loja}} ({{valor}}). Posso te ajudar a tirar alguma dúvida?\n{{link}}",
                'is_active' => true,
            ],
            [
                'name' => 'Lembrete por e-mail em 2 horas',
                'stage' => 'pending',
                'channel' => 'email',
                'delay_value' => 2,
                'delay_unit' => 'hours',
                'subject' => 'Lembrete do orçamento {{orcamento}}',
                'message_template' => "Olá {{cliente}},\n\nSeu orçamento {{orcamento}} da {{loja}} ainda está aguardando retorno.\n\nValor: {{valor}}\nLink: {{link}}\n\nEstamos à disposição.",
                'is_active' => false,
            ],
        ];
    }

    public function renderTemplate(Quote $quote, ?string $template = null): string
    {
        $quote->loadMissing(['customer', 'vehicle', 'tenant']);

        $replacements = [
            '{{cliente}}' => $quote->customer->name,
            '{{loja}}' => $quote->tenant->name,
            '{{orcamento}}' => '#'.str_pad((string) $quote->id, 4, '0', STR_PAD_LEFT),
            '{{valor}}' => 'R$ '.number_format((float) $quote->total, 2, ',', '.'),
            '{{placa}}' => $quote->vehicle?->plate ?? '—',
            '{{veiculo}}' => trim(($quote->vehicle?->brand ?? '').' '.($quote->vehicle?->model ?? '')) ?: '—',
            '{{link}}' => $quote->publicUrl(),
            '{{status}}' => self::STAGES[$quote->status] ?? $quote->status,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template ?? $this->message_template);
    }

    public function renderSubject(Quote $quote): string
    {
        $subject = $this->subject ?: 'Orçamento {{orcamento}} - {{loja}}';

        return $this->renderTemplate($quote, $subject);
    }
}
