<?php

namespace App\Http\Controllers\Dana;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dana\DanaRecipeRequest;
use App\Models\DanaProductionRecipe;
use App\Models\DanaProductionRecipeLine;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DanaRecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'dana-production');

        $recipes = DanaProductionRecipe::query()
            ->with(['outputMaterial:id,name'])
            ->withCount('lines')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Dana/Recipes/Index', [
            'pageTitle' => 'Dana recipes',
            'breadcrumbs' => $this->breadcrumbs('Recipes'),
            'recipes' => $recipes,
            'canManage' => $request->user()?->can('dana-production.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageDanaProduction');

        return Inertia::render('Dana/Recipes/Form', [
            'pageTitle' => 'New recipe',
            'breadcrumbs' => $this->breadcrumbs('New recipe'),
            'recipe' => null,
            'lookups' => $this->lookups(),
            'inputTypes' => config('dana.input_types'),
        ]);
    }

    public function store(DanaRecipeRequest $request): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        $data = $request->validated();

        $recipe = DB::transaction(function () use ($data) {
            $recipe = DanaProductionRecipe::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'output_material_id' => $data['output_material_id'],
                'output_material_variant_id' => $data['output_material_variant_id'] ?? null,
                'max_process_loss_percent' => $data['max_process_loss_percent'] ?? config('dana.default_max_process_loss_percent'),
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->syncLines($recipe, $data['lines'] ?? []);

            return $recipe;
        });

        return redirect()
            ->route('dana.recipes.index')
            ->with('success', 'Recipe saved.');
    }

    public function edit(DanaProductionRecipe $recipe): Response
    {
        $this->authorize('manageDanaProduction');

        $recipe->load('lines.material');

        return Inertia::render('Dana/Recipes/Form', [
            'pageTitle' => 'Edit recipe',
            'breadcrumbs' => $this->breadcrumbs('Edit recipe'),
            'recipe' => $recipe,
            'lookups' => $this->lookups(),
            'inputTypes' => config('dana.input_types'),
        ]);
    }

    public function update(DanaRecipeRequest $request, DanaProductionRecipe $recipe): RedirectResponse
    {
        $this->authorize('manageDanaProduction');

        $data = $request->validated();

        DB::transaction(function () use ($recipe, $data) {
            $recipe->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'output_material_id' => $data['output_material_id'],
                'output_material_variant_id' => $data['output_material_variant_id'] ?? null,
                'max_process_loss_percent' => $data['max_process_loss_percent'] ?? $recipe->max_process_loss_percent,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? $recipe->status,
            ]);

            $this->syncLines($recipe, $data['lines'] ?? []);
        });

        return redirect()
            ->route('dana.recipes.index')
            ->with('success', 'Recipe updated.');
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(DanaProductionRecipe $recipe, array $lines): void
    {
        DanaProductionRecipeLine::query()->where('dana_production_recipe_id', $recipe->id)->delete();

        $order = 1;
        foreach ($lines as $row) {
            if (empty($row['material_id'])) {
                continue;
            }

            DanaProductionRecipeLine::query()->create([
                'dana_production_recipe_id' => $recipe->id,
                'material_id' => $row['material_id'],
                'material_variant_id' => $row['material_variant_id'] ?? null,
                'input_type' => $row['input_type'] ?? 'raw',
                'quantity_per_output_kg' => $row['quantity_per_output_kg'],
                'line_order' => $order++,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'materials' => Material::query()->where('status', 'active')->with('variants')->orderBy('name')->get(),
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