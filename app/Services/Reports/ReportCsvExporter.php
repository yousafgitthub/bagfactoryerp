<?php

namespace App\Services\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvExporter
{
    public function download(ReportResult $result, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($result) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_column($result->columns, 'label'));

            foreach ($result->rows as $row) {
                $line = [];
                foreach ($result->columns as $column) {
                    $line[] = $row[$column['key']] ?? '';
                }
                fputcsv($out, $line);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}