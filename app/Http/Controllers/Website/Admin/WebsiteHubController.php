<?php

namespace App\Http\Controllers\Website\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteEnquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'website');

        $canManage = $request->user()?->can('website.manage') === true;

        return Inertia::render('Website/Admin/Hub', [
            'pageTitle' => 'Website',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Website'],
            ],
            'newEnquiries' => WebsiteEnquiry::query()->where('status', 'new')->count(),
            'canManage' => $canManage,
            'links' => array_values(array_filter([
                [
                    'href' => route('website.admin.enquiries.index'),
                    'label' => 'Enquiries',
                    'description' => 'Quotes, custom orders, and contact messages for sales follow-up.',
                ],
                [
                    'href' => route('website.admin.listings.index'),
                    'label' => 'Public listings',
                    'description' => 'Choose which bag products and materials appear on the public site.',
                ],
                $canManage ? [
                    'href' => route('website.admin.categories.edit'),
                    'label' => 'Categories',
                    'description' => 'Edit the sample catalog descriptions shown to customers.',
                ] : null,
                $canManage ? [
                    'href' => route('website.admin.gallery.index'),
                    'label' => 'Gallery',
                    'description' => 'Upload images for the public gallery.',
                ] : null,
                $canManage ? [
                    'href' => route('website.admin.testimonials.index'),
                    'label' => 'Testimonials',
                    'description' => 'Publish a testimonial only when you have a real one.',
                ] : null,
                [
                    'href' => route('website.admin.settings.edit'),
                    'label' => 'Site settings',
                    'description' => 'Company name and contact details. Empty contact fields stay off the public site.',
                ],
                [
                    'href' => route('website.public.home'),
                    'label' => 'View public site',
                    'description' => 'Open the customer-facing website.',
                ],
            ])),
        ]);
    }

}
