<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DailyReportRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_and_inventory_have_separate_routes(): void
    {
        $this->assertTrue(Route::has('daily-reports.index'));
        $this->assertTrue(Route::has('daily-reports.update'));
        $this->assertTrue(Route::has('inventory.index'));
        $this->assertTrue(Route::has('inventory.movements.store'));
    }

    public function test_daily_report_and_inventory_permissions_coexist_with_admin_assignments(): void
    {
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-daily-reports']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-all-daily-reports']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-inventory']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-all-inventory']);

        $superAdminPermissions = DB::table('permission_role')
            ->join('roles', 'roles.id', '=', 'permission_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
            ->where('roles.slug', 'super_admin')
            ->pluck('permissions.slug');

        $this->assertTrue($superAdminPermissions->contains('manage-daily-reports'));
        $this->assertTrue($superAdminPermissions->contains('manage-all-daily-reports'));
        $this->assertTrue($superAdminPermissions->contains('manage-inventory'));
        $this->assertTrue($superAdminPermissions->contains('manage-all-inventory'));
    }
}
