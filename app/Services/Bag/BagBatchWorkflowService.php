<?php

namespace App\Services\Bag;

use App\Models\BagProductionBatch;
use App\Models\User;

class BagBatchWorkflowService
{
    public function transition(BagProductionBatch $batch, string $action, User $actor): BagProductionBatch
    {
        $map = config('bag.batch_transitions.'.$batch->status, []);

        if (! isset($map[$action])) {
            throw new BagBatchException(sprintf(
                'Action "%s" is not allowed when batch is "%s".',
                $action,
                $batch->status,
            ));
        }

        $updates = ['status' => $map[$action]];

        if ($action === 'approve') {
            $updates['approved_at'] = now();
            $updates['approved_by'] = $actor->id;
        }

        if ($action === 'cancel') {
            $updates['cancelled_at'] = now();
            $updates['cancelled_by'] = $actor->id;
        }

        $batch->update($updates);

        return $batch->fresh();
    }

    public function allowedActions(BagProductionBatch $batch): array
    {
        return array_keys(config('bag.batch_transitions.'.$batch->status, []));
    }
}