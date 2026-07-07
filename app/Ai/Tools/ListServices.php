<?php

namespace App\Ai\Tools;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lista os serviços ativos da loja (nome, preço, duração, descrição).
 *
 * Use quando o cliente quer saber o que a loja oferece ou quanto custa, sem
 * ainda escolher horário. Para propor datas/horários, use a disponibilidade.
 */
class ListServices implements Tool
{
    public function __construct(private readonly Tenant $tenant) {}

    public function description(): Stringable|string
    {
        return 'Lista os serviços ativos da loja com nome, preço, duração e descrição. '
            .'Use para responder o que a loja oferece e quanto custa. Não invente serviços nem preços.';
    }

    public function handle(Request $request): Stringable|string
    {
        $services = $this->tenant->services()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => array_filter([
                'service_id' => $service->id,
                'name' => $service->name,
                'category' => $service->category,
                'price' => 'R$ '.number_format((float) $service->price, 2, ',', '.'),
                'duration_minutes' => $service->duration_minutes,
                'description' => $service->description,
            ], fn ($value) => filled($value)))
            ->all();

        if ($services === []) {
            return 'A loja ainda não cadastrou serviços.';
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
