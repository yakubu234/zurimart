<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchCapacitySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(private readonly NotificationDispatchService $notifications)
    {
    }

    public function createOrder(array $payload): Order
    {
        return DB::transaction(function () use ($payload) {
            $summary = $this->buildOrderSummary($payload);

            $branch = Branch::query()->lockForUpdate()->findOrFail($payload['branch_id']);
            $this->assertPlacementCapacity($branch, $summary['scheduled_for'], $summary['total_units']);

            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'branch_id' => $branch->id,
                'created_by' => $payload['created_by'] ?? null,
                'customer_name' => $payload['customer_name'],
                'customer_email' => $payload['customer_email'] ?? null,
                'customer_phone' => $payload['customer_phone'] ?? null,
                'customer_type' => $payload['customer_type'],
                'demand_type' => $payload['demand_type'],
                'pricing_tier' => $summary['pricing_tier'],
                'status' => 'pending',
                'scheduled_for' => $summary['scheduled_for'],
                'total_units' => $summary['total_units'],
                'total_weight_grams' => $summary['total_weight_grams'],
                'subtotal_amount' => $summary['subtotal_amount'],
                'discount_amount' => $summary['discount_amount'],
                'total_amount' => $summary['total_amount'],
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->syncOrderItems($order, $summary['line_items'], $summary['pricing_tier']);

            if ($this->notificationsEnabledFor('notifications.event_order_placed')) {
                $message = sprintf(
                    'Order %s has been tagged to %s for %s. Review and accept or reject based on live capacity.',
                    $order->order_number,
                    $branch->name,
                    Carbon::parse($summary['scheduled_for'])->format('d M Y')
                );

                $this->notifications->notifyBranch($branch, $order, 'New tagged production order', $message, 'order_placed');
                $this->notifications->notifyAdmins('New order placed', $message, 'order_placed', $order, $branch, [
                    'demand_type' => $order->demand_type,
                    'pricing_tier' => $order->pricing_tier,
                    'total_units' => $order->total_units,
                ]);
            }

            return $order->load(['branch', 'items']);
        });
    }

    public function updateOrder(Order $order, array $payload): Order
    {
        return DB::transaction(function () use ($order, $payload) {
            $order = Order::query()->lockForUpdate()->with(['items.product', 'branch'])->findOrFail($order->id);

            if ($order->status === 'completed') {
                throw ValidationException::withMessages([
                    'order' => 'Completed orders can no longer be edited.',
                ]);
            }

            $wasAccepted = $order->status === 'accepted';

            if ($wasAccepted) {
                $this->releaseAcceptedOrderResources($order);
            }

            $summary = $this->buildOrderSummary($payload);
            $branch = Branch::query()->lockForUpdate()->findOrFail($payload['branch_id']);

            if ($wasAccepted) {
                $this->reserveAcceptedOrderResources($branch, $summary['scheduled_for'], $summary['total_units'], $summary['line_items']);
            } else {
                $this->assertPlacementCapacity($branch, $summary['scheduled_for'], $summary['total_units']);
            }

            $order->update([
                'branch_id' => $branch->id,
                'customer_name' => $payload['customer_name'],
                'customer_email' => $payload['customer_email'] ?? null,
                'customer_phone' => $payload['customer_phone'] ?? null,
                'customer_type' => $payload['customer_type'],
                'demand_type' => $payload['demand_type'],
                'pricing_tier' => $summary['pricing_tier'],
                'status' => $wasAccepted ? 'accepted' : 'pending',
                'scheduled_for' => $summary['scheduled_for'],
                'total_units' => $summary['total_units'],
                'total_weight_grams' => $summary['total_weight_grams'],
                'subtotal_amount' => $summary['subtotal_amount'],
                'discount_amount' => $summary['discount_amount'],
                'total_amount' => $summary['total_amount'],
                'notes' => $payload['notes'] ?? null,
                'rejection_reason' => null,
                'rejected_at' => null,
            ]);

            $this->syncOrderItems($order, $summary['line_items'], $summary['pricing_tier']);

            return $order->load(['branch', 'items']);
        });
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->with(['items.product', 'branch'])->findOrFail($order->id);

            if ($order->status === 'accepted') {
                $this->releaseAcceptedOrderResources($order);
            }

            $order->delete();
        });
    }

    public function acceptOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->with('branch')->findOrFail($order->id);

            if ($order->status !== 'pending' || ! $order->branch) {
                throw ValidationException::withMessages([
                    'order' => 'Only pending tagged orders can be accepted.',
                ]);
            }

            $branch = Branch::query()->lockForUpdate()->findOrFail($order->branch_id);
            $slot = BranchCapacitySlot::query()
                ->where('branch_id', $branch->id)
                ->whereDate('production_date', $order->scheduled_for)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['branch_id' => $branch->id, 'production_date' => $order->scheduled_for->toDateString()],
                    ['capacity_units' => $branch->daily_capacity_units, 'locked_units' => 0]
                );

            if ($branch->status !== 'available') {
                throw ValidationException::withMessages([
                    'order' => 'The tagged branch is marked as overly booked.',
                ]);
            }

            if (($slot->locked_units + $order->total_units) > $slot->capacity_units) {
                $branch->update(['status' => 'overly_booked']);

                throw ValidationException::withMessages([
                    'order' => 'Accepting this order would exceed oven capacity for the selected date.',
                ]);
            }

            $projectedLockedUnits = $slot->locked_units + $order->total_units;
            $slot->update(['locked_units' => $projectedLockedUnits]);

            foreach ($order->items()->with('product')->get() as $item) {
                if ($item->product && $item->product->stock_units < $item->quantity) {
                    throw ValidationException::withMessages([
                        'order' => "Insufficient stock for {$item->product_name}.",
                    ]);
                }

                if ($item->product) {
                    $item->product->decrement('stock_units', $item->quantity);
                }
            }

            if ($projectedLockedUnits >= $slot->capacity_units) {
                $branch->update(['status' => 'overly_booked']);

                if ($this->notificationsEnabledFor('notifications.event_branch_overbooked')) {
                    $this->notifications->notifyBranchOverbooked($branch, $order);
                }
            }

            $order->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'rejection_reason' => null,
            ]);

            if ($this->notificationsEnabledFor('notifications.event_order_accepted')) {
                $message = "Capacity has been locked for {$order->order_number}. Inventory and oven allocation are now reserved.";
                $this->notifications->notifyBranch($branch, $order, 'Order accepted and capacity locked', $message, 'order_accepted');
                $this->notifications->notifyAdmins('Order accepted', $message, 'order_accepted', $order, $branch);
            }

            $threshold = (int) app(AppSettingsService::class)->get('notifications.low_stock_threshold', 150);
            if ($this->notificationsEnabledFor('notifications.event_low_stock')) {
                foreach ($order->items()->with('product')->get() as $item) {
                    if ($item->product && $item->product->stock_units <= $threshold) {
                        $this->notifications->notifyLowStock($item->product, $branch, $order);
                    }
                }
            }
        });
    }

    public function rejectOrder(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            $order = Order::query()->lockForUpdate()->with('branch')->findOrFail($order->id);

            if ($order->status !== 'pending') {
                throw ValidationException::withMessages([
                    'order' => 'Only pending orders can be rejected.',
                ]);
            }

            $order->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $reason ?: 'Branch manager rejected the tagged order due to capacity planning.',
            ]);

            if ($order->branch) {
                if ($this->notificationsEnabledFor('notifications.event_order_rejected')) {
                    $message = "Order {$order->order_number} was rejected. Please tag a different available branch and resubmit.";
                    $this->notifications->notifyBranch(
                        $order->branch,
                        $order,
                        'Order rejected and ready for re-routing',
                        $message,
                        'order_rejected'
                    );
                    $this->notifications->notifyAdmins('Order rejected', $message, 'order_rejected', $order, $order->branch);
                }
            }
        });
    }

    protected function notificationsEnabledFor(string $settingKey): bool
    {
        return app(AppSettingsService::class)->bool($settingKey, true);
    }

    protected function nextOrderNumber(): string
    {
        $latestId = (int) Order::query()->max('id') + 1;

        return 'ORD-' . str_pad((string) (10240 + $latestId), 5, '0', STR_PAD_LEFT);
    }

    protected function buildOrderSummary(array $payload): array
    {
        $products = Product::query()
            ->whereIn('id', array_keys($payload['items']))
            ->get()
            ->keyBy('id');

        $lineItems = [];
        $retailSubtotal = 0;
        $wholesaleSubtotal = 0;
        $totalUnits = 0;
        $totalWeight = 0;

        foreach ($payload['items'] as $productId => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity < 1 || ! isset($products[$productId])) {
                continue;
            }

            $product = $products[$productId];
            $retailSubtotal += $quantity * (float) $product->retail_price;
            $wholesaleSubtotal += $quantity * (float) $product->wholesale_price;
            $totalUnits += $quantity;
            $totalWeight += $quantity * $product->weight_grams;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
            ];
        }

        if ($totalUnits === 0) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product quantity before submitting the order.',
            ]);
        }

        $retailMinimumUnits = max(
            1,
            (int) app(AppSettingsService::class)->get('orders.retail_minimum_units', 1)
        );
        $wholesaleMinimumUnits = max(
            $retailMinimumUnits,
            (int) app(AppSettingsService::class)->get('orders.wholesale_minimum_units', 50)
        );

        if ($totalUnits < $retailMinimumUnits) {
            throw ValidationException::withMessages([
                'items' => "A retail order must contain at least {$retailMinimumUnits} units.",
            ]);
        }

        $pricingTier = $totalUnits >= $wholesaleMinimumUnits ? 'wholesale' : 'retail';
        $subtotal = $pricingTier === 'wholesale' ? $wholesaleSubtotal : $retailSubtotal;

        return [
            'line_items' => $lineItems,
            'scheduled_for' => Carbon::parse($payload['scheduled_for'])->toDateString(),
            'pricing_tier' => $pricingTier,
            'total_units' => $totalUnits,
            'total_weight_grams' => $totalWeight,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $pricingTier === 'wholesale' ? max($retailSubtotal - $wholesaleSubtotal, 0) : 0,
            'total_amount' => $subtotal,
        ];
    }

    protected function syncOrderItems(Order $order, array $lineItems, string $pricingTier): void
    {
        $order->items()->delete();

        foreach ($lineItems as $lineItem) {
            $product = $lineItem['product'];
            $unitPrice = $pricingTier === 'wholesale' ? $product->wholesale_price : $product->retail_price;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_weight_grams' => $product->weight_grams,
                'quantity' => $lineItem['quantity'],
                'unit_price' => $unitPrice,
                'line_total' => $lineItem['quantity'] * (float) $unitPrice,
            ]);
        }
    }

    protected function releaseAcceptedOrderResources(Order $order): void
    {
        if (! $order->branch_id) {
            return;
        }

        $branch = Branch::query()->lockForUpdate()->find($order->branch_id);

        if (! $branch) {
            return;
        }

        $slot = BranchCapacitySlot::query()
            ->where('branch_id', $branch->id)
            ->whereDate('production_date', $order->scheduled_for)
            ->lockForUpdate()
            ->first();

        if ($slot) {
            $slot->update([
                'locked_units' => max(0, $slot->locked_units - $order->total_units),
            ]);

            if ($slot->locked_units < $slot->capacity_units && $branch->status === 'overly_booked') {
                $branch->update(['status' => 'available']);
            }
        }

        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_units', $item->quantity);
            }
        }
    }

    protected function reserveAcceptedOrderResources(Branch $branch, string $scheduledFor, int $totalUnits, array $lineItems): void
    {
        $slot = BranchCapacitySlot::query()
            ->where('branch_id', $branch->id)
            ->whereDate('production_date', $scheduledFor)
            ->lockForUpdate()
            ->firstOrCreate(
                ['branch_id' => $branch->id, 'production_date' => $scheduledFor],
                ['capacity_units' => $branch->daily_capacity_units, 'locked_units' => 0]
            );

        if ($branch->status !== 'available' || $slot->locked_units >= $slot->capacity_units) {
            throw ValidationException::withMessages([
                'branch_id' => 'This branch is currently unavailable for the selected production date.',
            ]);
        }

        if (($slot->locked_units + $totalUnits) > $slot->capacity_units) {
            $branch->update(['status' => 'overly_booked']);

            throw ValidationException::withMessages([
                'order' => 'Updating this order would exceed oven capacity for the selected date.',
            ]);
        }

        foreach ($lineItems as $lineItem) {
            $product = $lineItem['product'];

            if ($product->stock_units < $lineItem['quantity']) {
                throw ValidationException::withMessages([
                    'order' => "Insufficient stock for {$product->name}.",
                ]);
            }
        }

        $slot->update(['locked_units' => $slot->locked_units + $totalUnits]);

        foreach ($lineItems as $lineItem) {
            $lineItem['product']->decrement('stock_units', $lineItem['quantity']);
        }

        if ($slot->locked_units >= $slot->capacity_units) {
            $branch->update(['status' => 'overly_booked']);
        }
    }

    protected function assertPlacementCapacity(Branch $branch, string $scheduledFor, int $requestedUnits): void
    {
        $slot = BranchCapacitySlot::query()
            ->where('branch_id', $branch->id)
            ->whereDate('production_date', $scheduledFor)
            ->lockForUpdate()
            ->firstOrCreate(
                ['branch_id' => $branch->id, 'production_date' => $scheduledFor],
                ['capacity_units' => $branch->daily_capacity_units, 'locked_units' => 0]
            );

        if ($branch->status !== 'available' || $slot->locked_units >= $slot->capacity_units) {
            throw ValidationException::withMessages([
                'branch_id' => 'This branch is currently unavailable for the selected production date.',
            ]);
        }

        $remainingUnits = max(0, (int) $slot->capacity_units - (int) $slot->locked_units);

        if ($requestedUnits > $remainingUnits) {
            throw ValidationException::withMessages([
                'items' => "Only {$remainingUnits} unit(s) remain for {$branch->name} on " . Carbon::parse($scheduledFor)->format('d M Y') . '. Reduce the order quantity or choose another branch/date.',
            ]);
        }
    }
}
