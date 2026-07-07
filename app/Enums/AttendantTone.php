<?php

namespace App\Enums;

enum AttendantTone: string
{
    case Friendly = 'friendly';
    case Objective = 'objective';
    case Consultative = 'consultative';
    case Enthusiastic = 'enthusiastic';

    public function label(): string
    {
        return match ($this) {
            self::Friendly => 'Simpático',
            self::Objective => 'Objetivo',
            self::Consultative => 'Consultivo',
            self::Enthusiastic => 'Entusiasmado',
        };
    }

    /**
     * Instrução de estilo injetada no system prompt.
     */
    public function instruction(): string
    {
        return match ($this) {
            self::Friendly => 'Fale de forma calorosa, próxima e acolhedora, como um atendente que conhece o cliente pelo nome.',
            self::Objective => 'Fale de forma direta e enxuta, sem rodeios, resolvendo a solicitação no menor número de mensagens.',
            self::Consultative => 'Fale de forma consultiva, entendendo a necessidade do cliente e recomendando o serviço mais adequado antes de agendar.',
            self::Enthusiastic => 'Fale com energia e entusiasmo pela loja, transmitindo confiança no resultado dos serviços, sem exagerar.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tone) => [$tone->value => $tone->label()])
            ->all();
    }
}
