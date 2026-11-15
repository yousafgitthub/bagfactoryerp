<?php

namespace App\Services\Reports;

class ReportResult
{
    /**
     * @param  list<array{key: string, label: string, sortable?: bool}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $columns,
        public array $rows,
        public array $filters,
        public array $summary = [],
        public int $totalRows = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPageProps(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'columns' => $this->columns,
            'rows' => $this->rows,
            'filters' => $this->filters,
            'summary' => $this->summary,
            'totalRows' => $this->totalRows ?: count($this->rows),
        ];
    }
}