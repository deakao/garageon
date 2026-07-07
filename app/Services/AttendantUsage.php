<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Cota diária de respostas do atendente virtual por tenant.
 *
 * O limite vem do plano do tenant (plans.ai_daily_message_limit) e o consumo é
 * contado no cache por dia. O contador reseta sozinho na virada do dia porque a
 * chave inclui a data e expira ao fim do dia.
 *
 * ponytail: contagem no cache (rápida e suficiente). Se precisar de auditoria/
 * relatório histórico de uso, migrar para uma tabela ai_usage_daily.
 */
class AttendantUsage
{
    /** Cota diária quando o tenant não tem plano associado. */
    private const FALLBACK_LIMIT = 50;

    public function limitFor(Tenant $tenant): int
    {
        return (int) ($tenant->plan?->ai_daily_message_limit ?? self::FALLBACK_LIMIT);
    }

    public function usedToday(Tenant $tenant): int
    {
        return (int) Cache::get($this->key($tenant), 0);
    }

    public function remainingToday(Tenant $tenant): int
    {
        return max(0, $this->limitFor($tenant) - $this->usedToday($tenant));
    }

    public function hasReachedLimit(Tenant $tenant): bool
    {
        return $this->usedToday($tenant) >= $this->limitFor($tenant);
    }

    /**
     * Registra uma resposta consumida hoje. A chave expira no fim do dia.
     */
    public function record(Tenant $tenant): void
    {
        $key = $this->key($tenant);

        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    private function key(Tenant $tenant): string
    {
        return "attendant-usage:{$tenant->id}:".now()->toDateString();
    }
}
