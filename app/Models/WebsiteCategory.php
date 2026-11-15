<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'card_title',
        'summary',
        'body',
        'is_sample',
        'is_visible',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_sample' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }
}
