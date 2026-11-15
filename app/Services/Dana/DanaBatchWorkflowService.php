<?php

namespace App\Services\Dana;

use App\Models\DanaBatch;
use App\Models\User;

class DanaBatchWorkflowService
{
    public function transition(DanaBatch $batch, string $action, User $actor): DanaBatch
    {
        $map = config('dana.transitions.'.$batch->status, []);

        if (! isset($map[$action])) {
            throw new DanaBatchException(sprintf(
                'Action "%s" is not allowed when batch is "%s".',
                $action,
                $batch->status,
            ));
        }

        $next = $map[$action];
        $updates = ['status' => $next];

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

    public function allowedActions(DanaBatch $batch): array
    {
        return array_keys(config('dana.transitions.'.$batch->status, []));
    }
}