<?php

namespace App\Http\Controllers\Website\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteCategory;
use App\Models\WebsiteGalleryItem;
use App\Models\WebsiteSetting;
use App\Models\WebsiteTestimonial;
use App\Support\WebsiteUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteContentController extends Controller
{
    public function categories(): Response
    {
        $this->manage();

        return Inertia::render('Website/Admin/Categories', [
            'pageTitle' => 'Website categories',
            'breadcrumbs' => $this->crumbs('Categories'),
            'categories' => WebsiteCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function updateCategory(Request $request, WebsiteCategory $category): RedirectResponse
    {
        $this->manage();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'card_title' => ['nullable', 'string', 'max:80'],
            'summary' => ['required', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:5000'],
            'is_sample' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
        ]);

        $category->update([
            'name' => $this->clean($data['name']),
            'card_title' => $this->clean($data['card_title'] ?? null),
            'summary' => $this->clean($data['summary']),
            'body' => $this->clean($data['body'] ?? null),
            'is_sample' => $request->boolean('is_sample'),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return back()->with('status', 'Category copy saved.');
    }

    public function gallery(): Response
    {
        $this->manage();

        $items = WebsiteGalleryItem::query()->orderBy('sort_order')->orderByDesc('id')->get()
            ->map(fn (WebsiteGalleryItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'caption' => $item->caption,
                'isVisible' => $item->is_visible,
                'sortOrder' => $item->sort_order,
                'imageUrl' => route('website.admin.gallery.image', $item),
            ]);

        return Inertia::render('Website/Admin/Gallery', [
            'pageTitle' => 'Website gallery',
            'breadcrumbs' => $this->crumbs('Gallery'),
            'items' => $items,
        ]);
    }

    public function storeGallery(Request $request): RedirectResponse
    {
        $this->manage();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => ['required', 'file', 'max:'.(int) config('website.max_upload_kb', 5120)],
        ]);

        $file = $request->file('image');
        abort_unless($file instanceof UploadedFile, 422);
        $this->assertImage($file);

        $path = WebsiteUpload::store($file, 'website/gallery', config('website.image_extensions', []));

        WebsiteGalleryItem::query()->create([
            'title' => $this->clean($data['title'] ?? null),
            'caption' => $this->clean($data['caption'] ?? null),
            'image_path' => $path,
            'is_visible' => true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return back()->with('status', 'Gallery image uploaded.');
    }

    public function updateGallery(Request $request, WebsiteGalleryItem $item): RedirectResponse
    {
        $this->manage();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_visible' => ['required', 'boolean'],
        ]);

        $item->update([
            'title' => $this->clean($data['title'] ?? null),
            'caption' => $this->clean($data['caption'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return back()->with('status', 'Gallery image updated.');
    }

    public function destroyGallery(WebsiteGalleryItem $item): RedirectResponse
    {
        $this->manage();
        WebsiteUpload::delete($item->image_path);
        $item->delete();

        return back()->with('status', 'Gallery image removed.');
    }

    public function galleryImage(WebsiteGalleryItem $item): StreamedResponse
    {
        $this->authorize('viewModule', 'website');

        return WebsiteUpload::inlineImage($item->image_path);
    }

    public function testimonials(): Response
    {
        $this->manage();

        return Inertia::render('Website/Admin/Testimonials', [
            'pageTitle' => 'Testimonials',
            'breadcrumbs' => $this->crumbs('Testimonials'),
            'testimonials' => WebsiteTestimonial::query()->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }

    public function storeTestimonial(Request $request): RedirectResponse
    {
        $this->manage();
        WebsiteTestimonial::query()->create($this->testimonialData($request));

        return back()->with('status', 'Testimonial saved. It appears on the public site only while it is visible.');
    }

    public function updateTestimonial(Request $request, WebsiteTestimonial $testimonial): RedirectResponse
    {
        $this->manage();
        $testimonial->update($this->testimonialData($request));

        return back()->with('status', 'Testimonial updated.');
    }

    public function destroyTestimonial(WebsiteTestimonial $testimonial): RedirectResponse
    {
        $this->manage();
        $testimonial->delete();

        return back()->with('status', 'Testimonial removed.');
    }

    public function editSettings(): Response
    {
        $this->authorize('viewModule', 'website');
        $settings = WebsiteSetting::current();

        return Inertia::render('Website/Admin/Settings', [
            'pageTitle' => 'Website settings',
            'breadcrumbs' => $this->crumbs('Settings'),
            'canManage' => request()->user()?->can('website.manage') === true,
            'settings' => [
                'company_name' => $settings->company_name,
                'tagline' => $settings->tagline,
                'hero_title' => $settings->hero_title,
                'hero_subtitle' => $settings->hero_subtitle,
                'phone' => $settings->phone,
                'email' => $settings->email,
                'whatsapp' => $settings->whatsapp,
                'address' => $settings->address,
                'map_url' => $settings->map_url,
                'facebook_url' => $settings->facebook_url,
                'linkedin_url' => $settings->linkedin_url,
                'about' => $settings->about,
                'manufacturing' => $settings->manufacturing,
                'why_choose_us' => $settings->why_choose_us ?: [],
                'feature_strip' => $settings->feature_strip ?: [],
                'show_sample_catalog_notice' => $settings->show_sample_catalog_notice,
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->manage();

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'hero_title' => ['nullable', 'string', 'max:200'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'map_url' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'string', 'max:500'],
            'linkedin_url' => ['nullable', 'string', 'max:500'],
            'about' => ['nullable', 'string', 'max:8000'],
            'manufacturing' => ['nullable', 'string', 'max:8000'],
            'why_choose_us' => ['nullable', 'array', 'max:8'],
            'why_choose_us.*.title' => ['nullable', 'string', 'max:80'],
            'why_choose_us.*.body' => ['nullable', 'string', 'max:300'],
            'feature_strip' => ['nullable', 'array', 'max:6'],
            'feature_strip.*' => ['nullable', 'string', 'max:60'],
            'show_sample_catalog_notice' => ['required', 'boolean'],
        ]);

        $reasons = [];
        foreach ($data['why_choose_us'] ?? [] as $reason) {
            $title = $this->clean($reason['title'] ?? null);
            if ($title === null) {
                continue;
            }
            $reasons[] = ['title' => $title, 'body' => $this->clean($reason['body'] ?? null) ?? ''];
        }

        $features = array_values(array_filter(array_map(
            fn ($item) => $this->clean($item),
            $data['feature_strip'] ?? []
        )));

        WebsiteSetting::current()->update([
            'company_name' => $this->clean($data['company_name']) ?: 'Bag Factory',
            'tagline' => $this->clean($data['tagline'] ?? null),
            'hero_title' => $this->clean($data['hero_title'] ?? null),
            'hero_subtitle' => $this->clean($data['hero_subtitle'] ?? null),
            'phone' => $this->clean($data['phone'] ?? null),
            'email' => $this->clean($data['email'] ?? null),
            'whatsapp' => $this->clean($data['whatsapp'] ?? null),
            'address' => $this->clean($data['address'] ?? null),
            'map_url' => $this->clean($data['map_url'] ?? null),
            'facebook_url' => $this->clean($data['facebook_url'] ?? null),
            'linkedin_url' => $this->clean($data['linkedin_url'] ?? null),
            'about' => $this->clean($data['about'] ?? null),
            'manufacturing' => $this->clean($data['manufacturing'] ?? null),
            'why_choose_us' => $reasons,
            'feature_strip' => $features,
            'show_sample_catalog_notice' => $request->boolean('show_sample_catalog_notice'),
        ]);

        return back()->with('status', 'Website settings saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function testimonialData(Request $request): array
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:1000'],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return [
            'author_name' => $this->clean($data['author_name']),
            'author_role' => $this->clean($data['author_role'] ?? null),
            'quote' => $this->clean($data['quote']),
            'is_visible' => $request->boolean('is_visible'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function manage(): void
    {
        abort_unless(request()->user()?->can('website.manage'), 403);
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
        $value = trim(strip_tags(str_replace("\0", '', (string) $value)));

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
