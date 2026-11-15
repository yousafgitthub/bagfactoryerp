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

    public function category(Request $request, string $category): Response
    {
        return $this->productIndex($request, $category);
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

    public function quality(): Response
    {
        return Inertia::render('Website/Quality', [
            'title' => 'Quality Control',
            'notice' => 'Demonstration process only. This is not a certification, an audit result, or a statement of factory practice.',
            'steps' => [
                [
                    'title' => 'Incoming material check',
                    'body' => 'Demonstration step. A sample process would record the material lot before it is issued to production. This page does not state that this factory performs that check.',
                ],
                [
                    'title' => 'In-process weight check',
                    'body' => 'Demonstration step. A sample process would compare a bag’s weight with the figure stored on the product. No pass rate is published here.',
                ],
                [
                    'title' => 'Print and seal review',
                    'body' => 'Demonstration step. A sample process would look at print placement and seals when an order asks for print. This is not a printing-quality claim.',
                ],
                [
                    'title' => 'Finished-goods count',
                    'body' => 'Demonstration step. A sample process would count good pieces and set rejects aside. Reject rates are not published here.',
                ],
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
        $record = null;
        if ($category !== null) {
            $record = $this->catalog->findCategory($category);
            abort_unless($record, 404);
        }

        $search = trim($request->string('q')->toString());

        return Inertia::render('Website/Products/Index', [
            'title' => $record?->name ?? 'Products',
            'activeCategory' => $category,
            'categories' => $this->catalog->categories(),
            'category' => $record ? [
                'name' => $record->name,
                'summary' => $record->summary,
                'body' => $record->body,
                'isSample' => $record->is_sample,
                'href' => route('website.public.products.category', $record->slug),
            ] : null,
            'search' => $search,
            'products' => $this->catalog->products($category, $search !== '' ? $search : null),
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
