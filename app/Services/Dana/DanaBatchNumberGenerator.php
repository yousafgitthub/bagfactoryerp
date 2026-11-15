<?php

namespace App\Services\Dana;

use App\Models\DanaBatch;
use Illuminate\Support\Str;

class DanaBatchNumberGenerator
{
    public function next(): string
    {
        $ym = now()->format('ym');

        do {
            $candidate = sprintf('DBN-%s-%s', $ym, Str::upper(Str::random(6)));
        } while (DanaBatch::query()->where('batch_number', $candidate)->exists());

        return $candidate;
    }
}