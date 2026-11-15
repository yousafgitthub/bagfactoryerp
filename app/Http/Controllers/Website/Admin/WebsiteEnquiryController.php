<?php

namespace App\Http\Controllers\Website\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WebsiteEnquiry;
use App\Support\WebsiteUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteEnquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'website');

        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();
        $types = ['quote', 'custom_order', 'contact'];
        $statuses = array_keys(config('website.enquiry_statuses', []));

        $enquiries = WebsiteEnquiry::query()
            ->when(in_array($type, $types, true), fn ($query) => $query->where('type', $type))
            ->when(in_array($status, $statuses, true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (WebsiteEnquiry $enquiry) => [
                'id' => $enquiry->id,
                'reference' => $enquiry->reference(),
                'type' => $enquiry->type,
                'status' => $enquiry->status,
                'name' => $enquiry->name,
                'email' => $enquiry->email,
                'phone' => $enquiry->phone,
                'productLabel' => $enquiry->product_label,
                'createdAt' => optional($enquiry->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'href' => route('website.admin.enquiries.show', $enquiry),
            ]);

        return Inertia::render('Website/Admin/Enquiries/Index', [
            'pageTitle' => 'Website enquiries',
            'breadcrumbs' => $this->crumbs('Enquiries'),
            'enquiries' => $enquiries,
            'filters' => [
                'type' => in_array($type, $types, true) ? $type : '',
                'status' => in_array($status, $statuses, true) ? $status : '',
            ],
            'statuses' => config('website.enquiry_statuses', []),
        ]);
    }

    public function show(Request $request, WebsiteEnquiry $enquiry): Response
    {
        $this->authorize('viewModule', 'website');

        $search = trim($request->string('customer_q')->toString());
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

        $customers = Customer::query()
            ->select(['id', 'code', 'name'])
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get();

        if ($enquiry->customer_id && $customers->doesntContain('id', $enquiry->customer_id)) {
            $linked = Customer::query()->select(['id', 'code', 'name'])->find($enquiry->customer_id);
            if ($linked) {
                $customers->prepend($linked);
            }
        }

        return Inertia::render('Website/Admin/Enquiries/Show', [
            'pageTitle' => $enquiry->reference(),
            'breadcrumbs' => $this->crumbs($enquiry->reference()),
            'enquiry' => [
                'id' => $enquiry->id,
                'reference' => $enquiry->reference(),
                'type' => $enquiry->type,
                'status' => $enquiry->status,
                'name' => $enquiry->name,
                'email' => $enquiry->email,
                'phone' => $enquiry->phone,
                'company' => $enquiry->company,
                'productLabel' => $enquiry->product_label,
                'message' => $enquiry->message,
                'details' => $enquiry->details ?? [],
                'internalNotes' => $enquiry->internal_notes,
                'customerId' => $enquiry->customer_id,
                'createdAt' => optional($enquiry->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'artworkName' => $enquiry->artwork_original_name,
                'artworkHref' => $enquiry->artwork_path
                    ? route('website.admin.enquiries.artwork', $enquiry)
                    : null,
            ],
            'customers' => $customers,
            'customerQuery' => $search,
            'statuses' => config('website.enquiry_statuses', []),
        ]);
    }

    public function update(Request $request, WebsiteEnquiry $enquiry): RedirectResponse
    {
        $this->authorize('viewModule', 'website');

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('website.enquiry_statuses', [])))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $enquiry->update([
            'status' => $data['status'],
            'internal_notes' => $this->clean($data['internal_notes'] ?? null),
            'customer_id' => $data['customer_id'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Enquiry updated.');
    }

    public function artwork(WebsiteEnquiry $enquiry): StreamedResponse
    {
        $this->authorize('viewModule', 'website');
        abort_unless(is_string($enquiry->artwork_path) && $enquiry->artwork_path !== '', 404);

        return WebsiteUpload::download(
            $enquiry->artwork_path,
            $enquiry->artwork_original_name ?: 'artwork'
        );
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
