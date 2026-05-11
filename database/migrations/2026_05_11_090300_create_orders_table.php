<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('customer_type', ['public_retailer', 'internal_outlet', 'whole_marketer']);
            $table->enum('demand_type', ['retail', 'wholesale']);
            $table->enum('pricing_tier', ['retail', 'wholesale']);
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed'])->default('pending');
            $table->date('scheduled_for');
            $table->unsignedInteger('total_units')->default(0);
            $table->unsignedInteger('total_weight_grams')->default(0);
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
