<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'service_id',
        'vehicle_id',
        'source',
        'status',
        'scheduled_at',
        'ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function serviceSummary(): string
    {
        $items = $this->relationLoaded('serviceItems') ? $this->serviceItems : $this->serviceItems()->get();

        if ($items->isNotEmpty()) {
            return $items
                ->map(fn (AppointmentService $item) => $item->quantity > 1 ? $item->name.' ('.$item->quantity.'x)' : $item->name)
                ->join(' + ');
        }

        return $this->service?->name ?? 'Serviço removido';
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
