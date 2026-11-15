<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;

class DashboardDateRange
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $period,
    ) {}

    /**
     * @param  array{period?: string, from?: string|null, to?: string|null}  $filters
     */
    public static function fromFilters(array $filters): self
    {
        $period = $filters['period'] ?? 'week';

        return match ($period) {
            'today' => new self(now()->startOfDay(), now()->endOfDay(), 'today'),
            'month' => new self(now()->startOfMonth(), now()->endOfMonth(), 'month'),
            'custom' => new self(
                Carbon::parse($filters['from'] ?? now()->toDateString())->startOfDay(),
                Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay(),
                'custom',
            ),
            default => new self(now()->startOfWeek(), now()->endOfWeek(), 'week'),
        };
    }

    /**
     * @return list<array{label: string, date: string}>
     */
    public function days(): array
    {
        $days = [];
        $cursor = $this->start->copy()->startOfDay();

        while ($cursor->lte($this->end)) {
            $days[] = [
                'label' => $cursor->format('D'),
                'date' => $cursor->toDateString(),
            ];
            $cursor->addDay();
        }

        return $days;
    }
}