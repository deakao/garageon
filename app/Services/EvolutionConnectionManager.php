<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\WhatsappConnection;
use Illuminate\Support\Str;

/**
 * Orquestra o ciclo de vida da instancia WhatsApp de um tenant na Evolution.
 *
 * Concentra o fluxo que antes vivia inline em routes/web.php:
 *   conectar  -> garante instancia remota viva, conecta e busca QR efemero;
 *   desconectar -> remove a instancia na Evolution e limpa o estado local;
 *   sincronizar -> le o status remoto real;
 *   renovar QR -> pede um novo QR.
 *
 * Metodos que falham lancam WhatsappConnectionException carregando o estado
 * ja persistido, para o controller mapear resposta uma unica vez.
 */
class EvolutionConnectionManager
{
    /** Eventos que a instancia deve assinar ao conectar. */
    private const EVENTS = ['MESSAGE', 'SEND_MESSAGE', 'CONNECTION', 'QRCODE', 'READ_RECEIPT'];

    public function __construct(private readonly EvolutionGoClient $evolution) {}

    public function configured(): bool
    {
        return $this->evolution->configured();
    }

    public function webhookUrl(WhatsappConnection $connection): string
    {
        return $this->evolution->webhookUrl($connection->webhook_secret);
    }

    /**
     * Retorna a conexao do tenant, criando o registro base se ainda nao existir.
     */
    public function connectionFor(Tenant $tenant): WhatsappConnection
    {
        return WhatsappConnection::query()->firstOrCreate([
            'tenant_id' => $tenant->id,
        ], [
            'instance_name' => $this->defaultInstanceName($tenant),
            'status' => 'unconfigured',
        ]);
    }

