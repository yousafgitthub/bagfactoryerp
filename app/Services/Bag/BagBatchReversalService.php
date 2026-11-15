<?php

namespace App\Services\Bag;

use App\Models\BagBatchInventoryLink;
use App\Models\BagProductionBatch;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Support\Facades\DB;

class BagBatchReversalService
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPosting,
        private readonly InventoryTransactionNumberGenerator $numbers,
        private readonly BagOrderPersistenceService $orders,
    ) {
    }

    public function reverse(BagProductionBatch $batch, User $actor, ?string $notes = null): BagProductionBatch
    {
        return DB::transaction(function () use ($batch, $actor, $notes) {
            $locked = BagProductionBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->with(['inventoryLinks.inventoryTransaction', 'order'])
                ->firstOrFail();

            if (! $locked->isPosted()) {
                throw new BagBatchException('Only posted batches can be reversed.');
            }

            if ($locked->reversed_at !== null) {
                throw new BagBatchException('Batch is already reversed.');
            }

            foreach ($locked->inventoryLinks()->where('link_type', '!=', 'reversal')->get() as $link) {
                $original = $link->inventoryTransaction;
                if (! $original || $original->status !== 'posted') {
                    continue;
                }

                $reversal = InventoryTransaction::query()->create([
                    'transaction_number' => $this->numbers->next('reversal'),
                    'type' => 'reversal',
                    'status' => 'draft',
                    'warehouse_id' => $original->warehouse_id,
                    'transaction_date' => now()->toDateString(),
                    'reference_type' => BagProductionBatch::class,
                    'reference_id' => $locked->id,
                    'notes' => trim('Reversal of bag batch '.$locked->batch_number.($notes ? ': '.$notes : '')),
                    'reversal_of_id' => $original->id,
                    'created_by' => $actor->id,
                ]);

                $this->inventoryPosting->post($reversal, $actor);

                BagBatchInventoryLink::query()->create([
                    'bag_production_batch_id' => $locked->id,
                    'inventory_transaction_id' => $reversal->id,
                    'link_type' => 'reversal',
                ]);
            }

            $locked->update([
                'status' => 'reversed',
                'reversed_at' => now(),
            ]);

            if ($locked->order) {
                $this->orders->refreshProducedTotals($locked->order);
            }

            return $locked->fresh();
        });
    }
}