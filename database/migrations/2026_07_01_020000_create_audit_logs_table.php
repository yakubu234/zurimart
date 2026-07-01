<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->string('auditable_type');
            $table->string('auditable_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'view-audit-trail'],
            [
                'name' => 'View Audit Trail',
                'group' => 'administration',
                'description' => 'View the system-wide history of created, updated, and deleted records.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'view-audit-trail')->value('id');
        $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

        if ($permissionId && $superAdminRoleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $superAdminRoleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionId = DB::table('permissions')->where('slug', 'view-audit-trail')->value('id');

            if ($permissionId && Schema::hasTable('permission_role')) {
                DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            }

            if ($permissionId && Schema::hasTable('permission_user')) {
                DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            }

            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('audit_logs');
    }
};
