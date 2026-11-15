<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 16, 2)->default(0);
            $table->decimal('current_balance', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('discount_total', 16, 2)->default(0);
            $table->decimal('tax_total', 16, 2)->default(0);
            $table->decimal('grand_total', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->foreignId('unit_of_measurement_id')->constrained('units_of_measurement');
            $table->decimal('ordered_qty', 16, 4);
            $table->decimal('received_qty', 16, 4)->default(0);
            $table->decimal('invoiced_qty', 16, 4)->default(0);
            $table->decimal('returned_qty', 16, 4)->default(0);
            $table->decimal('unit_price', 16, 4);
            $table->decimal('discount_amount', 16, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 32)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('receipt_date');
            $table->string('status', 16)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_order_id', 'status']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained('purchase_order_lines');
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('received_qty', 16, 4);
            $table->decimal('unit_price', 16, 4);
            $table->decimal('unit_cost', 16, 4);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 32)->unique();
            $table->string('supplier_invoice_number', 64)->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('discount_total', 16, 2)->default(0);
            $table->decimal('tax_total', 16, 2)->default(0);
            $table->decimal('grand_total', 16, 2)->default(0);
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->constrained('goods_receipt_lines')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('quantity', 16, 4);
            $table->decimal('unit_price', 16, 4);
            $table->decimal('discount_amount', 16, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 32)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('payment_date');
            $table->decimal('amount', 16, 2);
            $table->string('payment_method', 32)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('supplier_id');
        });

        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained('supplier_payments')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices');
            $table->decimal('amount', 16, 2);
            $table->timestamps();

            $table->unique(['supplier_payment_id', 'purchase_invoice_id'], 'supplier_payment_invoice_unique');
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 32)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('return_date');
            $table->string('status', 16)->default('draft');
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('quantity', 16, 4);
            $table->decimal('unit_cost', 16, 4)->default(0);
            $table->decimal('unit_price', 16, 4);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('entry_type', 32);
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('document_number', 64)->nullable();
            $table->string('description')->nullable();
            $table->decimal('debit', 16, 2)->default(0);
            $table->decimal('credit', 16, 2)->default(0);
            $table->decimal('running_balance', 16, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'entry_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};