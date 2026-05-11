<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            $table->string('recipient')->nullable()->after('channel');
            $table->json('payload')->nullable()->after('message');
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('failed_at')->nullable()->after('sent_at');
            $table->text('error_message')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            $table->dropColumn(['recipient', 'payload', 'sent_at', 'failed_at', 'error_message']);
        });
    }
};
