<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationRecipientRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_delivery_can_be_paused_while_manual_emails_receive_all_nine_events(): void
    {
        Mail::fake();
        $this->setNotificationSettings([
            'notifications.email_enabled' => true,
            'notifications.whatsapp_enabled' => false,
            'notifications.branch_recipients_enabled' => false,
            'notifications.manual_email_recipients' => "owner@example.com\nops@example.com, OWNER@example.com",
        ]);

        $branch = $this->createBranch();
        User::factory()->create([
            'branch_id' => $branch->id,
            'role' => 'production_branch_manager',
            'status' => 'active',
            'email' => 'branch-user@example.com',
        ]);
        $order = $this->createOrder($branch);
        $notifications = app(NotificationDispatchService::class);

        $notifications->notifyBranch($branch, $order, 'Branch alert', 'Branch message', 'order_placed');

        $this->assertDatabaseCount('system_notifications', 0);

        $events = [
            'order_placed',
            'order_accepted',
            'order_rejected',
            'low_stock',
            'raw_material_low_stock',
            'branch_overbooked',
            'opening_stock',
            'closing_stock',
            'stale_stock',
        ];

        foreach ($events as $event) {
            $notifications->notifyAdmins('Test alert', 'Test message', $event, $order, $branch);
        }

        $this->assertDatabaseCount('system_notifications', 18);
        $this->assertSame(
            ['ops@example.com', 'owner@example.com'],
            SystemNotification::query()->distinct()->orderBy('recipient')->pluck('recipient')->all()
        );

        foreach ($events as $event) {
            $this->assertSame(2, SystemNotification::query()->where('event_key', $event)->count());
        }
    }

    public function test_raw_material_alert_respects_branch_pause_but_still_reaches_manual_emails(): void
    {
        Mail::fake();
        $this->setNotificationSettings([
            'notifications.email_enabled' => true,
            'notifications.whatsapp_enabled' => false,
            'notifications.branch_recipients_enabled' => false,
            'notifications.manual_email_recipients' => 'inventory@example.com',
        ]);

        $branch = $this->createBranch();
        User::factory()->create([
            'branch_id' => $branch->id,
            'role' => 'production_branch_manager',
            'status' => 'active',
            'email' => 'branch-user@example.com',
        ]);
        $material = RawMaterial::query()->create([
            'code' => 'FLOUR',
            'name' => 'Flour',
            'unit' => 'kg',
            'low_stock_threshold' => 10,
            'is_active' => true,
        ]);

        app(NotificationDispatchService::class)->notifyRawMaterialLowStock($material, $branch, 5);

        $this->assertDatabaseCount('system_notifications', 1);
        $this->assertDatabaseHas('system_notifications', [
            'event_key' => 'raw_material_low_stock',
            'channel' => 'email',
            'recipient' => 'inventory@example.com',
        ]);
        $this->assertDatabaseMissing('system_notifications', ['recipient' => $branch->email]);
        $this->assertDatabaseMissing('system_notifications', ['recipient' => 'branch-user@example.com']);
    }

    public function test_settings_normalize_and_validate_the_manual_email_list(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'retail_minimum_units' => 1,
            'wholesale_minimum_units' => 50,
            'branch_recipients_enabled' => 0,
            'manual_email_recipients' => [
                'OWNER@example.com',
                'ops@example.com',
                'owner@example.com',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('app_settings', [
            'key' => 'notifications.branch_recipients_enabled',
            'value' => '0',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'notifications.manual_email_recipients',
            'value' => "owner@example.com\nops@example.com",
        ]);

        $this->from(route('settings.edit'))->put(route('settings.update'), [
            'retail_minimum_units' => 1,
            'wholesale_minimum_units' => 50,
            'manual_email_recipients' => ['not-an-email'],
        ])->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('manual_email_recipients.0');
    }

    private function setNotificationSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            AppSetting::query()->create([
                'group' => 'notifications',
                'key' => $key,
                'value' => $value,
                'is_encrypted' => false,
            ]);
        }
    }

    private function createBranch(): Branch
    {
        return Branch::query()->create([
            'code' => 'BR-1',
            'name' => 'Branch One',
            'manager_name' => 'Branch Manager',
            'email' => 'branch@example.com',
            'phone' => '+2348000000000',
            'daily_capacity_units' => 100,
            'status' => 'available',
        ]);
    }

    private function createOrder(Branch $branch): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-TEST-1',
            'branch_id' => $branch->id,
            'customer_name' => 'Test Customer',
            'customer_type' => 'public_retailer',
            'demand_type' => 'retail',
            'pricing_tier' => 'retail',
            'status' => 'pending',
            'scheduled_for' => now()->toDateString(),
        ]);
    }
}
