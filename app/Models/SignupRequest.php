<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignupRequest extends Model
{
    protected $fillable = [
        'owner_name',
        'business_name',
        'email',
        'whatsapp_phone',
        'business_type',
        'monthly_leads',
        'main_challenge',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
