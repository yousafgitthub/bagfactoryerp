<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\BagProduct;
use App\Models\Material;
use App\Models\WebsiteGalleryItem;
use App\Support\WebsiteUpload;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMediaController extends Controller
{
    public function catalog(string $source, string $slug): StreamedResponse
    {
        abort_unless(in_array($source, ['bag', 'material'], true), 404);

        $path = $source === 'bag'
            ? BagProduct::query()
                ->where('is_public', true)
                ->where('status', 'active')
                ->where('public_slug', $slug)
                ->value('public_image_path')
            : Material::query()
                ->where('is_public', true)
                ->where('status', 'active')
                ->where('public_slug', $slug)
                ->value('public_image_path');

        abort_unless(is_string($path) && $path !== '', 404);

        return WebsiteUpload::inlineImage($path);
    }

    public function gallery(WebsiteGalleryItem $item): StreamedResponse
    {
        abort_unless($item->is_visible, 404);

        return WebsiteUpload::inlineImage($item->image_path);
    }
}
