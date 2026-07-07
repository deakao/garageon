<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WhatsappConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'instance_name',
        'instance_id',
        'instance_token',
        'webhook_secret',
        'webhook_url',
        'status',
        'qrcode',
        'qrcode_code',
        'subscribed_events',
        'last_error',
        'last_synced_at',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'instance_token' => 'encrypted',
            'subscribed_events' => 'array',
            'last_synced_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WhatsappConnection $connection): void {
            if (! $connection->webhook_secret) {
                $connection->webhook_secret = Str::random(48);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
