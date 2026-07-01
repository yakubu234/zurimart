<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchInventoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_cannot_record_inventory_for_another_branch(): void
    {
        [$ownBranch, $otherBranch] = $this->branches();
        $user = $this->branchManager($ownBranch);
        $material = RawMaterial::query()->create([
            'code' => 'FLOUR',
            'name' => 'Flour',
            'unit' => 'kg',
            'low_stock_threshold' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('inventory.movements.store', absolute: false), [
                'branch_id' => $otherBranch->id,
                'raw_material_id' => $material->id,
                'movement_type' => 'received',
                'quantity' => 20,
                'movement_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('raw_material_movements', 0);
    }

    public function test_super_admin_can_record_inventory_for_any_branch(): void
    {
        [, $otherBranch] = $this->branches();
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $material = RawMaterial::query()->create([
            'code' => 'SUGAR',
            'name' => 'Sugar',
            'unit' => 'kg',
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('inventory.movements.store', absolute: false), [
                'branch_id' => $otherBranch->id,
                'raw_material_id' => $material->id,
                'movement_type' => 'received',
                'quantity' => 20,
                'movement_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('inventory.index', ['branch_id' => $otherBranch->id], false));

        $this->assertDatabaseHas('raw_material_movements', [
            'branch_id' => $otherBranch->id,
            'raw_material_id' => $material->id,
            'movement_type' => 'received',
        ]);
    }

    public function test_branch_user_cannot_update_another_branches_daily_report(): void
    {
        [$ownBranch, $otherBranch] = $this->branches();
        $user = $this->branchManager($ownBranch);
        $product = $this->product();

        $this->actingAs($user)
            ->put(route('daily-reports.update', absolute: false), [
                'branch_id' => $otherBranch->id,
                'report_date' => now()->toDateString(),
                'rows' => [
                    $product->id => [
                        'opening_units' => 10,
                        'produced_units' => 2,
                        'sold_units' => 1,
                        'adjustment_units' => 0,
                    ],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('branch_inventory_snapshots', 0);
    }

    public function test_super_admin_can_update_any_branches_daily_report(): void
    {
        [, $otherBranch] = $this->branches();
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $product = $this->product();

        $this->actingAs($admin)
            ->put(route('daily-reports.update', absolute: false), [
                'branch_id' => $otherBranch->id,
                'report_date' => now()->toDateString(),
                'rows' => [
                    $product->id => [
                        'opening_units' => 10,
                        'produced_units' => 2,
                        'sold_units' => 1,
                        'adjustment_units' => 0,
                    ],
                ],
            ])
            ->assertRedirect(route('daily-reports.index', [
                'branch_id' => $otherBranch->id,
                'report_date' => now()->toDateString(),
            ], false));

        $this->assertDatabaseHas('branch_inventory_snapshots', [
            'branch_id' => $otherBranch->id,
            'product_id' => $product->id,
            'closing_units' => 11,
        ]);
    }

    private function branches(): array
    {
        return [
            Branch::query()->create([
                'code' => 'BR-A',
                'name' => 'Branch A',
                'manager_name' => 'Manager A',
                'daily_capacity_units' => 1000,
                'status' => 'available',
            ]),
            Branch::query()->create([
                'code' => 'BR-B',
                'name' => 'Branch B',
                'manager_name' => 'Manager B',
                'daily_capacity_units' => 1000,
                'status' => 'available',
            ]),
        ];
    }

    private function branchManager(Branch $branch): User
    {
        return User::factory()->create([
            'role' => 'production_branch_manager',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
    }

    private function product(): Product
    {
        return Product::query()->create([
            'sku' => 'TEST-LOAF',
            'name' => 'Test Loaf',
            'category' => 'Bread',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_units' => 100,
            'is_active' => true,
        ]);
    }
}
