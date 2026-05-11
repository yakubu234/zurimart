<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('email');
            $table->string('title');
            $table->text('message');
            $table->enum('status', ['queued', 'sent', 'seen', 'failed'])->default('queued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
