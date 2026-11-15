<?php

namespace App\Services\Dana;

use App\Models\DanaBatch;
use App\Models\DanaBatchInventoryLink;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Support\Facades\DB;

class DanaBatchReversalService
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPosting,
        private readonly InventoryTransactionNumberGenerator $numbers,
    ) {
    }

    public function reverse(DanaBatch $batch, User $actor, ?string $notes = null): DanaBatch
    {
        return DB::transaction(function () use ($batch, $actor, $notes) {
            $locked = DanaBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->with(['inventoryLinks.inventoryTransaction.ledgerEntries', 'inventoryLinks.inventoryTransaction.items'])
                ->firstOrFail();

            if (! $locked->isPosted()) {
                throw new DanaBatchException('Only posted batches can be reversed.');
            }

            if ($locked->reversed_at !== null) {
                throw new DanaBatchException('Batch is already reversed.');
            }

            foreach ($locked->inventoryLinks as $link) {
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
                    'reference_type' => DanaBatch::class,
                    'reference_id' => $locked->id,
                    'notes' => trim('Reversal of Dana batch '.$locked->batch_number.($notes ? ': '.$notes : '')),
                    'reversal_of_id' => $original->id,
                    'created_by' => $actor->id,
                ]);

                $this->inventoryPosting->post($reversal, $actor);

                DanaBatchInventoryLink::query()->create([
                    'dana_batch_id' => $locked->id,
                    'inventory_transaction_id' => $reversal->id,
                    'link_type' => 'reversal',
                ]);
            }

            $locked->update([
                'status' => 'reversed',
                'reversed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}