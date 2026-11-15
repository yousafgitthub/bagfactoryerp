<?php

namespace App\Http\Controllers\Bag;

use App\Http\Controllers\Controller;
use App\Models\BagBillOfMaterial;
use App\Models\BagBomLine;
use App\Models\BagProduct;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BagBomController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $boms = BagBillOfMaterial::query()
            ->with(['product:id,code,name', 'variant:id,variant_code,name'])
            ->orderByDesc('effective_from')
            ->orderByDesc('version')
            ->paginate(20);

        return Inertia::render('Bag/Bom/Index', [
            'pageTitle' => 'Bill of materials',
            'breadcrumbs' => $this->breadcrumbs('BOM'),
            'boms' => $boms,
            'canManage' => auth()->user()?->can('shopping-bag-production.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageBagProduction');

        return Inertia::render('Bag/Bom/Form', [
            'pageTitle' => 'New BOM',
            'breadcrumbs' => $this->breadcrumbs('New BOM'),
            'bom' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        $data = $this->validateBom($request);
        DB::transaction(fn () => $this->persistBom(null, $data));

        return redirect()->route('bag.bom.index')->with('success', 'BOM saved.');
    }

    public function edit(BagBillOfMaterial $bom): Response
    {
        $this->authorize('manageBagProduction');

        $bom->load(['lines.material']);

        return Inertia::render('Bag/Bom/Form', [
            'pageTitle' => 'Edit '.$bom->code,
            'breadcrumbs' => $this->breadcrumbs('Edit BOM'),
            'bom' => $bom,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(Request $request, BagBillOfMaterial $bom): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        $data = $this->validateBom($request, $bom->id);
        DB::transaction(fn () => $this->persistBom($bom, $data));

        return redirect()->route('bag.bom.index')->with('success', 'BOM updated.');
    }

    public function newVersion(BagBillOfMaterial $bom): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        $nextVersion = (int) BagBillOfMaterial::query()
            ->where('bag_product_id', $bom->bag_product_id)
            ->where('bag_product_variant_id', $bom->bag_product_variant_id)
            ->max('version') + 1;

        $clone = DB::transaction(function () use ($bom, $nextVersion) {
            $bom->load('lines');

            $new = BagBillOfMaterial::query()->create([
                'code' => $bom->code,
                'name' => $bom->name.' v'.$nextVersion,
                'bag_product_id' => $bom->bag_product_id,
                'bag_product_variant_id' => $bom->bag_product_variant_id,
                'version' => $nextVersion,
                'effective_from' => now()->toDateString(),
                'status' => 'active',
                'notes' => $bom->notes,
            ]);

            foreach ($bom->lines as $line) {
                BagBomLine::query()->create([
                    'bag_bill_of_material_id' => $new->id,
                    'material_id' => $line->material_id,
                    'material_variant_id' => $line->material_variant_id,
                    'quantity_per_bag' => $line->quantity_per_bag,
                    'wastage_percent' => $line->wastage_percent,
                    'line_order' => $line->line_order,
                ]);
            }

            $bom->update(['status' => 'superseded', 'effective_to' => now()->toDateString()]);

            return $new;
        });

        return redirect()->route('bag.bom.edit', $clone)->with('success', 'New BOM version created.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistBom(?BagBillOfMaterial $bom, array $data): void
    {
        $header = $data['bom'];
        $lines = $data['lines'] ?? [];

        if ($bom) {
            $bom->update($header);
        } else {
            $version = (int) BagBillOfMaterial::query()
                ->where('bag_product_id', $header['bag_product_id'])
                ->where('bag_product_variant_id', $header['bag_product_variant_id'])
                ->max('version') + 1;

            $header['version'] = $version;
            $bom = BagBillOfMaterial::query()->create($header);
        }

        BagBomLine::query()->where('bag_bill_of_material_id', $bom->id)->delete();

        $order = 1;
        foreach ($lines as $line) {
            if (empty($line['material_id']) || (float) ($line['quantity_per_bag'] ?? 0) <= 0) {
                continue;
            }

            BagBomLine::query()->create([
                'bag_bill_of_material_id' => $bom->id,
                'material_id' => $line['material_id'],
                'material_variant_id' => $line['material_variant_id'] ?? null,
                'quantity_per_bag' => $line['quantity_per_bag'],
                'wastage_percent' => $line['wastage_percent'] ?? 0,
                'line_order' => $order++,
            ]);
        }
    }

    /**
     * @return array{bom: array<string, mixed>, lines: list<array<string, mixed>>}
     */
    private function validateBom(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'bag_product_id' => ['required', 'exists:bag_products,id'],
            'bag_product_variant_id' => ['required', 'exists:bag_product_variants,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', Rule::in(['active', 'superseded', 'inactive'])],
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.material_id' => ['required_with:lines', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'lines.*.quantity_per_bag' => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.wastage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return [
            'bom' => collect($validated)->except('lines')->all(),
            'lines' => $validated['lines'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'products' => BagProduct::query()->where('status', 'active')->with('variants')->orderBy('name')->get(),
            'materials' => Material::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'inventory_bucket']),
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