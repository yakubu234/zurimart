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
            'edit-inventory-movements' => [
                'name' => 'Edit Inventory Activity',
                'description' => 'Correct previously recorded raw-material inventory activity.',
            ],
            'delete-inventory-movements' => [
                'name' => 'Delete Inventory Activity',
                'description' => 'Permanently delete previously recorded raw-material inventory activity.',
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
            $superAdminRoleId = DB::table('roles')->where('slug', 'super_admin')->value('id');

            if ($permissionId && $superAdminRoleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $superAdminRoleId, 'permission_id' => $permissionId],
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
            ->whereIn('slug', ['edit-inventory-movements', 'delete-inventory-movements'])
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
