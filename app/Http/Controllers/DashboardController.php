<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDataService $dashboardData,
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewDashboard', $request->user());

        return Inertia::render('Dashboard', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
            'dashboard' => $this->dashboardData->forUser($request->user(), [
                'period' => $request->string('period', 'week')->toString(),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ]),
        ]);
    }
}