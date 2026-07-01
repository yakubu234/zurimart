<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_changes_record_actor_and_before_after_values(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin);

        $material = RawMaterial::query()->create([
            'code' => 'FLOUR',
            'name' => 'Flour',
            'unit' => 'kg',
            'low_stock_threshold' => 10,
            'is_active' => true,
        ]);

        $createdLog = AuditLog::query()
            ->where('auditable_type', RawMaterial::class)
            ->where('auditable_id', $material->id)
            ->where('action', 'created')
            ->firstOrFail();

        $this->assertSame($admin->id, $createdLog->user_id);
        $this->assertSame('Flour', $createdLog->new_values['name']);

        $material->update(['name' => 'Premium Flour']);

        $updatedLog = AuditLog::query()
            ->where('auditable_type', RawMaterial::class)
            ->where('auditable_id', $material->id)
            ->where('action', 'updated')
            ->firstOrFail();

        $this->assertSame('Flour', $updatedLog->old_values['name']);
        $this->assertSame('Premium Flour', $updatedLog->new_values['name']);

        $material->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => RawMaterial::class,
            'auditable_id' => (string) $material->id,
            'action' => 'deleted',
            'subject_label' => 'Premium Flour',
        ]);
    }

    public function test_passwords_and_setting_secrets_are_redacted(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin);

        $admin->update(['password' => 'a-new-secret-password']);

        $userLog = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $admin->id)
            ->where('action', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('[REDACTED]', $userLog->old_values['password']);
        $this->assertSame('[REDACTED]', $userLog->new_values['password']);

        $setting = AppSetting::query()->create([
            'group' => 'notifications',
            'key' => 'notifications.whatsapp_token',
            'value' => 'should-never-appear',
            'is_encrypted' => false,
        ]);
        $settingLog = AuditLog::query()
            ->where('auditable_type', AppSetting::class)
            ->where('auditable_id', $setting->id)
            ->where('action', 'created')
            ->firstOrFail();

        $this->assertSame('[REDACTED]', $settingLog->new_values['value']);
        $this->assertStringNotContainsString('should-never-appear', json_encode($settingLog->new_values));
    }

    public function test_audit_page_is_restricted_to_authorized_admins(): void
    {
        $branch = Branch::query()->create([
            'code' => 'BR-1',
            'name' => 'Branch One',
            'manager_name' => 'Manager',
            'daily_capacity_units' => 1000,
            'status' => 'available',
        ]);
        $branchManager = User::factory()->create([
            'role' => 'production_branch_manager',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($branchManager)
            ->get(route('audit-logs.index', absolute: false))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('audit-logs.index', absolute: false))
            ->assertOk()
            ->assertSee('System Audit Trail');
    }

    public function test_successful_login_activity_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        Event::dispatch(new Login('web', $admin, false));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'logged_in',
            'auditable_type' => User::class,
            'auditable_id' => (string) $admin->id,
        ]);
    }
}
