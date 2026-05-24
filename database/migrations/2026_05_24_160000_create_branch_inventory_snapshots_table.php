<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('inventory_date');
            $table->integer('opening_units')->default(0);
            $table->integer('produced_units')->default(0);
            $table->integer('sold_units')->default(0);
            $table->integer('adjustment_units')->default(0);
            $table->integer('closing_units')->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'product_id', 'inventory_date'], 'bis_branch_product_date_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_inventory_snapshots');
    }
};
