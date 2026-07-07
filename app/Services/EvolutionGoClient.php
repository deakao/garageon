<?php

namespace App\Services;

use App\Models\WhatsappConnection;
use App\Support\WhatsappPhone;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class EvolutionGoClient
{
    public function configured(): bool
    {
        return filled(config('services.evolution_go.url')) && filled(config('services.evolution_go.api_key'));
    }

    /**
     * Monta a URL de webhook que será entregue à Evolution.
     *
     * Em ambiente Docker o APP_URL público (ex.: http://localhost:8001) não é
     * acessível de dentro do container da Evolution. Quando
     * EVOLUTION_GO_WEBHOOK_BASE_URL está definido, usamos essa base interna
     * (ex.: http://nginx) preservando o path da rota nomeada.
     */
    public function webhookUrl(string $secret): string
    {
        $url = route('evolution.webhook', $secret);
        $base = config('services.evolution_go.webhook_base_url');

        if (! filled($base)) {
            return $url;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return rtrim((string) $base, '/').$path.($query ? '?'.$query : '');
    }

    /**
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function allInstances(): array
    {
        return $this->sendRequest(fn () => $this->http()->get($this->url('/instance/all')));
    }

    /**
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function createInstance(string $name, ?string $token = null): array
    {
        return $this->sendRequest(fn () => $this->http()->post($this->url('/instance/create'), array_filter([
            'name' => $name,
            'token' => $token,
            'advancedSettings' => [
                'alwaysOnline' => true,
                'readMessages' => false,
                'rejectCall' => false,
                'ignoreGroups' => true,
                'ignoreStatus' => true,
            ],
        ], fn ($value) => filled($value))));
    }

    /**
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function deleteInstance(string $instanceId): array
    {
        return $this->sendRequest(fn () => $this->http()->delete($this->url('/instance/delete/'.$instanceId)));
    }

    /**
     * @param  array<int, string>  $events
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function connect(WhatsappConnection $connection, string $webhookUrl, array $events, ?string $phone = null): array
    {
        $body = [
            'webhookUrl' => $webhookUrl,
            'subscribe' => $events,
            'immediate' => true,
        ];

        if (filled($phone)) {
            $body['phone'] = WhatsappPhone::normalize($phone);
        }

        return $this->sendRequest(fn () => $this->http($connection)->post($this->url('/instance/connect'), $body));
    }

    /**
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function status(WhatsappConnection $connection): array
    {
        return $this->sendRequest(fn () => $this->http($connection)->get($this->url('/instance/status')));
    }

    /**
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function qrCode(WhatsappConnection $connection): array
    {
        return $this->sendRequest(fn () => $this->http($connection)->get($this->url('/instance/qr')));
    }

    /**
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    public function sendText(WhatsappConnection $connection, string $number, string $text): array
    {
        return $this->sendRequest(fn () => $this->http($connection)->post($this->url('/send/text'), [
            'number' => WhatsappPhone::normalize($number),
            'text' => $text,
            'delay' => 0,
        ]));
    }

    private function http(?WhatsappConnection $connection = null): PendingRequest
    {
        $headers = [
            'apikey' => (string) ($connection?->instance_token ?: config('services.evolution_go.api_key')),
        ];

        if ($connection?->instance_id) {
            $headers['instanceId'] = $connection->instance_id;
        }

        return Http::acceptJson()
            ->asJson()
            ->withHeaders($headers)
            ->timeout((int) config('services.evolution_go.timeout', 12));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.evolution_go.url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  callable(): Response  $callback
     * @return array{successful: bool, payload?: array<string, mixed>, message?: string}
     */
    private function sendRequest(callable $callback): array
    {
        if (! $this->configured()) {
            return [
                'successful' => false,
                'message' => 'Configure EVOLUTION_GO_URL e EVOLUTION_GO_API_KEY antes de conectar o WhatsApp.',
            ];
        }

        try {
            $response = $callback();
            $payload = $response->json();

            if (! is_array($payload)) {
                $payload = [];
            }

            if (! $response->successful() || data_get($payload, 'success') === false) {
                return [
                    'successful' => false,
                    'payload' => $payload,
                    'message' => $this->errorMessage($payload),
                ];
            }

            return [
                'successful' => true,
                'payload' => $payload,
            ];
        } catch (Throwable $exception) {
            return [
                'successful' => false,
                'message' => 'Não consegui falar com a Evolution agora. Confira a URL e a API key.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function errorMessage(array $payload): string
    {
        $message = data_get($payload, 'error.message')
            ?: data_get($payload, 'message')
            ?: data_get($payload, 'error')
            ?: data_get($payload, 'errors.0.message')
            ?: data_get($payload, 'errors.0');

        return is_string($message) && $message !== ''
            ? $message
            : 'A Evolution não aceitou a solicitação agora.';
    }
}
