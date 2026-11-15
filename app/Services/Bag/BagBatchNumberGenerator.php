<?php

namespace App\Services\Bag;

use App\Models\BagProductionBatch;
use Illuminate\Support\Str;

class BagBatchNumberGenerator
{
    public function next(): string
    {
        $ym = now()->format('ym');

        do {
            $candidate = sprintf('BAG-%s-%s', $ym, Str::upper(Str::random(6)));
        } while (BagProductionBatch::query()->where('batch_number', $candidate)->exists());

        return $candidate;
    }
}