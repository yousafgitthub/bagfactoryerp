<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 32)->unique();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->date('joining_date');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->string('salary_type', 16);
            $table->decimal('basic_monthly_salary', 14, 2)->nullable();
            $table->decimal('daily_wage_rate', 14, 2)->nullable();
            $table->decimal('overtime_rate', 14, 4)->nullable();
            $table->string('overtime_rule')->nullable();
            $table->string('status', 32)->default('active');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'status']);
        });

        Schema::create('employee_salary_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('salary_type', 16);
            $table->decimal('basic_monthly_salary', 14, 2)->nullable();
            $table->decimal('daily_wage_rate', 14, 2)->nullable();
            $table->decimal('overtime_rate', 14, 4)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 32);
            $table->time('check_in_at')->nullable();
            $table->time('check_out_at')->nullable();
            $table->decimal('working_hours', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
            $table->index('attendance_date');
        });

        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('overtime_date');
            $table->decimal('hours', 6, 2);
            $table->decimal('rate_per_hour', 14, 4);
            $table->decimal('calculated_amount', 14, 2);
            $table->string('approval_status', 32)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'overtime_date']);
            $table->index('approval_status');
        });

        Schema::create('employee_production_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('assignment_type', 32);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->foreignId('dana_batch_id')->nullable()->constrained('dana_batches')->nullOnDelete();
            $table->foreignId('bag_production_batch_id')->nullable()->constrained('bag_production_batches')->nullOnDelete();
            $table->foreignId('bag_production_order_id')->nullable()->constrained('bag_production_orders')->nullOnDelete();
            $table->date('assigned_from')->nullable();
            $table->date('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'assignment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_production_assignments');
        Schema::dropIfExists('overtime_records');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employee_salary_rates');
        Schema::dropIfExists('employees');
    }
};