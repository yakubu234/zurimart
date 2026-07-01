<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renamePermissions([
            'manage-inventory' => [
                'slug' => 'manage-daily-reports',
                'name' => 'Manage Daily Reports',
                'description' => 'Record branch opening quantities, production, sales, adjustments, and closing quantities by date.',
            ],
            'manage-all-inventory' => [
                'slug' => 'manage-all-daily-reports',
                'name' => 'Manage All Daily Reports',
                'description' => 'Access and update daily reports across every branch instead of only an assigned branch.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->renamePermissions([
            'manage-daily-reports' => [
                'slug' => 'manage-inventory',
                'name' => 'Manage Inventory',
                'description' => 'Record branch opening stock, production, sales, and closing stock.',
            ],
            'manage-all-daily-reports' => [
                'slug' => 'manage-all-inventory',
                'name' => 'Manage All Inventory',
                'description' => 'Access and update inventory sheets across every branch instead of only an assigned branch.',
            ],
        ]);
    }

    private function renamePermissions(array $permissions): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ($permissions as $oldSlug => $permission) {
            $oldPermission = DB::table('permissions')->where('slug', $oldSlug)->first();
            $newPermission = DB::table('permissions')->where('slug', $permission['slug'])->first();

            if ($oldPermission && $newPermission) {
                $this->mergeAssignments('permission_role', 'role_id', $oldPermission->id, $newPermission->id);
                $this->mergeAssignments('permission_user', 'user_id', $oldPermission->id, $newPermission->id);
                DB::table('permissions')->where('id', $oldPermission->id)->delete();
            } elseif ($oldPermission) {
                DB::table('permissions')
                    ->where('id', $oldPermission->id)
                    ->update(['slug' => $permission['slug']]);
            }

            DB::table('permissions')
                ->where('slug', $permission['slug'])
                ->update([
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'updated_at' => now(),
                ]);
        }
    }

    private function mergeAssignments(string $table, string $ownerColumn, int $oldPermissionId, int $newPermissionId): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $ownerIds = DB::table($table)
            ->where('permission_id', $oldPermissionId)
            ->pluck($ownerColumn);

        foreach ($ownerIds as $ownerId) {
            DB::table($table)->updateOrInsert(
                [
                    $ownerColumn => $ownerId,
                    'permission_id' => $newPermissionId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table($table)->where('permission_id', $oldPermissionId)->delete();
    }
};
