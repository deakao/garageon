<?php

namespace App\Models;

use App\Enums\AttendantProvider;
use App\Enums\AttendantTone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAttendant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'tone',
        'provider',
        'model',
        'api_key',
        'context',
        'require_booking_confirmation',
        'is_active',
    ];

    protected $attributes = [
        'name' => 'Piloto Automático',
        'tone' => 'friendly',
        'provider' => 'openai',
        'require_booking_confirmation' => false,
        'is_active' => false,
    ];

    protected function casts(): array
    {
        return [
            'tone' => AttendantTone::class,
            'provider' => AttendantProvider::class,
            'api_key' => 'encrypted',
            'require_booking_confirmation' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Só pode atender se estiver ligado e houver uma chave utilizável:
     * a do próprio tenant (BYOK, sem limite) ou a da plataforma (com limite do plano).
     */
    public function isOperational(): bool
    {
        return $this->is_active && filled($this->resolveApiKey());
    }

    /**
     * Tenant trouxe a própria chave? Nesse caso o custo é dele e não há limite.
     */
    public function usesOwnKey(): bool
    {
        return filled($this->api_key);
    }

    /**
     * Chave a ser usada: a do tenant, com fallback para a da plataforma.
     */
    public function resolveApiKey(): ?string
    {
        return $this->api_key ?: config("ai.providers.{$this->provider->value}.key");
    }

    public function modelName(): string
    {
        return $this->model ?: $this->provider->defaultModel();
    }
}
