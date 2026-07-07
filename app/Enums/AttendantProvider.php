<?php

namespace App\Enums;

enum AttendantProvider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';

    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI (ChatGPT)',
            self::Anthropic => 'Anthropic (Claude)',
            self::Gemini => 'Google (Gemini)',
        };
    }

    /**
     * Modelo padrão usado quando o tenant não informa um específico.
     */
    public function defaultModel(): string
    {
        return match ($this) {
            self::OpenAI => 'gpt-4o-mini',
            self::Anthropic => 'claude-haiku-4-5-20251001',
            self::Gemini => 'gemini-2.0-flash',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $provider) => [$provider->value => $provider->label()])
            ->all();
    }
}
