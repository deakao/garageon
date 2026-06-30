<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'date',
        'repeats_yearly',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'repeats_yearly' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
