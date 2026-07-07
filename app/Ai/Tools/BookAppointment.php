<?php

namespace App\Ai\Tools;

use App\Models\Customer;
use App\Models\Service;
use App\Models\VirtualAttendant;
use App\Models\WhatsappConversation;
use App\Services\BookingAvailability;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Efetiva um agendamento para o cliente da conversa atual.
 *
 * O cliente é resolvido pelo telefone da conversa (criado se ainda não existir),
 * então o modelo não precisa perguntar telefone — só serviço, data e horário.
 *
 * Se o atendente exige confirmação manual, o agendamento é criado como "pending"
 * (aguardando a loja confirmar); caso contrário, já entra como "scheduled".
 */
class BookAppointment implements Tool
{
    public function __construct(
        private readonly VirtualAttendant $attendant,
        private readonly WhatsappConversation $conversation,
    ) {}

    public function description(): Stringable|string
    {
        return 'Cria um agendamento para o cliente. Use somente após o cliente confirmar '
            .'serviço, data e horário exatos. Os valores de service_id, date e time devem vir '
            .'da ferramenta de disponibilidade.';
    }

    public function handle(Request $request): Stringable|string
    {
        $tenant = $this->attendant->tenant;
        $serviceId = (int) ($request['service_id'] ?? 0);
        $date = trim((string) ($request['date'] ?? ''));
        $time = trim((string) ($request['time'] ?? ''));
        $customerName = trim((string) ($request['customer_name'] ?? ''));

        $service = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->find($serviceId);

        if (! $service) {
            return 'Serviço inválido. Consulte a disponibilidade antes de agendar.';
        }

        $customer = $this->resolveCustomer($customerName);
        $needsConfirmation = (bool) $this->attendant->require_booking_confirmation;

        $appointment = app(BookingAvailability::class)->book(
            $tenant,
            $service,
            $customer,
            $date,
            $time,
            [
                'source' => 'whatsapp-attendant',
                'status' => $needsConfirmation ? 'pending' : 'scheduled',
            ],
        );

        if (! $appointment) {
            return "O horário {$time} de {$date} não está mais disponível. Consulte a disponibilidade e ofereça outro.";
        }

        return json_encode([
            'confirmed' => ! $needsConfirmation,
            'pending_store_confirmation' => $needsConfirmation,
            'service' => $service->name,
            'date' => $date,
            'time' => $time,
            'message_to_customer' => $needsConfirmation
                ? 'Solicitação registrada. A loja vai confirmar o horário com você em breve.'
                : 'Agendamento confirmado.',
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'service_id' => $schema->integer()->description('ID do serviço retornado pela disponibilidade.')->required(),
            'date' => $schema->string()->description('Data no formato AAAA-MM-DD.')->required(),
            'time' => $schema->string()->description('Horário no formato HH:MM.')->required(),
            'customer_name' => $schema->string()->description('Nome do cliente para o cadastro.')->required(),
        ];
    }

    private function resolveCustomer(string $name): Customer
    {
        if ($this->conversation->customer_id) {
            $customer = $this->conversation->customer;

            if ($customer) {
                if (filled($name) && $customer->name !== $name) {
                    $customer->update(['name' => $name]);
                }

                return $customer;
            }
        }

        $customer = Customer::query()->firstOrCreate(
            ['tenant_id' => $this->attendant->tenant_id, 'phone' => $this->conversation->contact_phone],
            ['name' => $name ?: ($this->conversation->contact_name ?: 'Cliente WhatsApp'), 'tags' => ['whatsapp-attendant']],
        );

        $this->conversation->update(['customer_id' => $customer->id]);

        return $customer;
    }
}
