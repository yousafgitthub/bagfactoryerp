<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
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

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers');
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

            $table->index(['customer_id', 'status']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('bag_product_id')->nullable()->constrained('bag_products')->nullOnDelete();
            $table->foreignId('bag_product_variant_id')->nullable()->constrained('bag_product_variants')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->foreignId('unit_of_measurement_id')->constrained('units_of_measurement');
            $table->string('size_label')->nullable();
            $table->string('color_label')->nullable();
            $table->decimal('ordered_qty', 16, 4);
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

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('sale_type', 16)->default('credit');
            $table->string('status', 24)->default('draft');
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('discount_total', 16, 2)->default(0);
            $table->decimal('tax_total', 16, 2)->default(0);
            $table->decimal('grand_total', 16, 2)->default(0);
            $table->decimal('cogs_total', 16, 2)->default(0);
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });

        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines')->nullOnDelete();
            $table->foreignId('bag_product_id')->nullable()->constrained('bag_products')->nullOnDelete();
            $table->foreignId('bag_product_variant_id')->nullable()->constrained('bag_product_variants')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->foreignId('unit_of_measurement_id')->constrained('units_of_measurement');
            $table->string('size_label')->nullable();
            $table->string('color_label')->nullable();
            $table->decimal('quantity', 16, 4);
            $table->decimal('unit_price', 16, 4);
            $table->decimal('unit_cost', 16, 4)->default(0);
            $table->decimal('discount_amount', 16, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('receipt_date');
            $table->decimal('amount', 16, 2);
            $table->string('payment_method', 32)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('customer_id');
        });

        Schema::create('customer_receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_receipt_id')->constrained('customer_receipts')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->decimal('amount', 16, 2);
            $table->timestamps();

            $table->unique(['customer_receipt_id', 'sales_invoice_id'], 'customer_receipt_invoice_unique');
        });

        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('return_date');
            $table->string('status', 16)->default('draft');
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('tax_total', 16, 2)->default(0);
            $table->decimal('grand_total', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('sales_invoice_line_id')->nullable()->constrained('sales_invoice_lines')->nullOnDelete();
            $table->foreignId('bag_product_variant_id')->nullable()->constrained('bag_product_variants')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('quantity', 16, 4);
            $table->string('condition', 24);
            $table->string('disposition', 24)->nullable();
            $table->decimal('unit_price', 16, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->text('inspection_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
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

            $table->index(['customer_id', 'entry_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('customer_receipt_allocations');
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('sales_invoice_lines');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('customers');
    }
};