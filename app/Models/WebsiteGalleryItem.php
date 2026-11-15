<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteGalleryItem extends Model
{
    protected $fillable = [
        'title',
        'caption',
        'image_path',
        'is_visible',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }
}
