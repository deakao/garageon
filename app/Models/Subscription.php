<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'name',
        'recurrence_rule',
        'amount',
        'billing_gateway',
        'status',
        'next_charge_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_charge_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
