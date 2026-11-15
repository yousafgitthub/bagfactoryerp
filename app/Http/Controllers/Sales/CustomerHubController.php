<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'customers');

        return Inertia::render('Sales/Customers/Hub', [
            'pageTitle' => 'Customers',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Customers'],
            ],
            'links' => [
                [
                    'href' => route('sales.customers.index'),
                    'label' => 'Customer directory',
                    'description' => 'Codes, contacts and status.',
                ],
            ],
            'canManage' => $request->user()?->can('customers.manage') === true,
        ]);
    }
}