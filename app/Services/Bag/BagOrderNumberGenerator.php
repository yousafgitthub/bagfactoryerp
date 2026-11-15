<?php

namespace App\Services\Bag;

use App\Models\BagProductionOrder;
use Illuminate\Support\Str;

class BagOrderNumberGenerator
{
    public function next(): string
    {
        $ym = now()->format('ym');

        do {
            $candidate = sprintf('BPO-%s-%s', $ym, Str::upper(Str::random(6)));
        } while (BagProductionOrder::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}