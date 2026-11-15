<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measurement', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('symbol', 16)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('production_halls', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('machine_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 32)->unique();
            $table->string('hex_color', 16)->nullable();
            $table->string('reference_color')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('material_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->foreignId('machine_type_id')->constrained('machine_types');
            $table->foreignId('production_hall_id')->constrained('production_halls');
            $table->decimal('capacity', 12, 3)->nullable();
            $table->foreignId('capacity_unit_id')->nullable()->constrained('units_of_measurement')->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('material_categories');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('machine_types');
        Schema::dropIfExists('production_halls');
        Schema::dropIfExists('units_of_measurement');
    }
};