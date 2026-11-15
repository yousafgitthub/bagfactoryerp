<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\GlAccount;
use App\Services\Reports\ReportCsvExporter;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportRunner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ErpReportController extends Controller
{
    public function show(Request $request, string $reportKey, ReportRunner $runner): Response
    {
        $this->authorize('viewModule', 'reports');

        $definition = config("reports.definitions.{$reportKey}");
        abort_unless(is_array($definition), 404);

        $filters = ReportFilters::fromRequest($request);
        $result = $runner->run($reportKey, $filters);

        return Inertia::render('Reports/Show', [
            'pageTitle' => $result->title,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Reports', 'href' => route('erp.reports')],
                ['label' => $result->title],
            ],
            'reportKey' => $reportKey,
            'definition' => [
                'date_filter' => (bool) ($definition['date_filter'] ?? false),
                'granularity' => (bool) ($definition['granularity'] ?? false),
                'as_of' => (bool) ($definition['as_of'] ?? false),
                'gl_account' => (bool) ($definition['gl_account'] ?? false),
            ],
            'glAccounts' => ($definition['gl_account'] ?? false)
                ? GlAccount::query()->orderBy('code')->get(['id', 'code', 'name'])
                : [],
            ...$result->toPageProps(),
            'exportUrls' => [
                'csv' => route('erp.reports.export', ['reportKey' => $reportKey, 'format' => 'csv']).'?'.$request->getQueryString(),
                'print' => route('erp.reports.export', ['reportKey' => $reportKey, 'format' => 'print']).'?'.$request->getQueryString(),
            ],
        ]);
    }

    public function export(
        Request $request,
        string $reportKey,
        string $format,
        ReportRunner $runner,
        ReportCsvExporter $exporter,
    ): StreamedResponse|Response {
        $this->authorize('viewModule', 'reports');

        abort_unless(in_array($format, ['csv', 'print', 'pdf'], true), 404);

        $filters = ReportFilters::fromRequest($request);
        $result = $runner->run($reportKey, $filters);

        $safeName = str($reportKey)->slug()->toString();

        if ($format === 'csv') {
            return $exporter->download($result, "{$safeName}.csv");
        }

        return Inertia::render('Reports/Print', [
            'pageTitle' => $result->title,
            'reportKey' => $reportKey,
            ...$result->toPageProps(),
        ]);
    }
}