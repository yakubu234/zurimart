<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchCapacitySlot;
use App\Models\BranchInventorySnapshot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\NotificationDispatchService;
use App\Services\BranchProductStockService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AcceptedOrderEditReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_an_accepted_order_releases_resources_and_returns_it_to_review(): void
    {
        [$workflow, $branch, $originalProduct, $replacementProduct, $user] = $this->context();
        $order = $workflow->createOrder($this->payload($branch, $user, [$originalProduct->id => 20]));

        $workflow->acceptOrder($order);

        $this->assertSame('accepted', $order->fresh()->status);
        $this->assertSame(80, $originalProduct->fresh()->stock_units);
        $this->assertSame(20, $this->lockedUnits($branch));
        $this->assertSame('overly_booked', $branch->fresh()->status);

        $workflow->updateOrder(
            $order,
            $this->payload($branch, $user, [$replacementProduct->id => 5], 'Edited after approval')
        );

        $editedOrder = $order->fresh('items');

        $this->assertSame('pending', $editedOrder->status);
        $this->assertNull($editedOrder->accepted_at);
        $this->assertNull($editedOrder->rejected_at);
        $this->assertNull($editedOrder->rejection_reason);
        $this->assertSame(5, $editedOrder->total_units);
        $this->assertCount(1, $editedOrder->items);
        $this->assertSame($replacementProduct->id, $editedOrder->items->first()->product_id);
        $this->assertSame(100, $originalProduct->fresh()->stock_units);
        $this->assertSame(100, $replacementProduct->fresh()->stock_units);
        $this->assertSame(0, $this->lockedUnits($branch));
        $this->assertSame('available', $branch->fresh()->status);
        $this->assertSame(0, Order::query()->where('status', 'accepted')->count());

        $workflow->acceptOrder($editedOrder);

        $this->assertSame('accepted', $editedOrder->fresh()->status);
        $this->assertNotNull($editedOrder->fresh()->accepted_at);
        $this->assertSame(100, $originalProduct->fresh()->stock_units);
        $this->assertSame(95, $replacementProduct->fresh()->stock_units);
        $this->assertSame(5, $this->lockedUnits($branch));
    }

    private function context(): array
    {
        $notifications = Mockery::mock(NotificationDispatchService::class);
        $notifications->shouldIgnoreMissing();

        $branch = Branch::query()->create([
            'code' => 'REVIEW',
            'name' => 'Review Branch',
            'manager_name' => 'Manager',
            'daily_capacity_units' => 20,
            'status' => 'available',
        ]);
        $originalProduct = $this->product('ORIGINAL', 'Original Loaf');
        $replacementProduct = $this->product('REPLACEMENT', 'Replacement Loaf');
        foreach ([$originalProduct, $replacementProduct] as $product) {
            BranchInventorySnapshot::query()->create([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'inventory_date' => now()->toDateString(),
                'closing_units' => 100,
            ]);
        }
        $user = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        return [
            new OrderWorkflowService($notifications, new BranchProductStockService()),
            $branch,
            $originalProduct,
            $replacementProduct,
            $user,
        ];
    }

    private function product(string $sku, string $name): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $name,
            'category' => 'Bread',
            'weight_grams' => 500,
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_units' => 100,
            'is_active' => true,
        ]);
    }

    private function payload(Branch $branch, User $user, array $items, string $notes = 'Test order'): array
    {
        return [
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'customer_name' => 'Review Customer',
            'customer_email' => 'review@example.com',
            'customer_phone' => '08000000000',
            'customer_type' => 'public_retailer',
            'demand_type' => 'retail',
            'scheduled_for' => now()->toDateString(),
            'notes' => $notes,
            'items' => $items,
        ];
    }

    private function lockedUnits(Branch $branch): int
    {
        return (int) BranchCapacitySlot::query()
            ->where('branch_id', $branch->id)
            ->whereDate('production_date', now()->toDateString())
            ->value('locked_units');
    }
}
