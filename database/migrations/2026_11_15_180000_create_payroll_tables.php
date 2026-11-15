<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_label', 32);
            $table->string('status', 32)->default('draft');
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->decimal('total_paid', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
            $table->index('status');
        });

        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('advance_date');
            $table->decimal('amount', 14, 2);
            $table->string('reason')->nullable();
            $table->decimal('amount_repaid', 14, 2)->default(0);
            $table->decimal('outstanding_balance', 14, 2);
            $table->decimal('per_payroll_deduction', 14, 2)->nullable();
            $table->string('status', 32)->default('open');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('loan_date');
            $table->decimal('principal', 14, 2);
            $table->decimal('installment_amount', 14, 2);
            $table->unsignedSmallInteger('installment_count')->nullable();
            $table->decimal('amount_repaid', 14, 2)->default(0);
            $table->decimal('outstanding_principal', 14, 2);
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('salary_type', 16);
            $table->decimal('basic_monthly_salary', 14, 2)->nullable();
            $table->decimal('daily_wage_rate', 14, 2)->nullable();
            $table->foreignId('employee_salary_rate_id')->nullable()->constrained('employee_salary_rates')->nullOnDelete();
            $table->decimal('payable_days', 8, 2)->default(0);
            $table->decimal('absent_days', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_amount', 14, 2)->default(0);
            $table->decimal('bonuses_total', 14, 2)->default(0);
            $table->decimal('allowances_total', 14, 2)->default(0);
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('outstanding_amount', 14, 2)->default(0);
            $table->string('status', 32)->default('draft');
            $table->boolean('deductions_posted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['employee_id', 'period_start', 'period_end', 'status'], 'payroll_records_employee_period_idx');
        });

        Schema::create('payroll_record_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
            $table->string('line_type', 32);
            $table->string('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['payroll_record_id', 'line_type']);
        });

        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 32);
            $table->string('reference', 128)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('payment_date');
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
            $table->string('adjustment_type', 32);
            $table->decimal('amount', 14, 2);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('payroll_record_lines');
        Schema::dropIfExists('payroll_records');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('payroll_runs');
    }
};