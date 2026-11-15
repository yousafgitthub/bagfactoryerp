<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Services\Hr\PayrollException;
use App\Services\Hr\PayrollRunService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly PayrollRunService $payrollRuns
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'payroll');

        $runs = PayrollRun::query()
            ->withCount('records')
            ->orderByDesc('period_start')
            ->paginate(20);

        return Inertia::render('Hr/Payroll/Runs/Index', [
            'pageTitle' => 'Payroll runs',
            'breadcrumbs' => $this->breadcrumbs('Runs'),
            'runs' => $runs,
            'statuses' => config('payroll.run_statuses'),
            'canManage' => $request->user()?->can('payroll.manage') === true,
            'canApprove' => $request->user()?->can('payroll.approve') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('managePayroll');

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return Inertia::render('Hr/Payroll/Runs/Form', [
            'pageTitle' => 'New payroll run',
            'breadcrumbs' => $this->breadcrumbs('New run'),
            'defaults' => [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('managePayroll');

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $run = $this->payrollRuns->createRun(
                Carbon::parse($validated['period_start']),
                Carbon::parse($validated['period_end']),
                $request->user(),
                $validated['notes'] ?? null,
            );
        } catch (PayrollException $e) {
            return back()->withErrors(['period_start' => $e->getMessage()]);
        }

        return redirect()->route('hr.payroll.runs.show', $run)
            ->with('status', 'Payroll run created.');
    }

    public function show(Request $request, PayrollRun $payrollRun): Response
    {
        $this->authorize('viewModule', 'payroll');

        $payrollRun->load(['records.employee.department']);

        return Inertia::render('Hr/Payroll/Runs/Show', [
            'pageTitle' => 'Payroll '.$payrollRun->period_label,
            'breadcrumbs' => $this->breadcrumbs($payrollRun->period_label),
            'run' => $payrollRun,
            'statuses' => config('payroll.run_statuses'),
            'canManage' => $request->user()?->can('payroll.manage') === true,
            'canApprove' => $request->user()?->can('payroll.approve') === true,
        ]);
    }

    public function generate(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('managePayroll');

        try {
            $this->payrollRuns->generate($payrollRun, request()->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['generate' => $e->getMessage()]);
        }

        return back()->with('status', 'Payroll generated from attendance and overtime.');
    }

    public function review(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('managePayroll');

        try {
            $this->payrollRuns->review($payrollRun, request()->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('status', 'Payroll marked reviewed.');
    }

    public function approve(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('approvePayroll');

        try {
            $this->payrollRuns->approve($payrollRun, request()->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['approve' => $e->getMessage()]);
        }

        return back()->with('status', 'Payroll approved; advance and loan deductions posted.');
    }

    public function cancel(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('approvePayroll');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->payrollRuns->cancel($payrollRun, $request->user(), $validated['reason']);
        } catch (PayrollException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return redirect()->route('hr.payroll.runs.index')->with('status', 'Payroll run cancelled.');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function breadcrumbs(string $trail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Payroll', 'href' => route('erp.payroll')],
            ['label' => 'Runs', 'href' => route('hr.payroll.runs.index')],
            ['label' => $trail],
        ];
    }
}