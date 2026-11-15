<?php

namespace App\Services\Reports;

use InvalidArgumentException;

class ReportRunner
{
    public function run(string $key, ReportFilters $filters): ReportResult
    {
        $definition = config("reports.definitions.{$key}");

        if (! is_array($definition) || empty($definition['handler'])) {
            throw new InvalidArgumentException("Unknown report: {$key}");
        }

        [$class, $method] = $definition['handler'];

        $service = app($class);

        if (! method_exists($service, $method)) {
            throw new InvalidArgumentException("Report handler missing: {$class}::{$method}");
        }

        return $service->{$method}($filters);
    }

    /**
     * @return list<array{key: string, category: string, title: string, description: string}>
     */
    public function catalog(): array
    {
        $definitions = config('reports.definitions', []);
        $items = [];

        foreach ($definitions as $key => $definition) {
            $items[] = [
                'key' => $key,
                'category' => $definition['category'],
                'title' => $definition['title'],
                'description' => $definition['description'],
            ];
        }

        usort($items, fn ($a, $b) => [$a['category'], $a['title']] <=> [$b['category'], $b['title']]);

        return $items;
    }

    /**
     * @return array<string, list<array{key: string, title: string, description: string, href: string}>>
     */
    public function catalogByCategory(): array
    {
        $grouped = [];

        foreach ($this->catalog() as $item) {
            $grouped[$item['category']][] = [
                'key' => $item['key'],
                'title' => $item['title'],
                'description' => $item['description'],
                'href' => route('erp.reports.show', $item['key']),
            ];
        }

        return $grouped;
    }
}