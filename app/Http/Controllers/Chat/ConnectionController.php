<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConnection;
use App\Services\EvolutionConnectionManager;
use App\Services\WhatsappConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    public function __construct(private readonly EvolutionConnectionManager $manager) {}

    /**
     * Conectar: cria/reutiliza a instancia, conecta e gera o QR Code efemero.
     */
    public function connect(Request $request): JsonResponse|RedirectResponse
    {
        $this->guardTenantUser();
        $tenant = $request->user()->tenants()->firstOrFail();

        try {
            $result = $this->manager->connect($tenant);
        } catch (WhatsappConnectionException $e) {
            return $this->respondFailure($request, $e);
        }

        return $this->respond(
            $request,
            $result['connection'],
            $result['message'],
            $result['qrcode'],
        );
    }

    /**
     * Desconectar: remove a instancia na Evolution e limpa o estado local.
     */
    public function disconnect(Request $request): JsonResponse|RedirectResponse
    {
        $this->guardTenantUser();
        $tenant = $request->user()->tenants()->firstOrFail();
        $connection = $tenant->whatsappConnection()->firstOrFail();

        $message = $this->manager->disconnect($connection);

        return $this->respond($request, $connection, $message);
    }

    /**
     * Sincronizar: le o status real na Evolution.
     */
    public function sync(Request $request): JsonResponse|RedirectResponse
    {
        $this->guardTenantUser();
        $tenant = $request->user()->tenants()->firstOrFail();
        $connection = $tenant->whatsappConnection()->firstOrFail();

        try {
            $result = $this->manager->sync($connection);
        } catch (WhatsappConnectionException $e) {
            return $this->respondFailure($request, $e);
        }

        return $this->respond($request, $result['connection'], $result['message']);
    }

    /**
     * Renovar QR Code sob demanda.
     */
    public function renewQr(Request $request): JsonResponse|RedirectResponse
    {
        $this->guardTenantUser();
        $tenant = $request->user()->tenants()->firstOrFail();
        $connection = $tenant->whatsappConnection()->firstOrFail();

        try {
            $result = $this->manager->renewQrCode($connection);
        } catch (WhatsappConnectionException $e) {
            return $this->respondFailure($request, $e);
        }

        return $this->respond($request, $result['connection'], $result['message'], $result['qrcode']);
    }

    private function guardTenantUser(): void
    {
        abort_if(auth()->user()->isPlatformAdmin(), 403);
    }

    private function respond(Request $request, WhatsappConnection $connection, string $message, ?string $qrcode = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($this->state($connection, $message, $qrcode));
        }

        return redirect()->route('chat.index')->with('status', $message);
    }

    private function respondFailure(Request $request, WhatsappConnectionException $e): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($this->state($e->connection, $e->getMessage()), $e->statusCode);
        }

        return back()->withErrors(['whatsapp' => $e->getMessage()]);
    }

    /**
     * @return array{status: string, connected: bool, qrcode: ?string, message: ?string}
     */
    private function state(WhatsappConnection $connection, ?string $message = null, ?string $qrcode = null): array
    {
        $hasInstance = filled($connection->instance_id) && filled($connection->instance_token);

        return [
            'status' => $connection->status,
            'connected' => $connection->status === 'connected',
            'qrcode' => $hasInstance && $connection->status !== 'connected' ? $qrcode : null,
            'message' => $message,
        ];
    }
}
