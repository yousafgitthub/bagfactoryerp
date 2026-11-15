<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('type', 24);
            $table->string('normal_balance', 8);
            $table->foreignId('parent_id')->nullable()->constrained('gl_accounts')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_postable')->default(true);
            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        if (! Schema::hasColumn('expense_categories', 'gl_account_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->foreignId('gl_account_id')->nullable()->after('description')->constrained('gl_accounts')->nullOnDelete();
            });
        }

        Schema::create('gl_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 32)->unique();
            $table->date('entry_date');
            $table->string('status', 16)->default('posted');
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('gl_journal_entries')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('total_debit', 16, 2)->default(0);
            $table->decimal('total_credit', 16, 2)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'gl_journal_source_unique');
            $table->index('entry_date');
        });

        Schema::create('gl_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_journal_entry_id')->constrained('gl_journal_entries')->cascadeOnDelete();
            $table->foreignId('gl_account_id')->constrained('gl_accounts');
            $table->decimal('debit', 16, 2)->default(0);
            $table->decimal('credit', 16, 2)->default(0);
            $table->string('memo')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['gl_account_id', 'gl_journal_entry_id']);
        });

        Schema::create('treasury_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('account_type', 16);
            $table->foreignId('gl_account_id')->constrained('gl_accounts');
            $table->decimal('opening_balance', 16, 2)->default(0);
            $table->decimal('current_balance', 16, 2)->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        Schema::create('treasury_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number', 32)->unique();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts');
            $table->foreignId('counter_treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->string('movement_type', 24);
            $table->date('movement_date');
            $table->decimal('amount', 16, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('gl_journal_entry_id')->nullable()->constrained('gl_journal_entries')->nullOnDelete();
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['treasury_account_id', 'movement_date']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 32)->unique();
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->date('expense_date');
            $table->decimal('amount', 16, 2);
            $table->string('payment_status', 16)->default('paid');
            $table->string('payment_method', 24)->nullable();
            $table->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->string('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('gl_journal_entry_id')->nullable()->constrained('gl_journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expense_date', 'payment_status']);
        });

        Schema::create('company_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 32)->unique();
            $table->string('direction', 16);
            $table->string('counterparty_name');
            $table->decimal('principal', 16, 2);
            $table->decimal('outstanding_principal', 16, 2);
            $table->date('start_date');
            $table->date('maturity_date')->nullable();
            $table->string('status', 16)->default('active');
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('gl_journal_entry_id')->nullable()->constrained('gl_journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('company_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_loan_id')->constrained('company_loans')->cascadeOnDelete();
            $table->date('repayment_date');
            $table->decimal('principal_amount', 16, 2)->default(0);
            $table->decimal('interest_amount', 16, 2)->default(0);
            $table->decimal('total_amount', 16, 2);
            $table->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->foreignId('gl_journal_entry_id')->nullable()->constrained('gl_journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_payments', 'treasury_account_id')) {
                $table->foreignId('treasury_account_id')->nullable()->after('payment_method')->constrained('treasury_accounts')->nullOnDelete();
            }
        });

        Schema::table('customer_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_receipts', 'treasury_account_id')) {
                $table->foreignId('treasury_account_id')->nullable()->after('payment_method')->constrained('treasury_accounts')->nullOnDelete();
            }
        });

        Schema::table('payroll_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_payments', 'treasury_account_id')) {
                $table->foreignId('treasury_account_id')->nullable()->after('payment_method')->constrained('treasury_accounts')->nullOnDelete();
            }
        });

        $now = now();

        $accounts = [
            ['1000', 'Cash', 'asset', 'debit'],
            ['1010', 'Bank', 'asset', 'debit'],
            ['1100', 'Accounts receivable', 'asset', 'debit'],
            ['1200', 'Inventory', 'asset', 'debit'],
            ['1300', 'Goods received not invoiced', 'asset', 'debit'],
            ['1400', 'Loans receivable', 'asset', 'debit'],
            ['2000', 'Accounts payable', 'liability', 'credit'],
            ['2100', 'Salaries payable', 'liability', 'credit'],
            ['2200', 'Accrued expenses', 'liability', 'credit'],
            ['3000', 'Owner equity', 'equity', 'credit'],
            ['3100', 'Loans payable', 'liability', 'credit'],
            ['4000', 'Sales revenue', 'revenue', 'credit'],
            ['5000', 'Cost of goods sold', 'expense', 'debit'],
            ['5100', 'Payroll expense', 'expense', 'debit'],
            ['5200', 'Interest expense', 'expense', 'debit'],
            ['6000', 'Factory expenses', 'expense', 'debit'],
        ];

        foreach ($accounts as [$code, $name, $type, $normal]) {
            DB::table('gl_accounts')->insert([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'normal_balance' => $normal,
                'parent_id' => null,
                'is_system' => true,
                'is_postable' => true,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $cashGl = DB::table('gl_accounts')->where('code', '1000')->value('id');
        $bankGl = DB::table('gl_accounts')->where('code', '1010')->value('id');

        DB::table('treasury_accounts')->insert([
            [
                'code' => 'CASH-01',
                'name' => 'Main cash',
                'account_type' => 'cash',
                'gl_account_id' => $cashGl,
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BANK-01',
                'name' => 'Main bank',
                'account_type' => 'bank',
                'gl_account_id' => $bankGl,
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('payroll_payments', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_payments', 'treasury_account_id')) {
                $table->dropConstrainedForeignId('treasury_account_id');
            }
        });

        Schema::table('customer_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('customer_receipts', 'treasury_account_id')) {
                $table->dropConstrainedForeignId('treasury_account_id');
            }
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'treasury_account_id')) {
                $table->dropConstrainedForeignId('treasury_account_id');
            }
        });

        Schema::dropIfExists('company_loan_repayments');
        Schema::dropIfExists('company_loans');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('treasury_movements');
        Schema::dropIfExists('treasury_accounts');
        Schema::dropIfExists('gl_journal_lines');
        Schema::dropIfExists('gl_journal_entries');
        Schema::dropIfExists('gl_accounts');

        if (Schema::hasColumn('expense_categories', 'gl_account_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('gl_account_id');
            });
        }
    }
};