    /**
     * Passo 1 do fluxo: garante instancia remota, conecta e retorna o QR (efemero).
     *
     * @return array{connection: WhatsappConnection, qrcode: ?string, message: string}
     *
     * @throws WhatsappConnectionException
     */
    public function connect(Tenant $tenant): array
    {
        $connection = $this->connectionFor($tenant);

        $this->ensureRemoteInstance($tenant, $connection);

        if (! $connection->instance_token) {
            $this->fail($connection, 'A Evolution não retornou o token da instância. Remova a instância antiga e conecte novamente.', 422);
        }

        $webhookUrl = $this->webhookUrl($connection);
        $result = $this->evolution->connect($connection, $webhookUrl, self::EVENTS);

        if (! $result['successful']) {
            $this->fail($connection, $result['message'] ?? 'Não consegui iniciar a conexão na Evolution.');
        }

        $connection->forceFill([
            'webhook_url' => $webhookUrl,
            'subscribed_events' => self::EVENTS,
            'status' => 'connecting',
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();

        $qrcode = $this->fetchQrCode($connection);

        if ($qrcode) {
            $connection->forceFill([
                'status' => 'qrcode',
                'qrcode' => null,
                'qrcode_code' => null,
                'last_synced_at' => now(),
            ])->save();
        }

        return [
            'connection' => $connection,
            'qrcode' => $qrcode,
            'message' => 'Escaneie o QR Code no WhatsApp para deixar o atendimento ON.',
        ];
    }

    /**
     * Passo 3 do fluxo: remove a instancia na Evolution e zera o estado local.
     * A falha remota nao trava a operacao; vira aviso em last_error.
     */
    public function disconnect(WhatsappConnection $connection): string
    {
        $warning = null;

        if ($connection->instance_id) {
            $result = $this->evolution->deleteInstance($connection->instance_id);
            $warning = $result['successful'] ? null : ($result['message'] ?? 'Não consegui confirmar a remoção na Evolution.');
        }

        $connection->forceFill([
            'instance_name' => $this->defaultInstanceName($connection->tenant),
            'instance_id' => null,
            'instance_token' => null,
            'qrcode' => null,
            'qrcode_code' => null,
            'subscribed_events' => null,
            'status' => 'unconfigured',
            'connected_at' => null,
            'last_error' => $warning,
            'last_synced_at' => now(),
        ])->save();

        return $warning ? 'WhatsApp desconectado no GarageON. '.$warning : 'WhatsApp desconectado.';
    }

    /**
     * Le o status real na Evolution e persiste o estado de conexao.
     *
     * @return array{connection: WhatsappConnection, message: string}
     *
     * @throws WhatsappConnectionException
     */
    public function sync(WhatsappConnection $connection): array
    {
        if (! $connection->instance_id) {
            $connection->forceFill([
                'status' => 'unconfigured',
                'qrcode' => null,
                'qrcode_code' => null,
                'last_error' => 'Crie a instância antes de consultar o status.',
                'last_synced_at' => now(),
            ])->save();

            $this->fail($connection, $connection->last_error, 422);
        }

        $result = $this->evolution->status($connection);

        if (! $result['successful']) {
            $connection->forceFill([
                'status' => 'disconnected',
                'qrcode' => null,
                'qrcode_code' => null,
                'last_error' => $result['message'] ?? 'Não consegui consultar o status na Evolution.',
                'last_synced_at' => now(),
            ])->save();

            // Falha de status e informativa, nao erro fatal: devolve estado com 200.
            return ['connection' => $connection, 'message' => $connection->last_error];
        }

        $connected = $this->isConnected($result['payload'] ?? []);
        $nextStatus = $connected
            ? 'connected'
            : (in_array($connection->status, ['qrcode', 'connecting'], true) ? $connection->status : 'disconnected');

        $connection->forceFill([
            'status' => $nextStatus,
            'connected_at' => $connected ? now() : $connection->connected_at,
            'qrcode' => null,
            'qrcode_code' => null,
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();

        return [
            'connection' => $connection,
            'message' => $connected
                ? 'WhatsApp conectado e pronto para atendimento.'
                : 'Status atualizado. Gere ou escaneie o QR Code para conectar.',
        ];
    }

    /**
     * Pede um novo QR Code para a instancia ja preparada.
     *
     * @return array{connection: WhatsappConnection, qrcode: string, message: string}
     *
     * @throws WhatsappConnectionException
     */
    public function renewQrCode(WhatsappConnection $connection): array
    {
        if (! $connection->instance_id || ! $connection->instance_token) {
            $this->fail($connection, 'Conecte uma instância antes de renovar o QR Code.', 422);
        }

        $qrcode = $this->fetchQrCode($connection);

        if (! $qrcode) {
            $connection->forceFill([
                'last_error' => 'A Evolution não retornou um novo QR Code.',
                'last_synced_at' => now(),
            ])->save();

            $this->fail($connection, $connection->last_error);
        }

        $connection->forceFill([
            'status' => 'qrcode',
            'qrcode' => null,
            'qrcode_code' => null,
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();

        return ['connection' => $connection, 'qrcode' => $qrcode, 'message' => 'QR Code renovado.'];
    }

    /**
     * Garante uma instancia remota viva para a conexao, criando/recriando quando
     * necessario. Substitui os tres blocos duplicados do fluxo antigo.
     *
     * @throws WhatsappConnectionException
     */
    private function ensureRemoteInstance(Tenant $tenant, WhatsappConnection $connection): void
    {
        $instances = collect(data_get($this->evolution->allInstances(), 'payload.data', []));

        $remote = $connection->instance_id
            ? $instances->first(fn ($i) => data_get($i, 'id') === $connection->instance_id || data_get($i, 'name') === $connection->instance_name)
            : $instances->firstWhere('name', $connection->instance_name ?: $this->defaultInstanceName($tenant));

        if ($remote) {
            $connection->forceFill([
                'instance_name' => data_get($remote, 'name', $connection->instance_name),
                'instance_id' => data_get($remote, 'id', $connection->instance_id),
                'instance_token' => data_get($remote, 'token', $connection->instance_token),
                'last_synced_at' => now(),
            ])->save();

            return;
        }

        $this->createInstance($tenant, $connection);
    }

    /**
     * @throws WhatsappConnectionException
     */
    private function createInstance(Tenant $tenant, WhatsappConnection $connection): void
    {
        $instanceName = $connection->instance_name ?: $this->defaultInstanceName($tenant);
        $token = (string) Str::uuid();
        $result = $this->evolution->createInstance($instanceName, $token);

        if (! $result['successful']) {
            $connection->forceFill([
                'instance_name' => $instanceName,
                'instance_id' => null,
                'instance_token' => null,
                'status' => 'error',
                'last_error' => $result['message'] ?? 'Não consegui preparar o WhatsApp agora.',
                'last_synced_at' => now(),
            ])->save();

            $this->fail($connection, $connection->last_error);
        }

        $instance = data_get($result, 'payload.data', $result['payload'] ?? []);

        $connection->forceFill([
            'instance_name' => data_get($instance, 'name', $instanceName),
            'instance_id' => data_get($instance, 'id'),
            'instance_token' => data_get($instance, 'token', $token),
            'status' => data_get($instance, 'connected') ? 'connected' : 'disconnected',
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();
    }

    /**
     * QR Code e efemero: nunca persistido, apenas retornado para a resposta.
     */
    private function fetchQrCode(WhatsappConnection $connection): ?string
    {
        $result = $this->evolution->qrCode($connection);

        if (! $result['successful']) {
            return null;
        }

        $payload = $result['payload'] ?? [];

        return data_get($payload, 'data.Qrcode')
            ?: data_get($payload, 'data.qrcode')
            ?: data_get($payload, 'base64')
            ?: null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isConnected(array $payload): bool
    {
        $connectedFlag = filter_var(
            data_get($payload, 'data.Connected', data_get($payload, 'data.connected')),
            FILTER_VALIDATE_BOOLEAN
        );
        $loggedIn = data_get($payload, 'data.LoggedIn', data_get($payload, 'data.loggedIn'));

        return $connectedFlag && ($loggedIn === null || filter_var($loggedIn, FILTER_VALIDATE_BOOLEAN));
    }

    private function defaultInstanceName(Tenant $tenant): string
    {
        return 'garageon-'.$tenant->id;
    }

    /**
     * @throws WhatsappConnectionException
     */
    private function fail(WhatsappConnection $connection, string $message, int $status = 502): never
    {
        throw new WhatsappConnectionException($connection, $message, $status);
    }
}
