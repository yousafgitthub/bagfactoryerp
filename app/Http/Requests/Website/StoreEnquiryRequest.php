<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (['name', 'email', 'phone', 'company', 'message', 'category', 'product_slug', 'bag_type', 'size', 'thickness', 'color', 'print', 'idempotency_token'] as $field) {
            if ($this->exists($field)) {
                $clean[$field] = trim(strip_tags(str_replace("\0", '', (string) $this->input($field))));
            }
        }

        if (isset($clean['email'])) {
            $clean['email'] = strtolower($clean['email']);
        }

        $this->merge($clean);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->enquiryType();
        $artworkExtensions = config('website.artwork_extensions', []);
        $maxKb = (int) config('website.max_upload_kb', 5120);

        $rules = [
            'idempotency_token' => ['required', 'uuid'],
            'company_website' => ['nullable', 'string', 'max:200'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'phone' => [$type === 'contact' ? 'nullable' : 'required', 'string', 'max:40'],
            'message' => [$type === 'custom_order' ? 'nullable' : 'required', 'string', 'max:5000'],
        ];

        if ($type === 'quote') {
            $rules['category'] = ['nullable', 'string', 'max:64'];
            $rules['product_slug'] = ['nullable', 'string', 'max:160'];
        }

        if ($type === 'custom_order') {
            $rules['bag_type'] = ['required', Rule::in(array_keys(config('bag.bag_types', [])))];
            $rules['size'] = ['required', 'string', 'max:80'];
            $rules['thickness'] = ['nullable', 'string', 'max:40'];
            $rules['color'] = ['nullable', 'string', 'max:40'];
            $rules['print'] = ['nullable', Rule::in(array_keys(config('website.print_options', [])))];
            $rules['quantity'] = ['required', 'integer', 'min:1', 'max:10000000'];
            $rules['artwork'] = [
                'nullable',
                'file',
                'max:'.$maxKb,
                function (string $attribute, mixed $value, \Closure $fail) use ($artworkExtensions): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $original = strtolower($value->getClientOriginalName());
                    $clientExtension = strtolower($value->getClientOriginalExtension());
                    $guessed = strtolower((string) $value->guessExtension());

                    if (preg_match('/\.(php|phtml|phar|svg|exe|js|html|htm)(\.|$)/i', $original)) {
                        $fail('This file type is not allowed.');

                        return;
                    }

                    $allowed = array_map(fn (string $ext) => $ext === 'jpeg' ? 'jpg' : $ext, $artworkExtensions);
                    $clientExtension = $clientExtension === 'jpeg' ? 'jpg' : $clientExtension;
                    $guessed = $guessed === 'jpeg' ? 'jpg' : $guessed;

                    if (! in_array($clientExtension, $allowed, true) || ! in_array($guessed, $allowed, true)) {
                        $fail('Upload a JPG, PNG, WEBP, or PDF file.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function enquiryType(): string
    {
        if ($this->routeIs('website.public.custom-order.store')) {
            return 'custom_order';
        }

        if ($this->routeIs('website.public.contact.store')) {
            return 'contact';
        }

        return 'quote';
    }

    public function isHoneypot(): bool
    {
        return trim((string) $this->input('company_website')) !== '';
    }
}
