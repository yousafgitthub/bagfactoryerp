<?php

namespace App\Http\Controllers\Dana;

use App\Http\Controllers\Controller;
use App\Models\DanaBatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DanaHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'dana-production');

        $postedQuery = DanaBatch::query()->where('status', 'posted');

        $summary = [
            'posted_batches' => (clone $postedQuery)->count(),
            'output_kg_mtd' => (float) (clone $postedQuery)
                ->whereBetween('posted_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('output_kg'),
            'draft_batches' => DanaBatch::query()->where('status', 'draft')->count(),
            'in_progress' => DanaBatch::query()->whereIn('status', ['submitted', 'approved', 'in_progress', 'completed'])->count(),
        ];

        $recent = DanaBatch::query()
            ->with(['machine:id,name', 'outputMaterial:id,name'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return Inertia::render('Dana/Hub', [
            'pageTitle' => 'Dana Production',
            'breadcrumbs' => $this->breadcrumbs('Overview'),
            'summary' => $summary,
            'recentBatches' => $recent,
            'canManage' => $request->user()?->can('dana-production.manage') === true,
            'links' => [
                [
                    'label' => 'Production batches',
                    'description' => 'Create, approve, post and reverse Dana batches.',
                    'href' => route('dana.batches.index'),
                ],
                [
                    'label' => 'Recipes',
                    'description' => 'Configurable consumption ratios and loss tolerance.',
                    'href' => route('dana.recipes.index'),
                ],
                [
                    'label' => 'Costing report',
                    'description' => 'Posted batch costs per KG and totals.',
                    'href' => route('dana.reports.costing'),
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function breadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Dana Production', 'href' => route('erp.dana-production')],
            ['label' => $leaf],
        ];
    }
}