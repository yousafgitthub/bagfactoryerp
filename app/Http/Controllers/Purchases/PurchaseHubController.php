<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        return Inertia::render('Purchases/Hub', [
            'pageTitle' => 'Purchases',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Purchases'],
            ],
            'links' => [
                [
                    'label' => 'Purchase orders',
                    'description' => 'Raise and confirm supplier purchase orders.',
                    'href' => route('purchases.orders.index'),
                ],
                [
                    'label' => 'Goods receipts (GRN)',
                    'description' => 'Receive stock against confirmed POs — stock posts here.',
                    'href' => route('purchases.goods-receipts.index'),
                ],
                [
                    'label' => 'Purchase invoices',
                    'description' => 'Match supplier bills to received quantities (no extra stock).',
                    'href' => route('purchases.invoices.index'),
                ],
                [
                    'label' => 'Supplier payments',
                    'description' => 'Allocate cash/bank payments to posted invoices.',
                    'href' => route('purchases.payments.index'),
                ],
                [
                    'label' => 'Purchase returns',
                    'description' => 'Return goods to supplier and adjust payable.',
                    'href' => route('purchases.returns.index'),
                ],
                [
                    'label' => 'Outstanding payables',
                    'description' => 'Posted invoices with unpaid balances.',
                    'href' => route('purchases.reports.outstanding'),
                ],
            ],
            'stockPolicy' => config('purchases.stock_recognition'),
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }
}