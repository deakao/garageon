<?php

namespace App\Services;

use App\Models\WhatsappConnection;
use RuntimeException;

/**
 * Falha durante o fluxo de conexao WhatsApp, carregando a conexao ja persistida
 * e o status HTTP sugerido para o controller responder.
 */
class WhatsappConnectionException extends RuntimeException
{
    public function __construct(
        public readonly WhatsappConnection $connection,
        string $message,
        public readonly int $statusCode = 502,
    ) {
        parent::__construct($message);
    }
}
