<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\AttendanceRequest;
use App\Http\Requests\Hr\DailyAttendanceRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\HrException;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'attendance');

        $from = $request->string('from')->trim()->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->trim()->toString() ?: now()->endOfMonth()->toDateString();
        $employeeId = $request->integer('employee_id') ?: null;
        $status = $request->string('status')->trim()->toString();

        $query = Attendance::query()
            ->with(['employee.department'])
            ->whereBetween('attendance_date', [$from, $to]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return Inertia::render('Hr/Attendance/Index', [
            'pageTitle' => 'Attendance register',
            'breadcrumbs' => $this->attendanceBreadcrumbs('Register'),
            'attendances' => $query->orderByDesc('attendance_date')->orderBy('employee_id')->paginate(25)->withQueryString(),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'employee_id' => $employeeId,
                'status' => $status,
            ],
            'lookups' => [
                'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
                'statuses' => config('hr.attendance_statuses'),
            ],
            'canManage' => $request->user()?->can('attendance.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageAttendance');

        return Inertia::render('Hr/Attendance/Form', [
            'pageTitle' => 'Record attendance',
            'breadcrumbs' => $this->attendanceBreadcrumbs('New'),
            'attendance' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(AttendanceRequest $request): RedirectResponse
    {
        $this->authorize('manageAttendance');

        try {
            $this->attendanceService->record($request->validated(), $request->user());
        } catch (HrException $e) {
            return back()->withErrors(['attendance_date' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hr.attendance.index')->with('success', 'Attendance saved.');
    }

    public function edit(Attendance $attendance): Response
    {
        $this->authorize('manageAttendance');

        $attendance->load('employee');

        return Inertia::render('Hr/Attendance/Form', [
            'pageTitle' => 'Edit attendance',
            'breadcrumbs' => $this->attendanceBreadcrumbs('Edit'),
            'attendance' => $attendance,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(AttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('manageAttendance');

        try {
            $this->attendanceService->record($request->validated(), $request->user(), $attendance);
        } catch (HrException $e) {
            return back()->withErrors(['attendance_date' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hr.attendance.index')->with('success', 'Attendance updated.');
    }

    public function daily(Request $request): Response
    {
        $this->authorize('viewModule', 'attendance');

        $date = $request->string('date')->trim()->toString() ?: now()->toDateString();

        $employees = Employee::query()
            ->where('is_active', true)
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'employee_code', 'name', 'department_id']);

        $existing = Attendance::query()
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('employee_id');

        return Inertia::render('Hr/Attendance/DailyEntry', [
            'pageTitle' => 'Daily attendance',
            'breadcrumbs' => $this->attendanceBreadcrumbs('Daily entry'),
            'attendanceDate' => $date,
            'employees' => $employees,
            'existing' => $existing,
            'statuses' => config('hr.attendance_statuses'),
            'canManage' => $request->user()?->can('attendance.manage') === true,
        ]);
    }

    public function storeDaily(DailyAttendanceRequest $request): RedirectResponse
    {
        $this->authorize('manageAttendance');

        $result = $this->attendanceService->recordDailyBulk(
            $request->validated('attendance_date'),
            $request->validated('rows'),
            $request->user()
        );

        $message = "Saved {$result['saved']} attendance row(s).";
        if ($result['errors'] !== []) {
            return redirect()
                ->route('hr.attendance.daily', ['date' => $request->validated('attendance_date')])
                ->with('warning', $message.' Some rows were skipped: '.implode(' ', $result['errors']));
        }

        return redirect()
            ->route('hr.attendance.daily', ['date' => $request->validated('attendance_date')])
            ->with('success', $message);
    }

    public function monthly(Request $request): Response
    {
        $this->authorize('viewModule', 'attendance');

        $month = $request->string('month')->trim()->toString() ?: now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $departmentId = $request->integer('department_id') ?: null;

        $employeeQuery = Employee::query()->where('is_active', true)->with('department:id,name');
        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }
        $employees = $employeeQuery->orderBy('name')->get(['id', 'employee_code', 'name', 'department_id']);

        $counts = DB::table('attendances')
            ->select('employee_id', 'status', DB::raw('count(*) as total'))
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('employee_id', 'status')
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($counts) {
            $byStatus = ($counts[$employee->id] ?? collect())->keyBy('status');

            return [
                'employee' => $employee,
                'counts' => collect(config('hr.attendance_statuses'))->keys()->mapWithKeys(
                    fn ($status) => [$status => (int) ($byStatus[$status]->total ?? 0)]
                ),
            ];
        });

        return Inertia::render('Hr/Attendance/MonthlyReport', [
            'pageTitle' => 'Monthly attendance',
            'breadcrumbs' => $this->attendanceBreadcrumbs('Monthly report'),
            'month' => $month,
            'rows' => $rows,
            'statuses' => config('hr.attendance_statuses'),
            'lookups' => [
                'departments' => Department::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            ],
            'departmentId' => $departmentId,
        ]);
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function attendanceBreadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Attendance', 'href' => route('erp.attendance')],
            ['label' => $leaf],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
            'statuses' => config('hr.attendance_statuses'),
        ];
    }
}