<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\OvertimeRequest;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Services\Hr\HrException;
use App\Services\Hr\OvertimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    public function __construct(
        private readonly OvertimeService $overtimeService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'attendance');

        $from = $request->string('from')->trim()->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->trim()->toString() ?: now()->endOfMonth()->toDateString();
        $approvalStatus = $request->string('approval_status')->trim()->toString();
        $employeeId = $request->integer('employee_id') ?: null;

        $query = OvertimeRecord::query()->with(['employee', 'approver']);

        $query->whereBetween('overtime_date', [$from, $to]);

        if ($approvalStatus !== '') {
            $query->where('approval_status', $approvalStatus);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return Inertia::render('Hr/Overtime/Index', [
            'pageTitle' => 'Overtime register',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Attendance', 'href' => route('erp.attendance')],
                ['label' => 'Overtime'],
            ],
            'records' => $query->orderByDesc('overtime_date')->paginate(25)->withQueryString(),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'approval_status' => $approvalStatus,
                'employee_id' => $employeeId,
            ],
            'lookups' => [
                'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
                'approvalStatuses' => config('hr.overtime_approval_statuses'),
            ],
            'canManage' => $request->user()?->can('attendance.manage') === true
                || $request->user()?->can('employees.manage') === true,
            'canApprove' => $request->user()?->can('approveOvertime') === true,
        ]);
    }

    public function store(OvertimeRequest $request): RedirectResponse
    {
        $this->authorize('manageAttendance');

        try {
            $this->overtimeService->record($request->validated(), $request->user());
        } catch (HrException $e) {
            return back()->withErrors(['hours' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Overtime recorded.');
    }

    public function approve(Request $request, OvertimeRecord $overtime): RedirectResponse
    {
        $this->authorize('approveOvertime');

        $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->overtimeService->approve(
                $overtime,
                $request->user(),
                $request->string('decision')->toString(),
                $request->string('notes')->trim()->toString() ?: null
            );
        } catch (HrException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return back()->with('success', 'Overtime '.$request->string('decision').'.');
    }
}