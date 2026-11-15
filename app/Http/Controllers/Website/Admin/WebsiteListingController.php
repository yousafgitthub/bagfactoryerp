<?php

namespace App\Http\Controllers\Website\Admin;

use App\Http\Controllers\Controller;
use App\Models\BagProduct;
use App\Models\Material;
use App\Support\WebsiteUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteListingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'website');

        $search = trim($request->string('q')->toString());
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        $bags = BagProduct::query()
            ->select(['id', 'code', 'name', 'status', 'is_public', 'public_slug', 'public_category'])
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(12, ['*'], 'bags_page')
            ->withQueryString()
            ->through(fn (BagProduct $product) => $this->row($product, 'bag'));

        $materials = Material::query()
            ->select(['id', 'code', 'name', 'status', 'is_public', 'public_slug', 'public_category'])
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(12, ['*'], 'materials_page')
            ->withQueryString()
            ->through(fn (Material $material) => $this->row($material, 'material'));

        return Inertia::render('Website/Admin/Listings/Index', [
            'pageTitle' => 'Public listings',
            'breadcrumbs' => $this->crumbs('Public listings'),
            'bags' => $bags,
            'materials' => $materials,
            'filters' => ['q' => $search],
            'canManage' => $request->user()?->can('website.manage') === true,
        ]);
    }

    public function edit(Request $request, string $source, int $id): Response
    {
        abort_unless($request->user()?->can('website.manage'), 403);
        $model = $this->model($source, $id);

        return Inertia::render('Website/Admin/Listings/Edit', [
            'pageTitle' => 'Edit public listing',
            'breadcrumbs' => $this->crumbs('Edit listing'),
            'listing' => $this->formPayload($model, $source),
            'categories' => $this->categoryOptions($source),
        ]);
    }

    public function update(Request $request, string $source, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('website.manage'), 403);
        $model = $this->model($source, $id);

        $request->merge([
            'is_public' => $request->boolean('is_public'),
            'remove_image' => $request->boolean('remove_image'),
        ]);

        $allowed = $source === 'bag'
            ? config('website.bag_categories', [])
            : config('website.material_categories', []);

        $data = $request->validate([
            'is_public' => ['required', 'boolean'],
            'public_slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'public_category' => ['nullable', Rule::in($allowed)],
            'public_summary' => ['nullable', 'string', 'max:500'],
            'public_description' => ['nullable', 'string', 'max:5000'],
            'public_sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'remove_image' => ['required', 'boolean'],
            'image' => ['nullable', 'file', 'max:'.(int) config('website.max_upload_kb', 5120)],
        ]);

        if ($data['is_public'] && empty($data['public_category'])) {
            throw ValidationException::withMessages([
                'public_category' => 'Choose a public category before publishing.',
            ]);
        }

        $slug = $data['public_slug'] ?: ($data['is_public'] ? Str::slug($model->name) : null);
        $category = $data['public_category'] ?: null;

        if ($data['is_public'] && ($slug === null || $slug === '')) {
            throw ValidationException::withMessages([
                'public_slug' => 'Enter a public URL slug.',
            ]);
        }

        if ($slug && $category && $this->slugTaken($slug, $category, $source, $model->id)) {
            throw ValidationException::withMessages([
                'public_slug' => 'That public URL is already used in this category.',
            ]);
        }

        if ($request->file('image') instanceof UploadedFile) {
            $this->assertImage($request->file('image'));
        }

        $path = $model->public_image_path;

        if ($data['remove_image'] || $request->file('image')) {
            WebsiteUpload::delete($path);
            $path = null;
        }

        if ($request->file('image') instanceof UploadedFile) {
            $path = WebsiteUpload::store(
                $request->file('image'),
                'website/listings',
                config('website.image_extensions', [])
            );
        }

        $model->update([
            'is_public' => $data['is_public'],
            'public_slug' => $slug ?: null,
            'public_category' => $category,
            'public_summary' => $this->clean($data['public_summary'] ?? null),
            'public_description' => $this->clean($data['public_description'] ?? null),
            'public_sort' => (int) ($data['public_sort'] ?? 0),
            'public_image_path' => $path,
        ]);

        return redirect()
            ->route('website.admin.listings.edit', ['source' => $source, 'id' => $model->id])
            ->with('status', 'Public listing saved. Internal prices and stock were not changed.');
    }

    public function image(string $source, int $id): StreamedResponse
    {
        $this->authorize('viewModule', 'website');
        $model = $this->model($source, $id);
        abort_unless(is_string($model->public_image_path) && $model->public_image_path !== '', 404);

        return WebsiteUpload::inlineImage($model->public_image_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Model $model, string $source): array
    {
        return [
            'id' => $model->id,
            'source' => $source,
            'code' => $model->code,
            'name' => $model->name,
            'status' => $model->status,
            'isPublic' => (bool) $model->is_public,
            'category' => $model->public_category,
            'slug' => $model->public_slug,
            'href' => route('website.admin.listings.edit', ['source' => $source, 'id' => $model->id]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(Model $model, string $source): array
    {
        return [
            'source' => $source,
            'id' => $model->id,
            'code' => $model->code,
            'name' => $model->name,
            'status' => $model->status,
            'isPublic' => (bool) $model->is_public,
            'publicSlug' => $model->public_slug,
            'publicCategory' => $model->public_category,
            'publicSummary' => $model->public_summary,
            'publicDescription' => $model->public_description,
            'publicSort' => $model->public_sort,
            'imageUrl' => $model->public_image_path
                ? route('website.admin.listings.image', ['source' => $source, 'id' => $model->id])
                : null,
        ];
    }

    private function model(string $source, int $id): BagProduct|Material
    {
        abort_unless(in_array($source, ['bag', 'material'], true), 404);

        $model = $source === 'bag'
            ? BagProduct::query()->find($id)
            : Material::query()->find($id);

        abort_unless($model, 404);

        return $model;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryOptions(string $source): array
    {
        $slugs = $source === 'bag'
            ? config('website.bag_categories', [])
            : config('website.material_categories', []);

        return collect($slugs)->map(fn (string $slug) => [
            'value' => $slug,
            'label' => (string) (\App\Models\WebsiteCategory::query()->where('slug', $slug)->value('name') ?: $slug),
        ])->all();
    }

    private function slugTaken(string $slug, string $category, string $source, int $id): bool
    {
        $bag = BagProduct::query()
            ->where('public_slug', $slug)
            ->where('public_category', $category)
            ->when($source === 'bag', fn ($query) => $query->where('id', '!=', $id))
            ->exists();

        $material = Material::query()
            ->where('public_slug', $slug)
            ->where('public_category', $category)
            ->when($source === 'material', fn ($query) => $query->where('id', '!=', $id))
            ->exists();

        return $bag || $material;
    }

    private function assertImage(UploadedFile $file): void
    {
        $allowed = array_map(
            fn (string $ext) => $ext === 'jpeg' ? 'jpg' : $ext,
            config('website.image_extensions', [])
        );
        $client = strtolower($file->getClientOriginalExtension());
        $client = $client === 'jpeg' ? 'jpg' : $client;
        $guessed = strtolower((string) $file->guessExtension());
        $guessed = $guessed === 'jpeg' ? 'jpg' : $guessed;
        $original = strtolower($file->getClientOriginalName());

        if (
            preg_match('/\.(php|phtml|phar|svg|exe|js|html|htm)(\.|$)/i', $original)
            || ! in_array($client, $allowed, true)
            || ! in_array($guessed, $allowed, true)
        ) {
            throw ValidationException::withMessages([
                'image' => 'Upload a JPG, PNG, or WEBP image.',
            ]);
        }
    }

    private function clean(mixed $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : $value;
    }

    /**
     * @return list<array{label: string, href?: string}>
     */
    private function crumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Website', 'href' => route('website.admin.hub')],
            ['label' => $leaf],
        ];
    }
}
