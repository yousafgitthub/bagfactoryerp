<?php

namespace App\Services\Reports\Concerns;

use App\Services\Reports\ReportFilters;

trait PaginatesReportRows
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function paginateRows(array $rows, ReportFilters $filters): array
    {
        if ($filters->sort) {
            $key = $filters->sort;
            $dir = $filters->sortDirection === 'desc' ? -1 : 1;
            usort($rows, function ($a, $b) use ($key, $dir) {
                $va = $a[$key] ?? '';
                $vb = $b[$key] ?? '';

                return ($va <=> $vb) * $dir;
            });
        }

        if ($filters->search) {
            $needle = strtolower($filters->search);
            $rows = array_values(array_filter($rows, function ($row) use ($needle) {
                foreach ($row as $value) {
                    if (str_contains(strtolower((string) $value), $needle)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $offset = ($filters->page - 1) * $filters->perPage;

        return array_slice($rows, $offset, $filters->perPage);
    }

    protected function totalRowCount(array $rows, ReportFilters $filters): int
    {
        if (! $filters->search) {
            return count($rows);
        }

        $needle = strtolower($filters->search);

        return count(array_filter($rows, function ($row) use ($needle) {
            foreach ($row as $value) {
                if (str_contains(strtolower((string) $value), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }
}