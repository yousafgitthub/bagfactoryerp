<?php

namespace Tests\Feature\Hr;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeSalaryRate;
use App\Models\OvertimeRecord;
use App\Models\User;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\EmployeePersistenceService;
use App\Services\Hr\HrException;
use App\Services\Hr\OvertimeService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrEmployeeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->hrUser = User::factory()->create(['email_verified_at' => now()]);
        $this->hrUser->assignRole('hr-manager');

        $this->department = Department::query()->create([
            'code' => 'PROD',
            'name' => 'Production',
            'status' => 'active',
        ]);

        Designation::query()->create([
            'code' => 'OP',
            'name' => 'Operator',
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);
    }

    public function test_employee_create_opens_salary_rate_history(): void
    {
        $service = app(EmployeePersistenceService::class);

        $employee = $service->create([
            'employee_code' => 'EMP-001',
            'name' => 'Ali Khan',
            'joining_date' => '2026-01-01',
            'department_id' => $this->department->id,
            'salary_type' => 'monthly',
            'basic_monthly_salary' => 50000,
            'overtime_rate' => 250,
            'status' => 'active',
        ], $this->hrUser);

        $this->assertDatabaseCount('employee_salary_rates', 1);
        $this->assertSame('50000.00', $employee->salaryRates->first()->basic_monthly_salary);
    }

    public function test_salary_update_closes_prior_rate_row(): void
    {
        $service = app(EmployeePersistenceService::class);

        $employee = $service->create([
            'employee_code' => 'EMP-002',
            'name' => 'Sara',
            'joining_date' => '2026-01-01',
            'salary_type' => 'daily',
            'daily_wage_rate' => 1200,
            'status' => 'active',
        ], $this->hrUser);

        $service->update($employee, [
            'employee_code' => 'EMP-002',
            'name' => 'Sara',
            'joining_date' => '2026-01-01',
            'salary_type' => 'daily',
            'daily_wage_rate' => 1500,
            'status' => 'active',
            'salary_effective_from' => '2026-06-01',
        ], $this->hrUser);

        $rates = EmployeeSalaryRate::query()->where('employee_id', $employee->id)->orderBy('effective_from')->get();
        $this->assertCount(2, $rates);
        $this->assertSame('2026-05-31', $rates[0]->effective_to?->toDateString());
        $this->assertNull($rates[1]->effective_to);
        $this->assertSame('1500.00', $rates[1]->daily_wage_rate);
    }

    public function test_duplicate_attendance_is_rejected(): void
    {
        $employee = Employee::query()->create([
            'employee_code' => 'EMP-003',
            'name' => 'Worker',
            'joining_date' => '2026-01-01',
            'salary_type' => 'daily',
            'daily_wage_rate' => 1000,
            'status' => 'active',
            'is_active' => true,
        ]);

        $attendanceService = app(AttendanceService::class);

        $attendanceService->record([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-11-10',
            'status' => 'present',
        ], $this->hrUser);

        $this->expectException(HrException::class);

        $attendanceService->record([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-11-10',
            'status' => 'absent',
        ], $this->hrUser);
    }

    public function test_half_day_default_working_hours(): void
    {
        $employee = Employee::query()->create([
            'employee_code' => 'EMP-004',
            'name' => 'Half',
            'joining_date' => '2026-01-01',
            'salary_type' => 'daily',
            'daily_wage_rate' => 1000,
            'status' => 'active',
            'is_active' => true,
        ]);

        $row = app(AttendanceService::class)->record([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-11-11',
            'status' => 'half_day',
        ], $this->hrUser);

        $this->assertSame('4.00', number_format((float) $row->working_hours, 2, '.', ''));
    }

    public function test_overtime_approval_flow(): void
    {
        $employee = Employee::query()->create([
            'employee_code' => 'EMP-005',
            'name' => 'OT Worker',
            'joining_date' => '2026-01-01',
            'salary_type' => 'monthly',
            'basic_monthly_salary' => 30000,
            'overtime_rate' => 200,
            'status' => 'active',
            'is_active' => true,
        ]);

        $overtime = app(OvertimeService::class)->record([
            'employee_id' => $employee->id,
            'overtime_date' => '2026-11-12',
            'hours' => 2,
        ], $this->hrUser);

        $this->assertSame('pending', $overtime->approval_status);
        $this->assertSame('400.00', $overtime->calculated_amount);

        $approved = app(OvertimeService::class)->approve($overtime, $this->hrUser, 'approved');
        $this->assertSame('approved', $approved->approval_status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_hr_hub_routes_require_permission(): void
    {
        $denied = User::factory()->create(['email_verified_at' => now()]);
        $denied->assignRole('storekeeper');

        $this->actingAs($denied)->get(route('erp.employees'))->assertForbidden();

        $this->actingAs($this->hrUser)->get(route('erp.employees'))->assertOk();
        $this->actingAs($this->hrUser)->get(route('erp.attendance'))->assertOk();
    }
}