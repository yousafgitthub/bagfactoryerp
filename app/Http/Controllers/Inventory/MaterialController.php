<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\MaterialRequest;
use App\Models\Color;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialVariant;
use App\Models\UnitOfMeasurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'inventory');

        $query = Material::query()->with(['category', 'unit']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        $materials = $query->orderBy('code')->paginate(15)->withQueryString();

        return Inertia::render('Inventory/Materials/Index', [
            'pageTitle' => 'Materials',
            'breadcrumbs' => $this->breadcrumbs('Materials'),
            'materials' => $materials,
            'filters' => ['search' => $search, 'status' => $status],
            'canManage' => $request->user()?->can('inventory.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageInventory');

        return Inertia::render('Inventory/Materials/Form', [
            'pageTitle' => 'New material',
            'breadcrumbs' => $this->breadcrumbs('New material'),
            'material' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(MaterialRequest $request): RedirectResponse
    {
        $this->authorize('manageInventory');

        Material::query()->create($request->validated());

        return redirect()->route('inventory.materials.index')->with('success', 'Material created.');
    }

    public function show(Request $request, Material $material): Response
    {
        $this->authorize('viewModule', 'inventory');

        $material->load(['category', 'unit', 'variants.color', 'balances.warehouse']);

        return Inertia::render('Inventory/Materials/Show', [
            'pageTitle' => $material->name,
            'breadcrumbs' => $this->breadcrumbs($material->name),
            'material' => $material,
            'canManage' => $request->user()?->can('inventory.manage') === true,
        ]);
    }

    public function edit(Material $material): Response
    {
        $this->authorize('manageInventory');

        $material->load('variants');

        return Inertia::render('Inventory/Materials/Form', [
            'pageTitle' => 'Edit material',
            'breadcrumbs' => $this->breadcrumbs('Edit '.$material->name),
            'material' => $material,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(MaterialRequest $request, Material $material): RedirectResponse
    {
        $this->authorize('manageInventory');

        $material->update($request->validated());

        return redirect()->route('inventory.materials.show', $material)->with('success', 'Material updated.');
    }

    public function storeVariant(Request $request, Material $material): RedirectResponse
    {
        $this->authorize('manageInventory');

        $validated = $request->validate([
            'variant_code' => 'required|string|max:32',
            'name' => 'required|string|max:255',
            'color_id' => 'nullable|exists:colors,id',
            'status' => 'required|in:active,inactive',
        ]);

        $material->variants()->create($validated);

        return back()->with('success', 'Variant added.');
    }

    public function deactivate(Material $material): RedirectResponse
    {
        $this->authorize('manageInventory');

        $material->update(['status' => 'inactive']);

        return redirect()->route('inventory.materials.index')->with('success', 'Material deactivated.');
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Inventory', 'href' => route('erp.inventory')],
            ['label' => $leaf],
        ];
    }

  /**
   * @return array<string, mixed>
   */
    private function lookups(): array
    {
        return [
            'categories' => MaterialCategory::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']),
            'units' => UnitOfMeasurement::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'symbol']),
            'colors' => Color::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'hex_color']),
            'buckets' => config('inventory.inventory_buckets'),
        ];
    }
}