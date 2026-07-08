<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BranchProductStockService
{
    /**
     * @param  array<int, int>  $branchIds
     * @param  array<int, int>  $productIds
     * @return array<int, array<int, int>>
     */
    public function stockMap(
        array $branchIds,
        array $productIds,
        ?int $excludeAcceptedOrderId = null,
        bool $lockForUpdate = false,
        ?string $stockDate = null
    ): array {
        $branchIds = collect($branchIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $productIds = collect($productIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $stockDate = Carbon::parse($stockDate ?? now())->toDateString();
        $stock = [];

        foreach ($branchIds as $branchId) {
            foreach ($productIds as $productId) {
                $stock[$branchId][$productId] = 0;
            }
        }

        if ($branchIds === [] || $productIds === []) {
            return $stock;
        }

        $snapshotQuery = BranchInventorySnapshot::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->whereDate('inventory_date', '<=', $stockDate)
            ->orderByDesc('inventory_date')
            ->orderByDesc('id');

        if ($lockForUpdate) {
            $snapshotQuery->lockForUpdate();
        }

        $latestSnapshots = $snapshotQuery
            ->get()
            ->unique(fn (BranchInventorySnapshot $snapshot) => "{$snapshot->branch_id}:{$snapshot->product_id}");

        foreach ($latestSnapshots as $snapshot) {
            $stock[(int) $snapshot->branch_id][(int) $snapshot->product_id] = max(0, (int) $snapshot->closing_units);
        }

        $reserved = OrderItem::query()
            ->selectRaw('orders.branch_id, order_items.product_id, SUM(order_items.quantity) as reserved_units')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'accepted')
            ->whereDate('orders.scheduled_for', $stockDate)
            ->whereIn('orders.branch_id', $branchIds)
            ->whereIn('order_items.product_id', $productIds)
            ->when($excludeAcceptedOrderId, fn ($query, $orderId) => $query->where('orders.id', '!=', $orderId))
            ->groupBy('orders.branch_id', 'order_items.product_id')
            ->get();

        foreach ($reserved as $row) {
            $branchId = (int) $row->branch_id;
            $productId = (int) $row->product_id;
            $stock[$branchId][$productId] = max(
                0,
                ($stock[$branchId][$productId] ?? 0) - (int) $row->reserved_units
            );
        }

        return $stock;
    }

    /**
     * @param  array<int, array{product: mixed, quantity: int}>  $lineItems
     */
    public function assertAvailable(
        Branch $branch,
        array $lineItems,
        ?int $excludeAcceptedOrderId = null,
        bool $lockForUpdate = true,
        ?string $stockDate = null
    ): void {
        $productIds = collect($lineItems)
            ->pluck('product.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $stock = $this->stockMap([$branch->id], $productIds, $excludeAcceptedOrderId, $lockForUpdate, $stockDate);

        foreach ($lineItems as $lineItem) {
            $product = $lineItem['product'];
            $available = (int) ($stock[$branch->id][$product->id] ?? 0);

            if ((int) $lineItem['quantity'] > $available) {
                throw ValidationException::withMessages([
                    "items.{$product->id}" => "Only {$available} unit(s) of {$product->name} are available at {$branch->name}.",
                ]);
            }
        }
    }
}
