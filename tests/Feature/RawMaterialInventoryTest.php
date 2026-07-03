<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
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

    public function test_super_admin_can_open_catalogue_from_inventory_and_return(): void
    {
        [, $branch, $material] = $this->inventoryContext();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('inventory.index', ['branch_id' => $branch->id], false))
            ->assertOk()
            ->assertSee('Record Material Activity')
            ->assertSee('data-target="#recordMaterialModal"', false)
            ->assertSee('class="modal fade"', false)
            ->assertSee('Manage Raw Materials')
            ->assertSee(route('inventory.materials.index', absolute: false));

        $this->actingAs($admin)
            ->get(route('inventory.materials.index', absolute: false))
            ->assertOk()
            ->assertSee('Raw Material Catalogue')
            ->assertSee($material->name)
            ->assertSee('Back to Inventory')
            ->assertSee(route('inventory.index', absolute: false));
    }

    public function test_branch_manager_cannot_access_raw_material_catalogue(): void
    {
        [, $branch, , $manager] = $this->inventoryContext();

        $this->actingAs($manager)
            ->get(route('inventory.index', ['branch_id' => $branch->id], false))
            ->assertOk()
            ->assertDontSee('Manage Raw Materials');

        $this->actingAs($manager)
            ->get(route('inventory.materials.index', absolute: false))
            ->assertForbidden();
    }

    public function test_inventory_tables_have_independent_adjustable_pagination(): void
    {
        [, $branch, $material, $manager] = $this->inventoryContext();

        foreach (range(2, 12) as $number) {
            RawMaterial::query()->create([
                'code' => "MATERIAL-{$number}",
                'name' => "Material {$number}",
                'unit' => 'kg',
                'low_stock_threshold' => 5,
                'is_active' => true,
            ]);
        }

        foreach (range(1, 12) as $number) {
            RawMaterialMovement::query()->create([
                'branch_id' => $branch->id,
                'raw_material_id' => $material->id,
                'recorded_by' => $manager->id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'received',
                'quantity' => $number,
                'notes' => "Movement {$number}",
            ]);
        }

        $this->actingAs($manager)
            ->get(route('inventory.index', [
                'branch_id' => $branch->id,
                'stock_per_page' => 10,
                'stock_page' => 2,
                'activity_per_page' => 10,
                'activity_page' => 2,
            ], false))
            ->assertOk()
            ->assertViewHas('allStockRows', fn ($rows) => $rows->count() === 12)
            ->assertViewHas('stockRows', fn ($rows) => $rows->count() === 2
                && $rows->total() === 12
                && $rows->currentPage() === 2)
            ->assertViewHas('recentMovements', fn ($rows) => $rows->count() === 2
                && $rows->total() === 12
                && $rows->currentPage() === 2)
            ->assertSee('name="stock_per_page"', false)
            ->assertSee('name="activity_per_page"', false);
    }

    public function test_only_users_with_assigned_permissions_can_edit_or_delete_inventory_activity(): void
    {
        [$service, $branch, $material, $manager] = $this->inventoryContext();
        $movement = $service->recordMovement($branch, $material, $manager, $this->movement('received', 10));

        $this->actingAs($manager)
            ->put(route('inventory.movements.update', $movement, false), [
                'raw_material_id' => $material->id,
                'movement_type' => 'received',
                'quantity' => 12,
                'movement_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('inventory.movements.destroy', $movement, false))
            ->assertForbidden();

        $manager->permissions()->attach(
            Permission::query()
                ->whereIn('slug', ['edit-inventory-movements', 'delete-inventory-movements'])
                ->pluck('id')
        );

        $this->actingAs($manager)
            ->get(route('inventory.index', ['branch_id' => $branch->id], false))
            ->assertOk()
            ->assertSee('aria-label="Edit inventory activity"', false)
            ->assertSee('aria-label="Delete inventory activity"', false);

        $this->actingAs($manager)
            ->from(route('inventory.index', ['branch_id' => $branch->id], false))
            ->put(route('inventory.movements.update', $movement, false), [
                'raw_material_id' => $material->id,
                'movement_type' => 'received',
                'quantity' => 12,
                'movement_date' => now()->toDateString(),
                'notes' => 'Corrected quantity',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('raw_material_movements', [
            'id' => $movement->id,
            'quantity' => 12,
            'notes' => 'Corrected quantity',
        ]);

        $this->actingAs($manager)
            ->from(route('inventory.index', ['branch_id' => $branch->id], false))
            ->delete(route('inventory.movements.destroy', $movement, false))
            ->assertRedirect();

        $this->assertDatabaseMissing('raw_material_movements', ['id' => $movement->id]);
    }

    public function test_editing_or_deleting_activity_cannot_create_negative_stock(): void
    {
        [$service, $branch, $material, $manager] = $this->inventoryContext();
        $receipt = $service->recordMovement($branch, $material, $manager, $this->movement('received', 10));
        $service->recordMovement($branch, $material, $manager, $this->movement('used', 6));
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->from(route('inventory.index', ['branch_id' => $branch->id], false))
            ->put(route('inventory.movements.update', $receipt, false), [
                'raw_material_id' => $material->id,
                'movement_type' => 'received',
                'quantity' => 5,
                'movement_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('movement');

        $this->assertDatabaseHas('raw_material_movements', [
            'id' => $receipt->id,
            'quantity' => 10,
        ]);

        $this->actingAs($admin)
            ->from(route('inventory.index', ['branch_id' => $branch->id], false))
            ->delete(route('inventory.movements.destroy', $receipt, false))
            ->assertSessionHasErrors('movement');

        $this->assertDatabaseHas('raw_material_movements', ['id' => $receipt->id]);
    }

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
