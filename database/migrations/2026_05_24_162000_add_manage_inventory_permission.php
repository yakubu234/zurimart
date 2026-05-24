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
            ['slug' => 'manage-inventory'],
            [
                'name' => 'Manage Inventory',
                'group' => 'production',
                'description' => 'Record branch opening stock, production, sales, and closing stock.',
                'is_system' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'manage-inventory')->value('id');
        $roleIds = DB::table('roles')->whereIn('slug', ['super_admin', 'production_branch_manager'])->pluck('id');

        foreach ($roleIds as $roleId) {
            if ($permissionId && $roleId) {
                DB::table('permission_role')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'manage-inventory')->value('id');

        if ($permissionId && Schema::hasTable('permission_role') && Schema::hasTable('roles')) {
            DB::table('permission_role')
                ->where('permission_id', $permissionId)
                ->delete();
        }

        DB::table('permissions')->where('slug', 'manage-inventory')->delete();
    }
};
