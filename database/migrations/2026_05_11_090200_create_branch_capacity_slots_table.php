<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_capacity_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('production_date');
            $table->unsignedInteger('capacity_units');
            $table->unsignedInteger('locked_units')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'production_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_capacity_slots');
    }
};
