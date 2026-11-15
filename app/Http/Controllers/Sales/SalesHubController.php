<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'sales');

        return Inertia::render('Sales/Hub', [
            'pageTitle' => 'Sales',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Sales'],
            ],
            'costingMethod' => config('sales.costing_method'),
            'links' => [
                [
                    'href' => route('sales.orders.index'),
                    'label' => 'Sales orders',
                    'description' => 'Customer orders before invoicing.',
                ],
                [
                    'href' => route('sales.invoices.index'),
                    'label' => 'Sales invoices',
                    'description' => 'Post invoices — deducts finished stock and records COGS (WAC).',
                ],
                [
                    'href' => route('sales.receipts.index'),
                    'label' => 'Customer receipts',
                    'description' => 'Cash and bank collections against invoices.',
                ],
                [
                    'href' => route('sales.returns.index'),
                    'label' => 'Sales returns',
                    'description' => 'Returns with saleable vs damaged disposition.',
                ],
                [
                    'href' => route('sales.reports.outstanding'),
                    'label' => 'Outstanding receivables',
                    'description' => 'Open invoices and customer balances.',
                ],
                [
                    'href' => route('sales.reports.summary'),
                    'label' => 'Sales summary',
                    'description' => 'Posted revenue, tax and COGS.',
                ],
            ],
            'canManage' => $request->user()?->can('sales.manage') === true,
        ]);
    }
}