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
        $permissions = [
            'manage-inventory' => [
                'name' => 'Manage Inventory',
                'description' => 'Record raw materials received and used within an assigned branch.',
                'roles' => ['super_admin', 'production_branch_manager'],
            ],
            'manage-all-inventory' => [
                'name' => 'Manage All Inventory',
                'description' => 'Manage raw-material inventory and its catalogue across every branch.',
                'roles' => ['super_admin'],
            ],
        ];

        foreach ($permissions as $slug => $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $permission['name'],
                    'group' => 'production',
                    'description' => $permission['description'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
                continue;
            }

            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            $roleIds = DB::table('roles')->whereIn('slug', $permission['roles'])->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['manage-inventory', 'manage-all-inventory'])
            ->pluck('id');

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        }

        if (Schema::hasTable('permission_user')) {
            DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
