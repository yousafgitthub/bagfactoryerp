<?php

namespace App\Http\Controllers\Bag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bag\BagOrderRequest;
use App\Models\BagBillOfMaterial;
use App\Models\BagProduct;
use App\Models\BagProductionOrder;
use App\Models\Color;
use App\Models\Machine;
use App\Models\ProductionHall;
use App\Models\Warehouse;
use App\Services\Bag\BagBatchException;
use App\Services\Bag\BagOrderPersistenceService;
use App\Services\Bag\BagOrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BagOrderController extends Controller
{
    public function __construct(
        private readonly BagOrderPersistenceService $persistence,
        private readonly BagOrderWorkflowService $workflow,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $orders = BagProductionOrder::query()
            ->with(['product:id,code,name', 'variant:id,variant_code,name', 'machine:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bag/Orders/Index', [
            'pageTitle' => 'Production orders',
            'breadcrumbs' => $this->breadcrumbs('Orders'),
            'orders' => $orders,
            'filters' => $request->only(['status']),
            'statuses' => config('bag.order_statuses'),
            'canManage' => $request->user()?->can('shopping-bag-production.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageBagProduction');

        return Inertia::render('Bag/Orders/Form', [
            'pageTitle' => 'New production order',
            'breadcrumbs' => $this->breadcrumbs('New order'),
            'order' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(BagOrderRequest $request): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        $order = $this->persistence->create($request->validated(), $request->user());

        return redirect()->route('bag.orders.show', $order)->with('success', 'Order created.');
    }

    public function show(BagProductionOrder $order): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $order->load([
            'product',
            'variant',
            'billOfMaterial',
            'machine',
            'productionHall',
            'assignments',
            'batches',
        ]);

        return Inertia::render('Bag/Orders/Show', [
            'pageTitle' => $order->order_number,
            'breadcrumbs' => $this->breadcrumbs($order->order_number),
            'order' => $order,
            'statuses' => config('bag.order_statuses'),
            'allowedActions' => $this->workflow->allowedActions($order),
            'canManage' => auth()->user()?->can('shopping-bag-production.manage') === true,
        ]);
    }

    public function edit(BagProductionOrder $order): Response|RedirectResponse
    {
        $this->authorize('manageBagProduction');

        if (! $order->isEditable()) {
            return redirect()->route('bag.orders.show', $order)
                ->withErrors(['order' => 'Only draft orders can be edited.']);
        }

        $order->load('assignments');

        return Inertia::render('Bag/Orders/Form', [
            'pageTitle' => 'Edit '.$order->order_number,
            'breadcrumbs' => $this->breadcrumbs('Edit order'),
            'order' => $order,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(BagOrderRequest $request, BagProductionOrder $order): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $this->persistence->update($order, $request->validated());
        } catch (BagBatchException $e) {
            return back()->withErrors(['order' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bag.orders.show', $order)->with('success', 'Order updated.');
    }

    public function workflow(Request $request, BagProductionOrder $order, string $action): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $this->workflow->transition($order, $action, $request->user());
        } catch (BagBatchException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Order status updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'products' => BagProduct::query()->where('status', 'active')->with('variants')->orderBy('name')->get(),
            'boms' => BagBillOfMaterial::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'bag_product_id', 'bag_product_variant_id', 'version']),
            'halls' => ProductionHall::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'machines' => Machine::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'colors' => Color::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
        ];
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Bag Production', 'href' => route('erp.shopping-bag-production')],
            ['label' => $leaf],
        ];
    }
}