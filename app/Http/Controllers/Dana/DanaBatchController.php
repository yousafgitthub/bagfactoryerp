<?php

namespace App\Http\Controllers\Dana;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dana\DanaBatchRequest;
use App\Models\Color;
use App\Models\DanaBatch;
use App\Models\DanaProductionRecipe;
use App\Models\Machine;
use App\Models\Material;
use App\Models\ProductionHall;
use App\Models\Warehouse;
use App\Services\Dana\DanaBatchException;
use App\Services\Dana\DanaBatchPersistenceService;
use App\Services\Dana\DanaBatchPostingService;
use App\Services\Dana\DanaBatchWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DanaBatchController extends Controller
{
    public function __construct(
        private readonly DanaBatchPersistenceService $persistence,
        private readonly DanaBatchWorkflowService $workflow,
        private readonly DanaBatchPostingService $posting,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'dana-production');

        $query = DanaBatch::query()
            ->with(['machine:id,name', 'outputMaterial:id,name', 'outputVariant'])
            ->orderByDesc('production_date')
            ->orderByDesc('id');

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($machineId = $request->integer('machine_id')) {
            $query->where('machine_id', $machineId);
        }

        if ($batchNumber = $request->string('batch_number')->trim()->toString()) {
            $query->where('batch_number', 'like', '%'.$batchNumber.'%');
        }

        if ($colorId = $request->integer('color_id')) {
            $query->whereHas('outputVariant', fn ($q) => $q->where('color_id', $colorId));
        }

        if ($from = $request->date('date_from')) {
            $query->whereDate('production_date', '>=', $from);
        }

        if ($to = $request->date('date_to')) {
            $query->whereDate('production_date', '<=', $to);
        }

        $batches = $query->paginate(20)->withQueryString();

        return Inertia::render('Dana/Batches/Index', [
            'pageTitle' => 'Dana batches',
            'breadcrumbs' => $this->breadcrumbs('Batches'),
            'batches' => $batches,
            'filters' => $request->only(['status', 'machine_id', 'batch_number', 'color_id', 'date_from', 'date_to']),
            'statuses' => config('dana.statuses'),
            'machines' => Machine::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'colors' => Color::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()?->can('dana-production.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageDanaProduction');

        return Inertia::render('Dana/Batches/Form', [
            'pageTitle' => 'New Dana batch',
            'breadcrumbs' => $this->breadcrumbs('New batch'),
            'batch' => null,
            'lookups' => $this->lookups(),
            'inputTypes' => config('dana.input_types'),
        ]);
    }

    public function store(DanaBatchRequest $request): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        try {
            $batch = $this->persistence->create($request->validated(), $request->user());
        } catch (DanaBatchException $e) {
            return back()->withErrors(['batch' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('dana.batches.show', $batch)
            ->with('success', 'Dana batch draft created.');
    }

    public function show(DanaBatch $batch): Response
    {
        $this->authorize('viewModule', 'dana-production');

        $batch->load([
            'machine',
            'productionHall',
            'rawWarehouse',
            'finishedWarehouse',
            'wasteWarehouse',
            'outputMaterial.unit',
            'outputVariant.color',
            'materialInputs.material',
            'materialInputs.variant',
            'operators',
            'wastes',
            'inventoryLinks.inventoryTransaction',
            'recipe',
            'creator',
        ]);

        $previewCosts = null;
        if ($batch->materialInputs->isNotEmpty()) {
            $previewCosts = $this->posting->computeCosts($batch);
        }

        return Inertia::render('Dana/Batches/Show', [
            'pageTitle' => $batch->batch_number,
            'breadcrumbs' => $this->breadcrumbs($batch->batch_number),
            'batch' => $batch,
            'statuses' => config('dana.statuses'),
            'wasteTypes' => config('dana.waste_types'),
            'allowedActions' => $this->workflow->allowedActions($batch),
            'previewCosts' => $previewCosts,
            'canManage' => auth()->user()?->can('dana-production.manage') === true,
        ]);
    }

    public function edit(DanaBatch $batch): Response|RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        if (! $batch->isEditable()) {
            return redirect()
                ->route('dana.batches.show', $batch)
                ->withErrors(['batch' => 'Only draft or submitted batches can be edited.']);
        }

        $batch->load(['materialInputs', 'operators']);

        return Inertia::render('Dana/Batches/Form', [
            'pageTitle' => 'Edit '.$batch->batch_number,
            'breadcrumbs' => $this->breadcrumbs('Edit batch'),
            'batch' => $batch,
            'lookups' => $this->lookups(),
            'inputTypes' => config('dana.input_types'),
        ]);
    }

    public function update(DanaBatchRequest $request, DanaBatch $batch): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        try {
            $this->persistence->update($batch, $request->validated());
        } catch (DanaBatchException $e) {
            return back()->withErrors(['batch' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('dana.batches.show', $batch)
            ->with('success', 'Batch updated.');
    }

    public function workflow(Request $request, DanaBatch $batch, string $action): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        try {
            $this->workflow->transition($batch, $action, $request->user());
        } catch (DanaBatchException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Batch status updated.');
    }

    public function post(Request $request, DanaBatch $batch): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        try {
            $this->posting->post($batch, $request->user());
        } catch (DanaBatchException $e) {
            return back()->withErrors(['post' => $e->getMessage()]);
        }

        return back()->with('success', 'Batch posted to inventory.');
    }

    public function reverse(Request $request, DanaBatch $batch): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        try {
            $this->posting->reverse($batch, $request->user(), $request->string('notes')->toString());
        } catch (DanaBatchException $e) {
            return back()->withErrors(['reverse' => $e->getMessage()]);
        }

        return back()->with('success', 'Posted batch reversed.');
    }

    public function print(DanaBatch $batch): Response
    {
        $this->authorize('viewModule', 'dana-production');

        $batch->load([
            'machine',
            'productionHall',
            'materialInputs.material',
            'operators',
            'outputMaterial',
        ]);

        return Inertia::render('Dana/Batches/Print', [
            'pageTitle' => 'Print '.$batch->batch_number,
            'batch' => $batch,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'halls' => ProductionHall::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'machines' => Machine::query()->where('status', 'active')->with('productionHall:id,name')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'materials' => Material::query()->where('status', 'active')->with('variants.color')->orderBy('name')->get(),
            'recipes' => DanaProductionRecipe::query()->where('status', 'active')->with('outputMaterial:id,name')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Dana Production', 'href' => route('erp.dana-production')],
            ['label' => $leaf],
        ];
    }
}