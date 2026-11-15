<?php

namespace App\Services\Bag;

use App\Models\BagProductionOrder;
use App\Models\User;

class BagOrderWorkflowService
{
    public function transition(BagProductionOrder $order, string $action, User $actor): BagProductionOrder
    {
        $map = config('bag.order_transitions.'.$order->status, []);

        if (! isset($map[$action])) {
            throw new BagBatchException(sprintf(
                'Action "%s" is not allowed when order is "%s".',
                $action,
                $order->status,
            ));
        }

        $updates = ['status' => $map[$action]];

        if ($action === 'release') {
            $updates['released_at'] = now();
            $updates['released_by'] = $actor->id;
        }

        if ($action === 'cancel') {
            $updates['cancelled_at'] = now();
            $updates['cancelled_by'] = $actor->id;
        }

        $order->update($updates);

        return $order->fresh();
    }

    public function allowedActions(BagProductionOrder $order): array
    {
        return array_keys(config('bag.order_transitions.'.$order->status, []));
    }
}