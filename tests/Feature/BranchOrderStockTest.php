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

    public function test_branch_stock_only_reserves_accepted_orders_for_the_selected_production_date(): void
    {
        $branch = $this->branch('DATE', 'Date Branch');
        $product = Product::query()->create([
            'sku' => 'DATE-LOAF',
            'name' => 'Date Loaf',
            'category' => 'Bread',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_units' => 10,
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        BranchInventorySnapshot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'inventory_date' => now()->toDateString(),
            'closing_units' => 5,
        ]);

        $pastOrder = Order::query()->create([
            'order_number' => 'ORD-DATE-OLD',
            'branch_id' => $branch->id,
            'customer_name' => 'Old Customer',
            'customer_type' => 'public_retailer',
            'demand_type' => 'retail',
            'pricing_tier' => 'retail',
            'status' => 'accepted',
            'scheduled_for' => now()->subDay()->toDateString(),
            'total_units' => 5,
            'total_weight_grams' => 2500,
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'total_amount' => 5000,
        ]);
        $pastOrder->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_weight_grams' => $product->weight_grams,
            'quantity' => 5,
            'unit_price' => 1000,
            'line_total' => 5000,
        ]);

        $this->actingAs($admin)
            ->get(route('orders.create', absolute: false))
            ->assertOk()
            ->assertViewHas('branchStocks', fn (array $stocks) => $stocks[$branch->id][$product->id] === 5);

        $this->actingAs($admin)
            ->post(route('orders.store', absolute: false), $this->payload($branch, $product, 5))
            ->assertRedirect();
    }

    public function test_product_catalogue_shows_today_live_stock_without_old_order_reservations(): void
    {
        $branch = $this->branch('CAT', 'Catalogue Branch');
        $product = Product::query()->create([
            'sku' => 'CAT-LOAF',
            'name' => 'Catalogue Loaf',
            'category' => 'Bread',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_units' => 0,
            'is_active' => true,
        ]);

        $pastOrder = Order::query()->create([
            'order_number' => 'ORD-CAT-OLD',
            'branch_id' => $branch->id,
            'customer_name' => 'Old Customer',
            'customer_type' => 'public_retailer',
            'demand_type' => 'retail',
            'pricing_tier' => 'retail',
            'status' => 'accepted',
            'scheduled_for' => now()->subDay()->toDateString(),
            'total_units' => 5,
            'total_weight_grams' => 2500,
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'total_amount' => 5000,
        ]);
        $pastOrder->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_weight_grams' => $product->weight_grams,
            'quantity' => 5,
            'unit_price' => 1000,
            'line_total' => 5000,
        ]);

        app(\App\Services\BranchInventoryService::class)->syncDailyInventory($branch, now()->toDateString(), [
            $product->id => [
                'opening_units' => 0,
                'produced_units' => 5,
                'sold_units' => 0,
                'adjustment_units' => 0,
            ],
        ]);

        $this->assertSame(5, $product->fresh()->stock_units);

        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('products.index', absolute: false))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => (int) $products->firstWhere('id', $product->id)->current_stock_units === 5)
            ->assertSee('5 units');
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
