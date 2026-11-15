<?php

namespace App\Services\Website;

use App\Models\WebsiteSetting;
use App\Models\WebsiteTestimonial;

class WebsitePresenter
{
    public function __construct(private readonly PublicCatalog $catalog) {}

    /**
     * @return array<string, mixed>
     */
    public function shared(): array
    {
        $settings = WebsiteSetting::current();
        $company = trim((string) $settings->company_name) ?: 'Bag Factory';
        $testimonials = WebsiteTestimonial::query()->where('is_visible', true)->count();
        $phone = $this->phone($settings->phone);
        $email = $this->email($settings->email);
        $whatsapp = $this->whatsapp($settings->whatsapp);

        $nav = [
            ['label' => 'Home', 'href' => route('website.public.home'), 'match' => 'home'],
            ['label' => 'About Us', 'href' => route('website.public.about'), 'match' => 'about'],
            ['label' => 'Products', 'href' => route('website.public.products'), 'match' => 'products'],
            ['label' => 'Manufacturing', 'href' => route('website.public.manufacturing'), 'match' => 'manufacturing'],
            ['label' => 'Gallery', 'href' => route('website.public.gallery'), 'match' => 'gallery'],
        ];

        if ($testimonials > 0) {
            $nav[] = ['label' => 'Testimonials', 'href' => route('website.public.testimonials'), 'match' => 'testimonials'];
        }

        $nav[] = ['label' => 'Contact Us', 'href' => route('website.public.contact'), 'match' => 'contact'];

        return [
            'companyName' => $company,
            'tagline' => $this->blank($settings->tagline),
            'phone' => $phone,
            'email' => $email,
            'whatsappUrl' => $whatsapp,
            'address' => $this->blank($settings->address),
            'mapUrl' => $this->httpsUrl($settings->map_url, config('website.map_hosts', [])),
            'facebookUrl' => $this->httpsUrl($settings->facebook_url, config('website.facebook_hosts', [])),
            'linkedinUrl' => $this->httpsUrl($settings->linkedin_url, config('website.linkedin_hosts', [])),
            'hasTestimonials' => $testimonials > 0,
            'showSampleNotice' => (bool) $settings->show_sample_catalog_notice,
            'quoteHref' => route('website.public.quote'),
            'loginHref' => route('login'),
            'nav' => $nav,
            'categories' => collect($this->catalog->categories())
                ->map(fn (array $category) => [
                    'name' => $category['name'],
                    'href' => $category['href'],
                    'slug' => $category['slug'],
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function home(): array
    {
        $settings = WebsiteSetting::current();

        return [
            'heroLines' => $this->heroLines($settings->hero_title),
            'heroSubtitle' => $this->blank($settings->hero_subtitle),
            'features' => $this->features($settings->feature_strip),
            'categories' => $this->catalog->categories(),
            'whyChooseUs' => $this->reasons($settings->why_choose_us),
            'testimonials' => $this->testimonials(),
            'productsHref' => route('website.public.products'),
            'customOrderHref' => route('website.public.custom-order'),
            'aboutHref' => route('website.public.about'),
        ];
    }

    /**
     * @return list<array{authorName: string, authorRole: ?string, quote: string}>
     */
    public function testimonials(): array
    {
        return WebsiteTestimonial::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['author_name', 'author_role', 'quote'])
            ->map(fn (WebsiteTestimonial $row) => [
                'authorName' => $row->author_name,
                'authorRole' => $row->author_role,
                'quote' => $row->quote,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function heroLines(?string $title): array
    {
        $raw = trim((string) $title);
        if ($raw === '') {
            $raw = "Quality Bags.\nBuilt for Your Business.";
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));

        if (count($lines) === 1 && str_contains($lines[0], '.')) {
            [$first, $rest] = array_pad(explode('.', $lines[0], 2), 2, '');
            $lines = array_values(array_filter([
                trim($first).'.',
                trim($rest),
            ], fn (string $line) => $line !== '' && $line !== '.'));
        }

        return $lines === [] ? ['Quality Bags.', 'Built for Your Business.'] : $lines;
    }

    /**
     * @return list<string>
     */
    private function features(mixed $strip): array
    {
        if (! is_array($strip)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? trim($item) : '',
            $strip
        )));
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    private function reasons(mixed $reasons): array
    {
        if (! is_array($reasons)) {
            return [];
        }

        $clean = [];

        foreach ($reasons as $reason) {
            if (! is_array($reason)) {
                continue;
            }

            $title = trim((string) ($reason['title'] ?? ''));
            $body = trim((string) ($reason['body'] ?? ''));

            if ($title === '') {
                continue;
            }

            $clean[] = ['title' => $title, 'body' => $body];
        }

        return $clean;
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function phone(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('/^[0-9+\-\s()]{6,40}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function email(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    private function whatsapp(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

    /**
     * @param  list<string>  $hosts
     */
    private function httpsUrl(mixed $url, array $hosts): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (($parts['scheme'] ?? '') !== 'https' || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);

        foreach ($hosts as $allowed) {
            $allowed = strtolower($allowed);

            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return $url;
            }
        }

        return null;
    }
}
