<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    protected $fillable = [
        'tenant_id',
        'eyebrow',
        'headline',
        'subheadline',
        'hero_image',
        'hero_badge_title',
        'hero_badge_body',
        'cta_label',
        'sections',
        'testimonials',
        'google_place_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'analytics_head',
        'conversion_pixel',
        'custom_javascript',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'testimonials' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return list<array{name: string, role: string|null, quote: string, rating: int}>
     */
    public function publishedTestimonials(): array
    {
        return collect($this->testimonials ?? [])
            ->filter(fn ($item) => is_array($item)
                && filled($item['name'] ?? null)
                && filled($item['quote'] ?? null))
            ->map(fn (array $item) => [
                'name' => trim((string) $item['name']),
                'role' => filled($item['role'] ?? null) ? trim((string) $item['role']) : null,
                'quote' => trim((string) $item['quote']),
                'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
            ])
            ->values()
            ->all();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
