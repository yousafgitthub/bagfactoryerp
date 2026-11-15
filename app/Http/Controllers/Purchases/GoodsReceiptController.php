<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Services\Purchases\GoodsReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly GoodsReceiptService $receipts,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'purchases');

        $receipts = GoodsReceipt::query()
            ->with(['supplier', 'purchaseOrder', 'warehouse'])
            ->orderByDesc('receipt_date')
            ->paginate(15);

        return Inertia::render('Purchases/GoodsReceipts/Index', [
            'pageTitle' => 'Goods receipts',
            'breadcrumbs' => $this->crumbs('Goods receipts'),
            'receipts' => $receipts,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function show(Request $request, GoodsReceipt $goodsReceipt): Response
    {
        $this->authorize('viewModule', 'purchases');

        $goodsReceipt->load(['supplier', 'purchaseOrder', 'warehouse', 'lines.material', 'inventoryTransaction']);

        return Inertia::render('Purchases/GoodsReceipts/Show', [
            'pageTitle' => $goodsReceipt->receipt_number,
            'breadcrumbs' => $this->crumbs($goodsReceipt->receipt_number),
            'receipt' => $goodsReceipt,
            'canManage' => $request->user()?->can('purchases.manage') === true,
        ]);
    }

    public function post(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('managePurchases');

        $this->receipts->post($goodsReceipt, $request->user());

        return back()->with('success', 'Goods receipt posted. Stock updated at GRN.');
    }

    /** @return list<array{label: string, href?: string}> */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Purchases', 'href' => route('erp.purchases')],
            ['label' => $tail],
        ];
    }
}