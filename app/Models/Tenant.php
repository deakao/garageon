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

    public const ONBOARDING_STEPS = [
        'hours',
        'services',
        'logo',
        'attendant',
        'landing',
    ];

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
        'onboarding_step',
        'onboarding_completed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            // Tenants criados fora do signup (seeders/testes) já nascem com onboarding concluído.
            if ($tenant->onboarding_step === null && $tenant->onboarding_completed_at === null) {
                $tenant->onboarding_completed_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'brand_colors' => 'array',
            'trial_ends_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function markOnboardingStep(string $step): void
    {
        if (! in_array($step, self::ONBOARDING_STEPS, true)) {
            throw new \InvalidArgumentException("Invalid onboarding step [{$step}].");
        }

        $this->forceFill([
            'onboarding_step' => $step,
            'onboarding_completed_at' => null,
        ])->save();
    }

    public function completeOnboarding(): void
    {
        $this->forceFill([
            'onboarding_step' => null,
            'onboarding_completed_at' => now(),
        ])->save();
    }

    public function nextOnboardingStep(?string $current = null): ?string
    {
        $current ??= $this->onboarding_step ?? self::ONBOARDING_STEPS[0];
        $index = array_search($current, self::ONBOARDING_STEPS, true);

        if ($index === false) {
            return self::ONBOARDING_STEPS[0];
        }

        return self::ONBOARDING_STEPS[$index + 1] ?? null;
    }

    public function previousOnboardingStep(?string $current = null): ?string
    {
        $current ??= $this->onboarding_step ?? self::ONBOARDING_STEPS[0];
        $index = array_search($current, self::ONBOARDING_STEPS, true);

        if ($index === false || $index === 0) {
            return null;
        }

        return self::ONBOARDING_STEPS[$index - 1];
    }

    /**
     * @return array<string, bool>
     */
    public function onboardingChecklist(): array
    {
        return [
            'hours' => $this->operatingHours()->exists(),
            'services' => $this->services()->where('is_active', true)->exists(),
            'logo' => filled($this->logo_path),
            'attendant' => $this->virtualAttendant()->exists(),
            'landing' => $this->landingPage()->exists(),
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

    public function quoteFunnelAutomations(): HasMany
    {
        return $this->hasMany(QuoteFunnelAutomation::class);
    }

    public function whatsappConnection(): HasOne
    {
        return $this->hasOne(WhatsappConnection::class);
    }

    public function virtualAttendant(): HasOne
    {
        return $this->hasOne(VirtualAttendant::class);
    }

    public function whatsappConversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class);
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
