<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dana_production_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('output_material_id')->constrained('materials');
            $table->foreignId('output_material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->decimal('max_process_loss_percent', 8, 4)->default(5);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('dana_production_recipe_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dana_production_recipe_id')->constrained('dana_production_recipes')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->string('input_type', 16)->default('raw');
            $table->decimal('quantity_per_output_kg', 14, 6);
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('dana_production_machine_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->unique()->constrained('machines')->cascadeOnDelete();
            $table->decimal('max_output_kg_per_batch', 14, 4)->nullable();
            $table->decimal('max_process_loss_percent', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('dana_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 32)->unique();
            $table->date('production_date');
            $table->string('status', 32)->default('draft');
            $table->foreignId('machine_id')->constrained('machines');
            $table->foreignId('production_hall_id')->constrained('production_halls');
            $table->foreignId('raw_material_warehouse_id')->constrained('warehouses');
            $table->foreignId('finished_dana_warehouse_id')->constrained('warehouses');
            $table->foreignId('waste_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('output_material_id')->constrained('materials');
            $table->foreignId('output_material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->foreignId('dana_production_recipe_id')->nullable()->constrained('dana_production_recipes')->nullOnDelete();
            $table->decimal('total_input_kg', 14, 4)->default(0);
            $table->decimal('output_kg', 14, 4)->default(0);
            $table->decimal('reusable_waste_kg', 14, 4)->default(0);
            $table->decimal('scrap_waste_kg', 14, 4)->default(0);
            $table->decimal('process_loss_kg', 14, 4)->default(0);
            $table->decimal('electricity_cost', 14, 4)->default(0);
            $table->decimal('other_overhead_cost', 14, 4)->default(0);
            $table->decimal('direct_labor_cost', 14, 4)->default(0);
            $table->decimal('raw_material_cost', 14, 4)->default(0);
            $table->decimal('color_material_cost', 14, 4)->default(0);
            $table->decimal('total_batch_cost', 16, 4)->default(0);
            $table->decimal('cost_per_kg', 14, 4)->default(0);
            $table->foreignId('reusable_waste_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('scrap_waste_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_batch_id')->nullable()->constrained('dana_batches')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'production_date']);
            $table->index(['machine_id', 'posted_at']);
        });

        Schema::create('dana_batch_material_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dana_batch_id')->constrained('dana_batches')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('material_variant_id')->nullable()->constrained('material_variants')->nullOnDelete();
            $table->string('input_type', 16)->default('raw');
            $table->decimal('quantity_kg', 14, 4);
            $table->decimal('unit_cost_snapshot', 14, 4)->default(0);
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('dana_batch_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dana_batch_id')->constrained('dana_batches')->cascadeOnDelete();
            $table->string('operator_name');
            $table->decimal('labor_cost', 14, 4)->default(0);
            $table->decimal('hours', 8, 2)->nullable();
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->timestamps();
        });

        Schema::create('dana_batch_wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dana_batch_id')->constrained('dana_batches')->cascadeOnDelete();
            $table->string('waste_type', 32);
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('inventory_transaction_id')->nullable();
            $table->timestamps();

            $table->index('recorded_at');
        });

        Schema::create('dana_batch_inventory_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dana_batch_id')->constrained('dana_batches')->cascadeOnDelete();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->cascadeOnDelete();
            $table->string('link_type', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_batch_inventory_links');
        Schema::dropIfExists('dana_batch_wastes');
        Schema::dropIfExists('dana_batch_operators');
        Schema::dropIfExists('dana_batch_material_inputs');
        Schema::dropIfExists('dana_batches');
        Schema::dropIfExists('dana_production_machine_settings');
        Schema::dropIfExists('dana_production_recipe_lines');
        Schema::dropIfExists('dana_production_recipes');
    }
};