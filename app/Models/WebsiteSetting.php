<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'company_name',
        'tagline',
        'hero_title',
        'hero_subtitle',
        'phone',
        'email',
        'whatsapp',
        'address',
        'map_url',
        'facebook_url',
        'linkedin_url',
        'about',
        'manufacturing',
        'why_choose_us',
        'feature_strip',
        'show_sample_catalog_notice',
    ];

    protected function casts(): array
    {
        return [
            'why_choose_us' => 'array',
            'feature_strip' => 'array',
            'show_sample_catalog_notice' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'company_name' => 'Bag Factory',
            'tagline' => 'Quality Bags. Better Tomorrow.',
            'hero_title' => "Quality Bags.\nBuilt for Your Business.",
            'show_sample_catalog_notice' => true,
        ]);
    }
}
