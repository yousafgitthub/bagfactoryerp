<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreEnquiryRequest;
use App\Services\Website\EnquiryRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PublicEnquiryController extends Controller
{
    public function __construct(private readonly EnquiryRecorder $enquiries) {}

    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        if ($request->isHoneypot()) {
            return back()->with('status', 'Thank you. Your message has been received.');
        }

        $token = (string) $request->input('idempotency_token');

        if (! $this->enquiries->acceptsToken($token)) {
            throw ValidationException::withMessages([
                'idempotency_token' => 'Open the form again, then submit it once.',
            ]);
        }

        $result = $this->enquiries->record(
            $request->enquiryType(),
            $request->validated(),
            $request->file('artwork')
        );

        $reference = $result['enquiry']->reference();
        $message = $result['duplicate']
            ? "We already have this enquiry ({$reference}). The factory will follow up on that one."
            : "Thank you. Your enquiry {$reference} is with the factory.";

        return back()->with('status', $message);
    }
}
