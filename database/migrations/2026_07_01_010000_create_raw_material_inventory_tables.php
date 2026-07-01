<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('unit', 50);
            $table->decimal('low_stock_threshold', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('raw_material_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('movement_date');
            $table->enum('movement_type', ['received', 'used']);
            $table->decimal('quantity', 14, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'raw_material_id', 'movement_date'], 'raw_material_branch_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_movements');
        Schema::dropIfExists('raw_materials');
    }
};
