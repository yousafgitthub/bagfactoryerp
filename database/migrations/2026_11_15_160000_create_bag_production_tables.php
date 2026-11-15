<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bag_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('bag_type', 64)->nullable();
            $table->string('size_label', 64)->nullable();
            $table->decimal('width_mm', 10, 2)->nullable();
            $table->decimal('length_mm', 10, 2)->nullable();
            $table->decimal('thickness_micron', 10, 2)->nullable();
            $table->decimal('nominal_weight_per_bag_g', 10, 4)->nullable();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->foreignId('unit_of_measurement_id')->constrained('units_of_measurement');
            $table->decimal('selling_price', 14, 4)->default(0);
            $table->string('status', 32)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('bag_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_product_id')->constrained('bag_products')->cascadeOnDelete();
            $table->string('variant_code', 32);
            $table->string('name');
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->decimal('weight_per_bag_g', 10, 4)->nullable();
            $table->foreignId('saleable_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('saleable_material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->foreignId('rejected_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('rejected_material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('selling_price', 14, 4)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['bag_product_id', 'variant_code']);
        });

        Schema::create('bag_bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32);
            $table->string('name');
            $table->foreignId('bag_product_id')->constrained('bag_products')->cascadeOnDelete();
            $table->foreignId('bag_product_variant_id')->nullable()->constrained('bag_product_variants')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['bag_product_id', 'bag_product_variant_id', 'version'], 'bag_bom_product_variant_version_unique');
        });

        Schema::create('bag_bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_bill_of_material_id')->constrained('bag_bill_of_materials')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->string('consumption_unit', 16)->default('per_bag');
            $table->decimal('quantity_per_bag', 14, 6);
            $table->decimal('wastage_percent', 8, 4)->default(0);
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('bag_production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->string('status', 32)->default('draft');
            $table->foreignId('bag_product_id')->constrained('bag_products');
            $table->foreignId('bag_product_variant_id')->constrained('bag_product_variants');
            $table->foreignId('bag_bill_of_material_id')->nullable()->constrained('bag_bill_of_materials')->nullOnDelete();
            $table->unsignedSmallInteger('bom_version_snapshot')->nullable();
            $table->decimal('planned_quantity', 14, 4);
            $table->decimal('produced_quantity', 14, 4)->default(0);
            $table->decimal('rejected_quantity', 14, 4)->default(0);
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->foreignId('production_hall_id')->nullable()->constrained('production_halls')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->foreignId('finished_goods_warehouse_id')->constrained('warehouses');
            $table->foreignId('dana_warehouse_id')->constrained('warehouses');
            $table->foreignId('materials_warehouse_id')->constrained('warehouses');
            $table->foreignId('rejected_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('waste_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'planned_start_date']);
        });

        Schema::create('bag_production_order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_production_order_id')->constrained('bag_production_orders')->cascadeOnDelete();
            $table->string('employee_name');
            $table->string('role', 64)->nullable();
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('bag_production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 32)->unique();
            $table->foreignId('bag_production_order_id')->constrained('bag_production_orders')->cascadeOnDelete();
            $table->date('production_date');
            $table->string('status', 32)->default('draft');
            $table->foreignId('machine_id')->constrained('machines');
            $table->foreignId('production_hall_id')->constrained('production_halls');
            $table->foreignId('bag_bill_of_material_id')->nullable()->constrained('bag_bill_of_materials')->nullOnDelete();
            $table->unsignedSmallInteger('bom_version_snapshot')->nullable();
            $table->decimal('good_quantity', 14, 4)->default(0);
            $table->decimal('rejected_quantity', 14, 4)->default(0);
            $table->decimal('dana_consumed_kg', 14, 4)->default(0);
            $table->decimal('color_material_consumed_kg', 14, 4)->default(0);
            $table->decimal('actual_weight_per_good_bag_g', 10, 4)->nullable();
            $table->decimal('reusable_waste_kg', 14, 4)->default(0);
            $table->decimal('scrap_waste_kg', 14, 4)->default(0);
            $table->decimal('process_loss_kg', 14, 4)->default(0);
            $table->decimal('electricity_cost', 14, 4)->default(0);
            $table->decimal('other_overhead_cost', 14, 4)->default(0);
            $table->decimal('direct_labor_cost', 14, 4)->default(0);
            $table->decimal('material_cost', 14, 4)->default(0);
            $table->decimal('dana_cost', 14, 4)->default(0);
            $table->decimal('color_material_cost', 14, 4)->default(0);
            $table->decimal('total_batch_cost', 16, 4)->default(0);
            $table->decimal('cost_per_good_bag', 14, 4)->default(0);
            $table->foreignId('finished_goods_warehouse_id')->constrained('warehouses');
            $table->foreignId('dana_warehouse_id')->constrained('warehouses');
            $table->foreignId('materials_warehouse_id')->constrained('warehouses');
            $table->foreignId('rejected_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('waste_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('reusable_waste_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('scrap_waste_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->decimal('production_time_hours', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'production_date']);
            $table->index(['machine_id', 'posted_at']);
        });

        Schema::create('bag_batch_material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_production_batch_id')->constrained('bag_production_batches')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->string('consumption_type', 16)->default('material');
            $table->decimal('quantity', 14, 4);
            $table->string('unit', 16)->default('kg');
            $table->decimal('unit_cost_snapshot', 14, 4)->default(0);
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('bag_batch_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_production_batch_id')->constrained('bag_production_batches')->cascadeOnDelete();
            $table->string('operator_name');
            $table->decimal('labor_cost', 14, 4)->default(0);
            $table->decimal('hours', 8, 2)->nullable();
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('bag_batch_wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_production_batch_id')->constrained('bag_production_batches')->cascadeOnDelete();
            $table->string('waste_type', 32);
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('quantity_kg', 14, 4);
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('inventory_transaction_id')->nullable();
            $table->timestamps();

            $table->index('recorded_at');
        });

        Schema::create('bag_batch_inventory_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_production_batch_id')->constrained('bag_production_batches')->cascadeOnDelete();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->cascadeOnDelete();
            $table->string('link_type', 32);
            $table->timestamps();
        });

        Schema::create('bag_production_machine_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->unique()->constrained('machines')->cascadeOnDelete();
            $table->decimal('max_bags_per_batch', 14, 4)->nullable();
            $table->decimal('max_process_loss_percent', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bag_production_machine_settings');
        Schema::dropIfExists('bag_batch_inventory_links');
        Schema::dropIfExists('bag_batch_wastes');
        Schema::dropIfExists('bag_batch_operators');
        Schema::dropIfExists('bag_batch_material_consumptions');
        Schema::dropIfExists('bag_production_batches');
        Schema::dropIfExists('bag_production_order_assignments');
        Schema::dropIfExists('bag_production_orders');
        Schema::dropIfExists('bag_bom_lines');
        Schema::dropIfExists('bag_bill_of_materials');
        Schema::dropIfExists('bag_product_variants');
        Schema::dropIfExists('bag_products');
    }
};