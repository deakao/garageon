<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'legal_name',
        'document',
        'whatsapp_phone',
        'primary_domain',
        'logo_path',
        'brand_colors',
        'status',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'brand_colors' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')->withPivot('role')->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(TenantServiceCategory::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function operatingHours(): HasMany
    {
        return $this->hasMany(TenantOperatingHour::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(TenantHoliday::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function orderBumps(): HasMany
    {
        return $this->hasMany(OrderBump::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function landingPage(): HasOne
    {
        return $this->hasOne(LandingPage::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? '/storage/'.ltrim($this->logo_path, '/') : null;
    }
}
