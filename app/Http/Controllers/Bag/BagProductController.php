<?php

namespace App\Http\Controllers\Bag;

use App\Http\Controllers\Controller;
use App\Models\BagProduct;
use App\Models\BagProductVariant;
use App\Models\Color;
use App\Models\Material;
use App\Models\UnitOfMeasurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BagProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'shopping-bag-production');

        $products = BagProduct::query()
            ->with(['color:id,name', 'unit:id,symbol', 'variants'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bag/Products/Index', [
            'pageTitle' => 'Bag products',
            'breadcrumbs' => $this->breadcrumbs('Products'),
            'products' => $products,
            'canManage' => $request->user()?->can('shopping-bag-production.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageBagProduction');

        return Inertia::render('Bag/Products/Form', [
            'pageTitle' => 'New product',
            'breadcrumbs' => $this->breadcrumbs('New product'),
            'product' => null,
            'lookups' => $this->lookups(),
            'bagTypes' => config('bag.bag_types'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        $data = $this->validateProduct($request);

        $product = DB::transaction(function () use ($data) {
            $product = BagProduct::query()->create($data['product']);
            $this->syncVariants($product, $data['variants'] ?? []);

            return $product;
        });

        return redirect()->route('bag.products.index')->with('success', 'Product saved.');
    }

    public function edit(BagProduct $product): Response
    {
        $this->authorize('manageBagProduction');

        $product->load('variants.saleableMaterial', 'variants.rejectedMaterial');

        return Inertia::render('Bag/Products/Form', [
            'pageTitle' => 'Edit '.$product->code,
            'breadcrumbs' => $this->breadcrumbs('Edit product'),
            'product' => $product,
            'lookups' => $this->lookups(),
            'bagTypes' => config('bag.bag_types'),
        ]);
    }

    public function update(Request $request, BagProduct $product): RedirectResponse
    {
        $this->authorize('manageBagProduction');

        $data = $this->validateProduct($request, $product->id);

        DB::transaction(function () use ($product, $data) {
            $product->update($data['product']);
            $this->syncVariants($product, $data['variants'] ?? []);
        });

        return redirect()->route('bag.products.index')->with('success', 'Product updated.');
    }

    /**
     * @return array{product: array<string, mixed>, variants: list<array<string, mixed>>}
     */
    private function validateProduct(Request $request, ?int $productId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('bag_products', 'code')->ignore($productId)],
            'name' => ['required', 'string', 'max:120'],
            'bag_type' => ['nullable', Rule::in(array_keys(config('bag.bag_types', [])))],
            'size_label' => ['nullable', 'string', 'max:64'],
            'width_mm' => ['nullable', 'numeric', 'min:0'],
            'length_mm' => ['nullable', 'numeric', 'min:0'],
            'thickness_micron' => ['nullable', 'numeric', 'min:0'],
            'nominal_weight_per_bag_g' => ['nullable', 'numeric', 'min:0'],
            'color_id' => ['nullable', 'exists:colors,id'],
            'unit_of_measurement_id' => ['required', 'exists:units_of_measurement,id'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.variant_code' => ['required_with:variants', 'string', 'max:32'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:120'],
            'variants.*.color_id' => ['nullable', 'exists:colors,id'],
            'variants.*.weight_per_bag_g' => ['nullable', 'numeric', 'min:0'],
            'variants.*.saleable_material_id' => ['nullable', 'exists:materials,id'],
            'variants.*.rejected_material_id' => ['nullable', 'exists:materials,id'],
            'variants.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $productFields = collect($validated)->except('variants')->all();

        return ['product' => $productFields, 'variants' => $validated['variants'] ?? []];
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(BagProduct $product, array $variants): void
    {
        $keep = [];
        foreach ($variants as $row) {
            if (empty($row['variant_code'])) {
                continue;
            }

            $payload = [
                'variant_code' => $row['variant_code'],
                'name' => $row['name'],
                'color_id' => $row['color_id'] ?? null,
                'weight_per_bag_g' => $row['weight_per_bag_g'] ?? null,
                'saleable_material_id' => $row['saleable_material_id'] ?? null,
                'rejected_material_id' => $row['rejected_material_id'] ?? null,
                'selling_price' => $row['selling_price'] ?? null,
                'status' => $row['status'] ?? 'active',
            ];

            if (! empty($row['id'])) {
                $variant = BagProductVariant::query()->where('bag_product_id', $product->id)->whereKey($row['id'])->first();
                $variant?->update($payload);
                $keep[] = (int) $row['id'];
            } else {
                $created = $product->variants()->create($payload);
                $keep[] = $created->id;
            }
        }

        if ($keep) {
            BagProductVariant::query()
                ->where('bag_product_id', $product->id)
                ->whereNotIn('id', $keep)
                ->update(['status' => 'inactive']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'colors' => Color::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'units' => UnitOfMeasurement::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name', 'symbol']),
            'materials' => Material::query()
                ->where('status', 'active')
                ->whereIn('inventory_bucket', ['finished_bag', 'rejected_bag', 'other'])
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'inventory_bucket']),
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