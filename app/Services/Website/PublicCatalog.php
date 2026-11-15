<?php

namespace App\Services\Website;

use App\Models\BagProduct;
use App\Models\Material;
use App\Models\WebsiteCategory;
use App\Models\WebsiteGalleryItem;
use Illuminate\Database\Eloquent\Builder;

class PublicCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return WebsiteCategory::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (WebsiteCategory $category) => [
                'slug' => $category->slug,
                'name' => $category->name,
                'cardTitle' => $category->card_title,
                'summary' => $category->summary,
                'body' => $category->body,
                'isSample' => $category->is_sample,
                'href' => route('website.public.products.category', $category->slug),
                'productCount' => $this->countFor($category->slug),
            ])
            ->all();
    }

    public function findCategory(string $slug): ?WebsiteCategory
    {
        return WebsiteCategory::query()
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function products(?string $category = null, ?string $search = null): array
    {
        $term = $this->searchTerm($search);

        $bags = $this->bagQuery()
            ->when($category, fn (Builder $query) => $query->where('public_category', $category))
            ->when($term, function (Builder $query) use ($term) {
                $query->where(function (Builder $inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('public_summary', 'like', $term)
                        ->orWhere('size_label', 'like', $term)
                        ->orWhere('bag_type', 'like', $term);
                });
            })
            ->with('color:id,name')
            ->orderBy('public_sort')
            ->orderBy('name')
            ->get();

        $materials = $this->materialQuery()
            ->when($category, fn (Builder $query) => $query->where('public_category', $category))
            ->when($term, function (Builder $query) use ($term) {
                $query->where(function (Builder $inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('public_summary', 'like', $term);
                });
            })
            ->with('category:id,name')
            ->orderBy('public_sort')
            ->orderBy('name')
            ->get();

        return $bags
            ->map(fn (BagProduct $product) => $this->presentBag($product))
            ->concat($materials->map(fn (Material $material) => $this->presentMaterial($material)))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findProduct(string $category, string $slug): ?array
    {
        $bag = $this->bagQuery()
            ->where('public_category', $category)
            ->where('public_slug', $slug)
            ->with('color:id,name')
            ->first();

        if ($bag) {
            return $this->presentBag($bag, true);
        }

        $material = $this->materialQuery()
            ->where('public_category', $category)
            ->where('public_slug', $slug)
            ->with('category:id,name')
            ->first();

        return $material ? $this->presentMaterial($material, true) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gallery(): array
    {
        return WebsiteGalleryItem::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (WebsiteGalleryItem $item) => [
                'title' => $item->title,
                'caption' => $item->caption,
                'imageUrl' => route('website.public.media.gallery', $item),
            ])
            ->all();
    }

    private function countFor(string $category): int
    {
        return $this->bagQuery()->where('public_category', $category)->count()
            + $this->materialQuery()->where('public_category', $category)->count();
    }

    private function bagQuery(): Builder
    {
        return BagProduct::query()
            ->where('is_public', true)
            ->where('status', 'active')
            ->whereNotNull('public_slug')
            ->whereNotNull('public_category');
    }

    private function materialQuery(): Builder
    {
        return Material::query()
            ->where('is_public', true)
            ->where('status', 'active')
            ->whereNotNull('public_slug')
            ->whereNotNull('public_category');
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBag(BagProduct $product, bool $detailed = false): array
    {
        $payload = [
            'source' => 'bag',
            'name' => $product->name,
            'slug' => $product->public_slug,
            'category' => $product->public_category,
            'categoryName' => $this->categoryName($product->public_category),
            'summary' => $product->public_summary,
            'size' => $product->size_label,
            'color' => $product->color?->name,
            'thickness' => $this->measure($product->thickness_micron, 'micron'),
            'material' => null,
            'typeLabel' => config('bag.bag_types')[$product->bag_type] ?? null,
            'imageUrl' => $product->public_image_path
                ? route('website.public.media.catalog', ['source' => 'bag', 'slug' => $product->public_slug])
                : null,
            'href' => route('website.public.products.show', [
                'category' => $product->public_category,
                'slug' => $product->public_slug,
            ]),
            'quoteHref' => route('website.public.quote', [
                'category' => $product->public_category,
                'product' => $product->public_slug,
            ]),
        ];

        if ($detailed) {
            $payload['description'] = $product->public_description;
            $payload['specs'] = array_values(array_filter([
                $this->spec('Type', config('bag.bag_types')[$product->bag_type] ?? null),
                $this->spec('Size', $product->size_label),
                $this->spec('Width', $this->measure($product->width_mm, 'mm')),
                $this->spec('Length', $this->measure($product->length_mm, 'mm')),
                $this->spec('Thickness', $this->measure($product->thickness_micron, 'micron')),
                $this->spec('Color', $product->color?->name),
            ]));
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMaterial(Material $material, bool $detailed = false): array
    {
        $payload = [
            'source' => 'material',
            'name' => $material->name,
            'slug' => $material->public_slug,
            'category' => $material->public_category,
            'categoryName' => $this->categoryName($material->public_category),
            'summary' => $material->public_summary,
            'size' => null,
            'color' => null,
            'thickness' => null,
            'material' => $material->category?->name,
            'typeLabel' => null,
            'imageUrl' => $material->public_image_path
                ? route('website.public.media.catalog', ['source' => 'material', 'slug' => $material->public_slug])
                : null,
            'href' => route('website.public.products.show', [
                'category' => $material->public_category,
                'slug' => $material->public_slug,
            ]),
            'quoteHref' => route('website.public.quote', [
                'category' => $material->public_category,
                'product' => $material->public_slug,
            ]),
        ];

        if ($detailed) {
            $payload['description'] = $material->public_description;
            $payload['specs'] = array_values(array_filter([
                $this->spec('Material category', $material->category?->name),
            ]));
        }

        return $payload;
    }

    /**
     * @return array{label: string, value: string}|null
     */
    private function spec(string $label, mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ['label' => $label, 'value' => (string) $value];
    }

    private function categoryName(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $this->categoryNames ??= WebsiteCategory::query()->pluck('name', 'slug')->all();

        return $this->categoryNames[$slug] ?? $slug;
    }

    private function searchTerm(?string $search): ?string
    {
        $search = trim((string) $search);
        if ($search === '') {
            return null;
        }

        return '%'.addcslashes($search, '%_\\').'%';
    }

    /** @var array<string, string>|null */
    private ?array $categoryNames = null;

    private function measure(mixed $value, string $unit): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $number.' '.$unit;
    }
}
