<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('material_category_id')->constrained('material_categories');
            $table->foreignId('unit_of_measurement_id')->constrained('units_of_measurement');
            $table->decimal('default_unit_cost', 14, 4)->default(0);
            $table->decimal('reorder_level', 14, 4)->default(0);
            $table->string('inventory_bucket', 32)->default('raw');
            $table->boolean('supports_variants')->default(false);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('material_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->string('variant_code', 32);
            $table->string('name');
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['material_id', 'variant_code']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 32)->unique();
            $table->string('type', 48);
            $table->string('status', 16)->default('draft');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('transaction_date');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('reversal_of_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['transaction_date', 'status']);
        });

        Schema::create('inventory_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_value', 16, 4)->default(0);
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('lot_number', 64);
            $table->timestamp('received_at');
            $table->decimal('quantity_received', 14, 4);
            $table->decimal('quantity_remaining', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->foreignId('source_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['warehouse_id', 'material_id', 'material_variant_id', 'lot_number'], 'stock_lots_unique');
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->string('bucket', 32)->default('raw');
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('average_unit_cost', 14, 4)->default(0);
            $table->decimal('total_value', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(
                ['warehouse_id', 'material_id', 'material_variant_id'],
                'inventory_balances_location_unique',
            );
        });

        Schema::create('inventory_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions');
            $table->foreignId('inventory_transaction_item_id')->nullable()->constrained('inventory_transaction_items')->nullOnDelete();
            $table->string('movement_type', 32);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_value', 16, 4)->default(0);
            $table->timestamp('occurred_at');
            $table->string('reference', 64)->nullable();
            $table->foreignId('stock_lot_id')->nullable()->constrained('stock_lots')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['material_id', 'occurred_at']);
            $table->index(['warehouse_id', 'occurred_at']);
            $table->index('movement_type');
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->unique()->constrained('inventory_transactions')->cascadeOnDelete();
            $table->string('reason');
            $table->string('direction', 16);
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->unique()->constrained('inventory_transactions')->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('inventory_ledger_entries');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('stock_lots');
        Schema::dropIfExists('inventory_transaction_items');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('material_variants');
        Schema::dropIfExists('materials');
    }
};