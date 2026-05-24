<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'delete-branches-with-orders'],
            [
                'name' => 'Delete Branches With Orders',
                'group' => 'production',
                'description' => 'Delete branches even when they already have related order history.',
                'is_system' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'delete-branches-with-orders')->value('id');
        $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

        if ($permissionId && $superAdminRoleId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $superAdminRoleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'delete-branches-with-orders')->value('id');

        if ($permissionId && Schema::hasTable('roles') && Schema::hasTable('permission_role')) {
            $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

            if ($superAdminRoleId) {
                DB::table('permission_role')
                    ->where('role_id', $superAdminRoleId)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }
        }

        DB::table('permissions')->where('slug', 'delete-branches-with-orders')->delete();
    }
};
