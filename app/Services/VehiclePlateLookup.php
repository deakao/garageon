<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VehiclePlateLookup
{
    /**
     * @return array{plate: string, brand: string|null, model: string|null, year: int|null, color: string|null, source: string}|null
     */
    public function lookup(string $plate): ?array
    {
        $plate = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $plate) ?? '');
        $provider = config('services.vehicle_plate.provider', 'apiplacas');

        return match ($provider) {
            'apiplacas' => $this->lookupWithApiPlacas($plate),
            'brasildados' => $this->lookupWithBrasilDados($plate),
            default => null,
        };
    }

    /**
     * @return array{plate: string, brand: string|null, model: string|null, year: int|null, color: string|null, source: string}|null
     */
    private function lookupWithApiPlacas(string $plate): ?array
    {
        $token = config('services.vehicle_plate.apiplacas_token');

        if (! $token) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.vehicle_plate.apiplacas_url'), '/');
        $response = Http::acceptJson()
            ->timeout(8)
            ->get("{$baseUrl}/{$plate}/{$token}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data)) {
            return null;
        }

        $year = $data['anoModelo'] ?? $data['ano'] ?? data_get($data, 'extra.ano_modelo') ?? data_get($data, 'extra.ano_fabricacao');

        return $this->normalize([
            'plate' => $data['placa'] ?? $plate,
            'brand' => $data['marca'] ?? $data['MARCA'] ?? null,
            'model' => $data['modelo'] ?? $data['MODELO'] ?? null,
            'year' => $year,
            'color' => $data['cor'] ?? data_get($data, 'extra.cor'),
            'source' => 'apiplacas',
        ]);
    }

    /**
     * @return array{plate: string, brand: string|null, model: string|null, year: int|null, color: string|null, source: string}|null
     */
    private function lookupWithBrasilDados(string $plate): ?array
    {
        $token = config('services.vehicle_plate.brasildados_token');

        if (! $token) {
            return null;
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->timeout(12)
            ->post(config('services.vehicle_plate.brasildados_url'), [
                'placas' => [$plate],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data)) {
            return null;
        }

        $item = array_is_list($data) ? ($data[0] ?? null) : $data;

        if (! is_array($item) || (array_key_exists('encontrado', $item) && ! $item['encontrado'])) {
            return null;
        }

        $vehicle = $item['veiculo'] ?? $item;

        if (! is_array($vehicle)) {
            return null;
        }

        $year = $vehicle['anoModelo'] ?? $vehicle['anoFabricacao'] ?? null;

        return $this->normalize([
            'plate' => $vehicle['placa'] ?? $item['placaConsultada'] ?? $plate,
            'brand' => $vehicle['marca'] ?? null,
            'model' => $vehicle['modelo'] ?? null,
            'year' => $year,
            'color' => $vehicle['cor'] ?? null,
            'source' => 'brasildados',
        ]);
    }

    /**
     * @param  array{plate: mixed, brand: mixed, model: mixed, year: mixed, color: mixed, source: mixed}  $vehicle
     * @return array{plate: string, brand: string|null, model: string|null, year: int|null, color: string|null, source: string}|null
     */
    private function normalize(array $vehicle): ?array
    {
        if (! $vehicle['brand'] || ! $vehicle['model']) {
            return null;
        }

        return [
            'plate' => Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $vehicle['plate']) ?? ''),
            'brand' => $vehicle['brand'] ? (string) $vehicle['brand'] : null,
            'model' => $vehicle['model'] ? (string) $vehicle['model'] : null,
            'year' => is_numeric($vehicle['year']) ? (int) $vehicle['year'] : null,
            'color' => $vehicle['color'] ? (string) $vehicle['color'] : null,
            'source' => (string) $vehicle['source'],
        ];
    }
}
