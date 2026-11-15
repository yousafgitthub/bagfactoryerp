<?php

namespace Tests\Feature\Hr;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\OvertimeRecord;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Services\Hr\AdvanceLoanService;
use App\Services\Hr\EmployeePersistenceService;
use App\Services\Hr\PayrollException;
use App\Services\Hr\PayrollPaymentService;
use App\Services\Hr\PayrollRunService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
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
            'code' => 'HR',
            'name' => 'HR',
            'status' => 'active',
        ]);
        Designation::query()->create([
            'code' => 'STF',
            'name' => 'Staff',
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);
    }

    public function test_monthly_payroll_applies_absence_and_overtime(): void
    {
        $employee = $this->monthlyEmployee('EMP-M1', 30000);

        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-10',
            'status' => 'absent',
        ]);

        OvertimeRecord::query()->create([
            'employee_id' => $employee->id,
            'overtime_date' => '2026-03-12',
            'hours' => 2,
            'rate_per_hour' => 200,
            'calculated_amount' => 400,
            'approval_status' => 'approved',
        ]);

        $run = $this->runForMonth('2026-03-01', '2026-03-31');
        $record = $run->records()->where('employee_id', $employee->id)->first();

        $this->assertNotNull($record);
        $this->assertEqualsWithDelta(-1000.0, (float) $record->lines()->where('line_type', 'absence_deduction')->value('amount'), 0.01);
        $this->assertEqualsWithDelta(400.0, (float) $record->overtime_amount, 0.01);
        $this->assertEqualsWithDelta(29400.0, (float) $record->net_payable, 0.01);
    }

    public function test_daily_wage_payroll_counts_half_days(): void
    {
        $employee = $this->dailyEmployee('EMP-D1', 1000);

        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-04-05',
            'status' => 'present',
        ]);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-04-06',
            'status' => 'half_day',
        ]);

        $run = $this->runForMonth('2026-04-01', '2026-04-30');
        $record = $run->records()->where('employee_id', $employee->id)->first();

        $this->assertSame('1.50', $record->payable_days);
        $this->assertSame('1500.00', $record->gross_amount);
    }

    public function test_advance_and_loan_deducted_once_on_approve(): void
    {
        $employee = $this->monthlyEmployee('EMP-AL', 50000);

        app(AdvanceLoanService::class)->recordAdvance($employee, [
            'advance_date' => '2026-05-01',
            'amount' => 5000,
            'per_payroll_deduction' => 2000,
        ], $this->hrUser);

        app(AdvanceLoanService::class)->recordLoan($employee, [
            'loan_date' => '2026-05-01',
            'principal' => 10000,
            'installment_amount' => 2500,
        ], $this->hrUser);

        $service = app(PayrollRunService::class);
        $run = $service->createRun(Carbon::parse('2026-05-01'), Carbon::parse('2026-05-31'), $this->hrUser);
        $service->generate($run);
        $service->approve($run, $this->hrUser);

        $advance = EmployeeAdvance::query()->where('employee_id', $employee->id)->first();
        $loan = EmployeeLoan::query()->where('employee_id', $employee->id)->first();

        $this->assertSame('2000.00', $advance->amount_repaid);
        $this->assertSame('3000.00', $advance->outstanding_balance);
        $this->assertSame('2500.00', $loan->amount_repaid);

        $record = $run->records()->where('employee_id', $employee->id)->first();
        $this->assertTrue($record->deductions_posted);

        $this->expectException(PayrollException::class);
        $service->recalculateRecord($record);
    }

    public function test_partial_salary_payment_updates_outstanding(): void
    {
        $employee = $this->monthlyEmployee('EMP-PAY', 20000);
        $service = app(PayrollRunService::class);
        $run = $service->createRun(Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'), $this->hrUser);
        $service->generate($run);
        $service->approve($run, $this->hrUser);

        $record = $run->records()->where('employee_id', $employee->id)->first();
        app(PayrollPaymentService::class)->recordPayment($record, [
            'payment_date' => '2026-07-01',
            'amount' => 8000,
            'payment_method' => 'cash',
        ], $this->hrUser);

        $record->refresh();
        $this->assertSame('partially_paid', $record->status);
        $this->assertSame('8000.00', $record->amount_paid);
        $this->assertSame('12000.00', $record->outstanding_amount);
    }

    public function test_duplicate_payroll_period_is_blocked(): void
    {
        $employee = $this->monthlyEmployee('EMP-DUP', 15000);
        $service = app(PayrollRunService::class);
        $run1 = $service->createRun(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $this->hrUser);
        $service->generate($run1);
        $service->approve($run1, $this->hrUser);

        $this->expectException(PayrollException::class);
        $service->createRun(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $this->hrUser);
    }

    public function test_recalculation_preserves_manual_bonus(): void
    {
        $employee = $this->monthlyEmployee('EMP-REC', 25000);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-15',
            'status' => 'absent',
        ]);

        $service = app(PayrollRunService::class);
        $run = $service->createRun(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'), $this->hrUser);
        $service->generate($run);

        $record = $run->records()->where('employee_id', $employee->id)->first();
        $record->lines()->create([
            'line_type' => 'bonus',
            'description' => 'Festival bonus',
            'amount' => 1000,
        ]);
        app(\App\Services\Hr\PayrollCalculationService::class)->recomputeTotals($record);

        $service->recalculateRecord($record->fresh());
        $record->refresh();

        $this->assertTrue($record->lines()->where('line_type', 'bonus')->exists());
        $netBeforeBonus = 25000 - (25000 / 30);
        $this->assertSame(number_format($netBeforeBonus + 1000, 2, '.', ''), $record->net_payable);
    }

    public function test_hr_manager_can_open_payroll_hub(): void
    {
        $this->withoutVite();

        $this->actingAs($this->hrUser)
            ->get(route('erp.payroll'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Hr/Payroll/Hub'));
    }

    private function monthlyEmployee(string $code, float $salary): Employee
    {
        return app(EmployeePersistenceService::class)->create([
            'employee_code' => $code,
            'name' => $code,
            'joining_date' => '2026-01-01',
            'department_id' => $this->department->id,
            'salary_type' => 'monthly',
            'basic_monthly_salary' => $salary,
            'status' => 'active',
        ], $this->hrUser);
    }

    private function dailyEmployee(string $code, float $rate): Employee
    {
        return app(EmployeePersistenceService::class)->create([
            'employee_code' => $code,
            'name' => $code,
            'joining_date' => '2026-01-01',
            'salary_type' => 'daily',
            'daily_wage_rate' => $rate,
            'status' => 'active',
        ], $this->hrUser);
    }

    private function runForMonth(string $start, string $end)
    {
        $service = app(PayrollRunService::class);
        $run = $service->createRun(Carbon::parse($start), Carbon::parse($end), $this->hrUser);
        $service->generate($run);

        return $run->fresh(['records']);
    }
}