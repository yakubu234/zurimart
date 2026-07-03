<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchOrderStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_form_and_workflow_use_stock_from_the_selected_branch(): void
    {
        $branchA = $this->branch('A', 'Branch A');
        $branchB = $this->branch('B', 'Branch B');
        $product = Product::query()->create([
            'sku' => 'BRANCH-LOAF',
            'name' => 'Branch Loaf',
            'category' => 'Bread',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_units' => 15,
            'is_active' => true,
        ]);
        $this->snapshot($branchA, $product, 5);
        $this->snapshot($branchB, $product, 10);
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('orders.create', absolute: false))
            ->assertOk()
            ->assertViewHas('branchStocks', fn (array $stocks) => $stocks[$branchA->id][$product->id] === 5
                && $stocks[$branchB->id][$product->id] === 10)
            ->assertSee('Branch Stock')
            ->assertSee('class="branch-stock-value"', false);

        $this->actingAs($admin)
            ->from(route('orders.create', absolute: false))
            ->post(route('orders.store', absolute: false), $this->payload($branchA, $product, 6))
            ->assertSessionHasErrors("items.{$product->id}");

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($admin)
            ->post(route('orders.store', absolute: false), $this->payload($branchB, $product, 6))
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        app(OrderWorkflowService::class)->acceptOrder($order);

        $this->assertSame(9, $product->fresh()->stock_units);

        $this->actingAs($admin)
            ->from(route('orders.create', absolute: false))
            ->post(route('orders.store', absolute: false), $this->payload($branchB, $product, 5))
            ->assertSessionHasErrors("items.{$product->id}");

        $this->actingAs($admin)
            ->post(route('orders.store', absolute: false), $this->payload($branchA, $product, 5))
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 2);
    }

    private function branch(string $code, string $name): Branch
    {
        return Branch::query()->create([
            'code' => $code,
            'name' => $name,
            'manager_name' => 'Manager',
            'daily_capacity_units' => 100,
            'status' => 'available',
        ]);
    }

    private function snapshot(Branch $branch, Product $product, int $closingUnits): void
    {
        BranchInventorySnapshot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'inventory_date' => now()->toDateString(),
            'closing_units' => $closingUnits,
        ]);
    }

    private function payload(Branch $branch, Product $product, int $quantity): array
    {
        return [
            'branch_id' => $branch->id,
            'customer_name' => 'Branch Customer',
            'customer_email' => 'branch@example.com',
            'customer_phone' => '08000000000',
            'customer_type' => 'public_retailer',
            'demand_type' => 'retail',
            'scheduled_for' => now()->toDateString(),
            'notes' => null,
            'items' => [$product->id => $quantity],
        ];
    }
}
