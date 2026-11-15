<?php

namespace App\Services\Dana;

use App\Models\DanaBatch;
use App\Models\DanaBatchMaterialInput;
use App\Models\DanaBatchOperator;
use App\Models\DanaProductionRecipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DanaBatchPersistenceService
{
    public function __construct(
        private readonly DanaBatchNumberGenerator $numbers,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): DanaBatch
    {
        return DB::transaction(function () use ($data, $actor) {
            $batch = DanaBatch::query()->create([
                'batch_number' => $this->numbers->next(),
                'production_date' => $data['production_date'],
                'status' => 'draft',
                'machine_id' => $data['machine_id'],
                'production_hall_id' => $data['production_hall_id'],
                'raw_material_warehouse_id' => $data['raw_material_warehouse_id'],
                'finished_dana_warehouse_id' => $data['finished_dana_warehouse_id'],
                'waste_warehouse_id' => $data['waste_warehouse_id'] ?? null,
                'output_material_id' => $data['output_material_id'],
                'output_material_variant_id' => $data['output_material_variant_id'] ?? null,
                'dana_production_recipe_id' => $data['dana_production_recipe_id'] ?? null,
                'output_kg' => $data['output_kg'] ?? 0,
                'reusable_waste_kg' => $data['reusable_waste_kg'] ?? 0,
                'scrap_waste_kg' => $data['scrap_waste_kg'] ?? 0,
                'process_loss_kg' => $data['process_loss_kg'] ?? 0,
                'electricity_cost' => $data['electricity_cost'] ?? 0,
                'other_overhead_cost' => $data['other_overhead_cost'] ?? 0,
                'reusable_waste_material_id' => $data['reusable_waste_material_id'] ?? null,
                'scrap_waste_material_id' => $data['scrap_waste_material_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncInputs($batch, $data['inputs'] ?? []);
            $this->syncOperators($batch, $data['operators'] ?? []);
            $this->recalculateInputTotal($batch);

            if (! empty($data['dana_production_recipe_id']) && empty($data['inputs'])) {
                $this->applyRecipeLines($batch, (int) $data['dana_production_recipe_id'], (float) $batch->output_kg);
                $this->recalculateInputTotal($batch);
            }

            return $batch->fresh(['materialInputs', 'operators']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DanaBatch $batch, array $data): DanaBatch
    {
        if (! $batch->isEditable()) {
            throw new DanaBatchException('Batch cannot be edited in its current status.');
        }

        return DB::transaction(function () use ($batch, $data) {
            $batch->update([
                'production_date' => $data['production_date'],
                'machine_id' => $data['machine_id'],
                'production_hall_id' => $data['production_hall_id'],
                'raw_material_warehouse_id' => $data['raw_material_warehouse_id'],
                'finished_dana_warehouse_id' => $data['finished_dana_warehouse_id'],
                'waste_warehouse_id' => $data['waste_warehouse_id'] ?? null,
                'output_material_id' => $data['output_material_id'],
                'output_material_variant_id' => $data['output_material_variant_id'] ?? null,
                'dana_production_recipe_id' => $data['dana_production_recipe_id'] ?? null,
                'output_kg' => $data['output_kg'] ?? 0,
                'reusable_waste_kg' => $data['reusable_waste_kg'] ?? 0,
                'scrap_waste_kg' => $data['scrap_waste_kg'] ?? 0,
                'process_loss_kg' => $data['process_loss_kg'] ?? 0,
                'electricity_cost' => $data['electricity_cost'] ?? 0,
                'other_overhead_cost' => $data['other_overhead_cost'] ?? 0,
                'reusable_waste_material_id' => $data['reusable_waste_material_id'] ?? null,
                'scrap_waste_material_id' => $data['scrap_waste_material_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncInputs($batch, $data['inputs'] ?? []);
            $this->syncOperators($batch, $data['operators'] ?? []);
            $this->recalculateInputTotal($batch);

            return $batch->fresh(['materialInputs', 'operators']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $inputs
     */
    private function syncInputs(DanaBatch $batch, array $inputs): void
    {
        DanaBatchMaterialInput::query()->where('dana_batch_id', $batch->id)->delete();

        $order = 1;
        foreach ($inputs as $row) {
            if (empty($row['material_id']) || (float) ($row['quantity_kg'] ?? 0) <= 0) {
                continue;
            }

            DanaBatchMaterialInput::query()->create([
                'dana_batch_id' => $batch->id,
                'material_id' => $row['material_id'],
                'material_variant_id' => $row['material_variant_id'] ?? null,
                'input_type' => $row['input_type'] ?? 'raw',
                'quantity_kg' => $row['quantity_kg'],
                'line_order' => $order++,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $operators
     */
    private function syncOperators(DanaBatch $batch, array $operators): void
    {
        DanaBatchOperator::query()->where('dana_batch_id', $batch->id)->delete();

        $order = 1;
        foreach ($operators as $row) {
            if (empty($row['operator_name'])) {
                continue;
            }

            DanaBatchOperator::query()->create([
                'dana_batch_id' => $batch->id,
                'operator_name' => $row['operator_name'],
                'labor_cost' => $row['labor_cost'] ?? 0,
                'hours' => $row['hours'] ?? null,
                'line_order' => $order++,
            ]);
        }
    }

    private function recalculateInputTotal(DanaBatch $batch): void
    {
        $total = (float) DanaBatchMaterialInput::query()
            ->where('dana_batch_id', $batch->id)
            ->sum('quantity_kg');

        $batch->update(['total_input_kg' => $total]);
    }

    private function applyRecipeLines(DanaBatch $batch, int $recipeId, float $outputKg): void
    {
        $recipe = DanaProductionRecipe::query()->with('lines')->find($recipeId);
        if (! $recipe) {
            return;
        }

        $order = 1;
        foreach ($recipe->lines as $line) {
            $qty = round($outputKg * (float) $line->quantity_per_output_kg, 4);
            if ($qty <= 0) {
                continue;
            }

            DanaBatchMaterialInput::query()->create([
                'dana_batch_id' => $batch->id,
                'material_id' => $line->material_id,
                'material_variant_id' => $line->material_variant_id,
                'input_type' => $line->input_type,
                'quantity_kg' => $qty,
                'line_order' => $order++,
            ]);
        }
    }
}