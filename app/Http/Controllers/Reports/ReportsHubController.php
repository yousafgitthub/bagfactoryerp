<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportRunner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsHubController extends Controller
{
    public function __invoke(Request $request, ReportRunner $runner): Response
    {
        $this->authorize('viewModule', 'reports');

        $categories = config('reports.categories', []);

        return Inertia::render('Reports/Hub', [
            'pageTitle' => 'Reports',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Reports'],
            ],
            'categories' => $categories,
            'reportsByCategory' => $runner->catalogByCategory(),
        ]);
    }
}