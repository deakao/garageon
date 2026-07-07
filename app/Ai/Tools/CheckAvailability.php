<?php

namespace App\Ai\Tools;

use App\Models\Tenant;
use App\Services\BookingAvailability;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lista serviços e horários livres da loja para o modelo propor ao cliente.
 */
class CheckAvailability implements Tool
{
    public function __construct(private readonly Tenant $tenant) {}

    public function description(): Stringable|string
    {
        return 'Lista os serviços ativos da loja e os horários disponíveis nos próximos dias. '
            .'Use antes de propor qualquer data ou horário ao cliente. Não invente horários.';
    }

    public function handle(Request $request): Stringable|string
    {
        $availability = app(BookingAvailability::class)->forTenant($this->tenant, 14);
        $services = collect($availability['services'])
            ->map(fn (array $service) => [
                'service_id' => $service['id'],
                'name' => $service['name'],
                'duration_minutes' => $service['duration'],
                'price' => $service['price'],
                // Limita a 5 dias/6 horários por dia para caber no contexto.
                'available_days' => collect($service['days'])->take(5)->map(fn (array $day) => [
                    'date' => $day['date'],
                    'label' => $day['date_label'],
                    'times' => collect($day['times'])->pluck('value')->take(6)->all(),
                ])->all(),
            ])
            ->values()
            ->all();

        if ($services === []) {
            return 'Nenhum serviço ativo ou nenhum horário disponível no momento.';
        }

        return json_encode(['services' => $services], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
