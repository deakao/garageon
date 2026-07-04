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
            'published_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
