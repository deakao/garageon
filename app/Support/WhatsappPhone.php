<?php

namespace App\Support;

class WhatsappPhone
{
    /**
     * Normaliza um telefone para o formato E.164 do Brasil (sem o "+"),
     * garantindo o código do país 55. Isso mantém o envio pela Evolution e o
     * casamento com os JIDs recebidos (que chegam como 55DDD...) consistentes.
     */
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return '';
        }

        // Números locais (DDD + número, 10 ou 11 dígitos) ganham o DDI 55.
        if (in_array(strlen($digits), [10, 11], true)) {
            return '55'.$digits;
        }

        return $digits;
    }

    public static function fromJid(?string $jid): string
    {
        $identifier = str($jid ?? '')->before('@')->before(':')->toString();

        return self::normalize($identifier);
    }
}
