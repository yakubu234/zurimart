<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\RawMaterial;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\NotificationDispatchService;
use App\Services\RawMaterialInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class RawMaterialInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_received_and_used_movements_calculate_branch_balance(): void
    {
        [$service, $branch, $material, $user] = $this->inventoryContext();

        $service->recordMovement($branch, $material, $user, $this->movement('received', 25));
        $service->recordMovement($branch, $material, $user, $this->movement('used', 7.5));

        $row = $service->stockRows($branch)->first();

        $this->assertSame(17.5, $row['balance']);
        $this->assertFalse($row['is_low']);
    }

    public function test_usage_cannot_exceed_available_branch_stock(): void
    {
        [$service, $branch, $material, $user] = $this->inventoryContext();
        $service->recordMovement($branch, $material, $user, $this->movement('received', 5));

        try {
            $service->recordMovement($branch, $material, $user, $this->movement('used', 6));
            $this->fail('Using more than the available stock should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertDatabaseCount('raw_material_movements', 1);
    }

    public function test_low_stock_alert_is_sent_when_balance_crosses_threshold(): void
    {
        $notifications = Mockery::mock(NotificationDispatchService::class);
        $notifications->shouldReceive('notifyRawMaterialLowStock')->once();
        $this->app->instance(NotificationDispatchService::class, $notifications);

        [$service, $branch, $material, $user] = $this->inventoryContext();
        app(AppSettingsService::class)->setMany('notifications', [
            'notifications.event_raw_material_low_stock' => true,
        ]);

        $service->recordMovement($branch, $material, $user, $this->movement('received', 20));
        $service->recordMovement($branch, $material, $user, $this->movement('used', 10));
    }

    private function inventoryContext(): array
    {
        $branch = Branch::query()->create([
            'code' => 'STORE-1',
            'name' => 'Main Store',
            'manager_name' => 'Store Manager',
            'daily_capacity_units' => 1000,
            'status' => 'available',
        ]);
        $user = User::factory()->create([
            'role' => 'production_branch_manager',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        $material = RawMaterial::query()->create([
            'code' => 'FLOUR',
            'name' => 'Flour',
            'unit' => 'kg',
            'low_stock_threshold' => 10,
            'is_active' => true,
        ]);

        return [app(RawMaterialInventoryService::class), $branch, $material, $user];
    }

    private function movement(string $type, float $quantity): array
    {
        return [
            'movement_type' => $type,
            'quantity' => $quantity,
            'movement_date' => now()->toDateString(),
            'notes' => null,
        ];
    }
}
