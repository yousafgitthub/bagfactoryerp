<?php

namespace App\Services\Website;

use App\Models\BagProduct;
use App\Models\Material;
use App\Models\WebsiteCategory;
use App\Models\WebsiteEnquiry;
use App\Support\WebsiteUpload;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class EnquiryRecorder
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{duplicate: bool, enquiry: WebsiteEnquiry}
     */
    public function record(string $type, array $input, ?UploadedFile $artwork = null): array
    {
        $token = (string) ($input['idempotency_token'] ?? '');
        $existingToken = WebsiteEnquiry::query()->where('idempotency_token', $token)->first();

        if ($existingToken) {
            return ['duplicate' => true, 'enquiry' => $existingToken];
        }

        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $phone = $this->normalizePhone((string) ($input['phone'] ?? ''));
        $subject = $this->subjectKey($type, $input);

        $window = max(1, (int) config('website.duplicate_window_minutes', 15));
        $recent = WebsiteEnquiry::query()
            ->where('type', $type)
            ->where('email', $email)
            ->where('phone_normalized', $phone)
            ->where('subject_key', $subject)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->first();

        if ($recent) {
            return ['duplicate' => true, 'enquiry' => $recent];
        }

        $path = null;
        $original = null;

        if ($artwork instanceof UploadedFile) {
            $path = WebsiteUpload::store(
                $artwork,
                'website/enquiries',
                config('website.artwork_extensions', [])
            );
            $original = WebsiteUpload::originalName($artwork);
        }

        try {
            $enquiry = WebsiteEnquiry::query()->create([
                'type' => $type,
                'status' => 'new',
                'idempotency_token' => $token,
                'name' => $input['name'],
                'email' => $email,
                'phone' => $phone === '' ? null : trim((string) ($input['phone'] ?? '')),
                'phone_normalized' => $phone,
                'company' => $this->blankToNull($input['company'] ?? null),
                'subject_key' => $subject,
                'product_label' => $this->productLabel($type, $input),
                'message' => $this->blankToNull($input['message'] ?? null),
                'details' => $this->details($type, $input),
                'artwork_path' => $path,
                'artwork_original_name' => $original,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            WebsiteUpload::delete($path);
            $enquiry = WebsiteEnquiry::query()->where('idempotency_token', $token)->first();

            if (! $enquiry) {
                throw $exception;
            }

            return ['duplicate' => true, 'enquiry' => $enquiry];
        }

        return ['duplicate' => false, 'enquiry' => $enquiry];
    }

    public function acceptsToken(string $token): bool
    {
        if (! Str::isUuid($token)) {
            return false;
        }

        $issued = session()->get('website.form_tokens', []);

        if (is_array($issued) && array_key_exists($token, $issued)) {
            return true;
        }

        return WebsiteEnquiry::query()->where('idempotency_token', $token)->exists();
    }

    public function issueToken(): string
    {
        $token = (string) Str::uuid();
        $issued = session()->get('website.form_tokens', []);

        if (! is_array($issued)) {
            $issued = [];
        }

        $issued[$token] = now()->timestamp;

        if (count($issued) > 10) {
            $issued = array_slice($issued, -10, null, true);
        }

        session()->put('website.form_tokens', $issued);

        return $token;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function subjectKey(string $type, array $input): string
    {
        if ($type === 'contact') {
            return 'contact';
        }

        if ($type === 'custom_order') {
            $size = strtolower(trim((string) ($input['size'] ?? '')));
            $color = strtolower(trim((string) ($input['color'] ?? '')));
            $bag = (string) ($input['bag_type'] ?? '');

            return mb_substr('custom:'.$bag.'|'.$size.'|'.$color, 0, 190);
        }

        $product = strtolower(trim((string) ($input['product_slug'] ?? '')));
        $category = strtolower(trim((string) ($input['category'] ?? '')));
        $key = $product !== '' ? 'product:'.$product : ($category !== '' ? 'category:'.$category : 'general');

        return mb_substr('quote:'.$key, 0, 190);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function productLabel(string $type, array $input): string
    {
        if ($type === 'contact') {
            return 'Contact';
        }

        if ($type === 'custom_order') {
            $bag = config('bag.bag_types')[$input['bag_type']] ?? 'Custom bag';
            $size = trim((string) ($input['size'] ?? ''));

            return mb_substr(trim($bag.' '.$size), 0, 160);
        }

        $slug = trim((string) ($input['product_slug'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));

        if ($slug !== '' && $category !== '') {
            $bag = BagProduct::query()
                ->where('is_public', true)
                ->where('public_category', $category)
                ->where('public_slug', $slug)
                ->value('name');

            if (is_string($bag) && $bag !== '') {
                return mb_substr($bag, 0, 160);
            }

            $material = Material::query()
                ->where('is_public', true)
                ->where('public_category', $category)
                ->where('public_slug', $slug)
                ->value('name');

            if (is_string($material) && $material !== '') {
                return mb_substr($material, 0, 160);
            }
        }

        if ($category !== '') {
            $name = WebsiteCategory::query()->where('slug', $category)->where('is_visible', true)->value('name');

            if (is_string($name) && $name !== '') {
                return mb_substr($name, 0, 160);
            }
        }

        return 'General quote';
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    private function details(string $type, array $input): ?array
    {
        if ($type !== 'custom_order') {
            $category = $this->blankToNull($input['category'] ?? null);
            $slug = $this->blankToNull($input['product_slug'] ?? null);

            if ($category === null && $slug === null) {
                return null;
            }

            return array_filter([
                'category' => $category,
                'product_slug' => $slug,
            ]);
        }

        return [
            'bag_type' => $input['bag_type'],
            'bag_type_label' => config('bag.bag_types')[$input['bag_type']] ?? $input['bag_type'],
            'size' => $input['size'],
            'thickness' => $this->blankToNull($input['thickness'] ?? null),
            'color' => $this->blankToNull($input['color'] ?? null),
            'print' => $this->blankToNull($input['print'] ?? null),
            'print_label' => config('website.print_options')[$input['print'] ?? ''] ?? null,
            'quantity' => (int) $input['quantity'],
        ];
    }

    private function normalizePhone(string $phone): string
    {
        return substr(preg_replace('/\D+/', '', $phone) ?? '', 0, 40);
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
