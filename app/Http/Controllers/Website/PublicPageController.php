<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Services\Website\EnquiryRecorder;
use App\Services\Website\PublicCatalog;
use App\Services\Website\WebsitePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __construct(
        private readonly PublicCatalog $catalog,
        private readonly WebsitePresenter $presenter,
        private readonly EnquiryRecorder $enquiries,
    ) {}

    public function home(): Response
    {
        return Inertia::render('Website/Home', $this->presenter->home());
    }

    public function about(): Response
    {
        $settings = WebsiteSetting::current();

        return Inertia::render('Website/About', [
            'title' => 'About Us',
            'body' => $this->text($settings->about),
            'empty' => $this->text($settings->about) === null,
        ]);
    }

    public function products(Request $request): Response
    {
        return $this->productIndex($request, null);
    }

    public function category(string $category): Response
    {
        $record = $this->catalog->findCategory($category);
        abort_unless($record, 404);

        return Inertia::render('Website/Products/Index', [
            'title' => $record->name,
            'activeCategory' => $category,
            'categories' => $this->catalog->categories(),
            'category' => [
                'name' => $record->name,
                'summary' => $record->summary,
                'body' => $record->body,
                'isSample' => $record->is_sample,
            ],
            'products' => $this->catalog->products($category),
        ]);
    }

    public function show(string $category, string $slug): Response
    {
        $record = $this->catalog->findCategory($category);
        $product = $this->catalog->findProduct($category, $slug);
        abort_unless($record && $product, 404);

        return Inertia::render('Website/Products/Show', [
            'title' => $product['name'],
            'category' => [
                'name' => $record->name,
                'href' => route('website.public.products.category', $record->slug),
            ],
            'product' => $product,
            'quoteHref' => route('website.public.quote', [
                'category' => $category,
                'product' => $slug,
            ]),
        ]);
    }

    public function customOrder(): Response
    {
        return Inertia::render('Website/CustomOrder', [
            'title' => 'Custom Order',
            'formToken' => $this->enquiries->issueToken(),
            'bagTypes' => $this->options(config('bag.bag_types', [])),
            'printOptions' => $this->options(config('website.print_options', [])),
        ]);
    }

    public function quote(Request $request): Response
    {
        $category = $request->string('category')->toString();
        $slug = $request->string('product')->toString();
        $knownCategory = $this->catalog->findCategory($category);
        $product = ($knownCategory && $slug !== '') ? $this->catalog->findProduct($category, $slug) : null;

        return Inertia::render('Website/Quote', [
            'title' => 'Request a Quote',
            'formToken' => $this->enquiries->issueToken(),
            'categories' => $this->catalog->categories(),
            'products' => $this->catalog->products(),
            'prefill' => [
                'category' => $knownCategory ? $category : '',
                'product_slug' => $product['slug'] ?? '',
            ],
        ]);
    }

    public function manufacturing(): Response
    {
        $settings = WebsiteSetting::current();

        return Inertia::render('Website/Manufacturing', [
            'title' => 'Manufacturing',
            'body' => $this->text($settings->manufacturing),
            'empty' => $this->text($settings->manufacturing) === null,
        ]);
    }

    public function gallery(): Response
    {
        return Inertia::render('Website/Gallery', [
            'title' => 'Gallery',
            'items' => $this->catalog->gallery(),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Website/Contact', [
            'title' => 'Contact Us',
            'formToken' => $this->enquiries->issueToken(),
        ]);
    }

    public function testimonials(): Response
    {
        return Inertia::render('Website/Testimonials', [
            'title' => 'Testimonials',
            'testimonials' => $this->presenter->testimonials(),
        ]);
    }

    private function productIndex(Request $request, ?string $category): Response
    {
        return Inertia::render('Website/Products/Index', [
            'title' => 'Products',
            'activeCategory' => $category,
            'categories' => $this->catalog->categories(),
            'category' => null,
            'products' => $this->catalog->products($category),
        ]);
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{value: string, label: string}>
     */
    private function options(array $options): array
    {
        $rows = [];

        foreach ($options as $value => $label) {
            $rows[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return $rows;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
