<?php

namespace App\Http\Controllers\Bag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bag\BagBatchRequest;
use App\Models\BagBillOfMaterial;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use App\Models\Machine;
use App\Models\Material;
use App\Models\ProductionHall;
use App\Models\Warehouse;
use App\Services\Bag\BagBatchException;
use App\Services\Bag\BagBatchPersistenceService;
use App\Services\Bag\BagBatchPostingService;
use App\Services\Bag\BagBatchWorkflowService;
use App\Services\Bag\BagConsumptionPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BagBatchController extends Controller
{
    public function __construct(
        private readonly BagBatchPersistenceService $persistence,
        private readonly BagBatchWorkflowService $workflow,
        private readonly BagBatchPostingService $posting,
        private readonly BagConsumptionPreviewService $preview,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $batches = BagProductionBatch::query()
            ->with(['machine:id,name', 'order.variant.product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->integer('machine_id'), fn ($q) => $q->where('machine_id', $request->integer('machine_id')))
            ->orderByDesc('production_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bag/Batches/Index', [
            'pageTitle' => 'Bag batches',
            'breadcrumbs' => $this->breadcrumbs('Batches'),
            'batches' => $batches,
            'filters' => $request->only(['status', 'machine_id']),
            'statuses' => config('bag.batch_statuses'),
            'machines' => Machine::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()?->can('shopping-bag-production.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageBagProduction');

        return Inertia::render('Bag/Batches/Form', [
            'pageTitle' => 'New bag batch',
            'breadcrumbs' => $this->breadcrumbs('New batch'),
            'batch' => null,
            'lookups' => $this->lookups(),
            'consumptionTypes' => config('bag.consumption_types'),
            'preselectedOrderId' => $request->integer('order_id') ?: null,
        ]);
    }

    public function store(BagBatchRequest $request): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $batch = $this->persistence->create($request->validated(), $request->user());
        } catch (BagBatchException $e) {
            return back()->withErrors(['batch' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bag.batches.show', $batch)->with('success', 'Batch draft created.');
    }

    public function show(BagProductionBatch $batch): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $batch->load([
            'machine',
            'productionHall',
            'materialConsumptions.material',
            'operators',
            'wastes',
            'inventoryLinks.inventoryTransaction',
            'order.product',
            'order.variant.saleableMaterial',
        ]);

        $postingPreview = $batch->status === 'completed'
            ? $this->preview->preview($batch)
            : null;

        return Inertia::render('Bag/Batches/Show', [
            'pageTitle' => $batch->batch_number,
            'breadcrumbs' => $this->breadcrumbs($batch->batch_number),
            'batch' => $batch,
            'statuses' => config('bag.batch_statuses'),
            'wasteTypes' => config('bag.waste_types'),
            'allowedActions' => $this->workflow->allowedActions($batch),
            'postingPreview' => $postingPreview,
            'canManage' => auth()->user()?->can('shopping-bag-production.manage') === true,
        ]);
    }

    public function edit(BagProductionBatch $batch): Response|RedirectResponse
    {
        $this->authorize('manageBagProduction');

        if (! $batch->isEditable()) {
            return redirect()->route('bag.batches.show', $batch)
                ->withErrors(['batch' => 'Only draft or submitted batches can be edited.']);
        }

        $batch->load(['materialConsumptions', 'operators', 'order']);

        return Inertia::render('Bag/Batches/Form', [
            'pageTitle' => 'Edit '.$batch->batch_number,
            'breadcrumbs' => $this->breadcrumbs('Edit batch'),
            'batch' => $batch,
            'lookups' => $this->lookups(),
            'consumptionTypes' => config('bag.consumption_types'),
            'preselectedOrderId' => $batch->bag_production_order_id,
        ]);
    }

    public function update(BagBatchRequest $request, BagProductionBatch $batch): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $this->persistence->update($batch, $request->validated());
        } catch (BagBatchException $e) {
            return back()->withErrors(['batch' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bag.batches.show', $batch)->with('success', 'Batch updated.');
    }

    public function workflow(Request $request, BagProductionBatch $batch, string $action): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $this->workflow->transition($batch, $action, $request->user());
        } catch (BagBatchException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Batch status updated.');
    }

    public function post(Request $request, BagProductionBatch $batch): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $this->posting->post($batch, $request->user());
        } catch (BagBatchException $e) {
            return back()->withErrors(['post' => $e->getMessage()]);
        }

        return back()->with('success', 'Batch posted to inventory.');
    }

    public function reverse(Request $request, BagProductionBatch $batch): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        try {
            $this->posting->reverse($batch, $request->user(), $request->string('notes')->toString());
        } catch (BagBatchException $e) {
            return back()->withErrors(['reverse' => $e->getMessage()]);
        }

        return back()->with('success', 'Posted batch reversed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'orders' => BagProductionOrder::query()
                ->whereIn('status', ['released', 'in_progress'])
                ->with(['product:id,code,name', 'variant:id,variant_code,name'])
                ->orderByDesc('id')
                ->get(),
            'halls' => ProductionHall::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'machines' => Machine::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'materials' => Material::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'inventory_bucket']),
            'boms' => BagBillOfMaterial::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'version']),
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