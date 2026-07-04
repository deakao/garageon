<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'vehicle_id',
        'public_token',
        'status',
        'total',
        'quoted_at',
        'paid_at',
        'payment_method',
        'valid_until',
        'last_follow_up_at',
        'channel',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'quoted_at' => 'datetime',
            'paid_at' => 'datetime',
            'valid_until' => 'date',
            'last_follow_up_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->public_token)) {
                $quote->public_token = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function publicUrl(): string
    {
        return route('quotes.public', $this->public_token);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
