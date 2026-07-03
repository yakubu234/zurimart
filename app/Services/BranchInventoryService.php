<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\BranchStockBatch;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchInventoryService
{
    public function __construct(
        private readonly NotificationDispatchService $notifications,
        private readonly AppSettingsService $settings,
    ) {
    }

    public function syncDailyInventory(Branch $branch, string $inventoryDate, array $rows): void
    {
        DB::transaction(function () use ($branch, $inventoryDate, $rows) {
            $date = Carbon::parse($inventoryDate)->toDateString();
            $threshold = (int) $this->settings->get('notifications.low_stock_threshold', 150);

            foreach ($rows as $productId => $payload) {
                $product = Product::query()->find($productId);

                if (! $product) {
                    continue;
                }

                $openingUnits = max(0, (int) ($payload['opening_units'] ?? 0));
                $producedUnits = max(0, (int) ($payload['produced_units'] ?? 0));
                $soldUnits = max(0, (int) ($payload['sold_units'] ?? 0));
                $adjustmentUnits = (int) ($payload['adjustment_units'] ?? 0);
                $closingUnits = max(0, $openingUnits + $producedUnits + $adjustmentUnits - $soldUnits);

                $snapshot = BranchInventorySnapshot::query()->firstOrNew([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'inventory_date' => $date,
                ]);

                $previousClosingUnits = $snapshot->exists ? (int) $snapshot->closing_units : null;

                $snapshot->fill([
                    'opening_units' => $openingUnits,
                    'produced_units' => $producedUnits,
                    'sold_units' => $soldUnits,
                    'adjustment_units' => $adjustmentUnits,
                    'closing_units' => $closingUnits,
                ]);
                $snapshot->save();

                $this->rebuildBatchesForProduct($branch->id, $product->id);

                if (($previousClosingUnits === null || $previousClosingUnits > $threshold) && $closingUnits <= $threshold) {
                    $this->notifications->notifyLowStock($product, $branch, null);
                }
            }

            $this->syncAggregateProductStocks();
        });
    }

    public function rowsForDate(Branch $branch, string $inventoryDate): Collection
    {
        $date = Carbon::parse($inventoryDate)->toDateString();
        $snapshots = BranchInventorySnapshot::query()
            ->where('branch_id', $branch->id)
            ->whereDate('inventory_date', $date)
            ->get()
            ->keyBy('product_id');

        return Product::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($snapshots) {
                $snapshot = $snapshots->get($product->id);

                return [
                    'product' => $product,
                    'opening_units' => $snapshot?->opening_units ?? 0,
                    'produced_units' => $snapshot?->produced_units ?? 0,
                    'sold_units' => $snapshot?->sold_units ?? 0,
                    'adjustment_units' => $snapshot?->adjustment_units ?? 0,
                    'closing_units' => $snapshot?->closing_units ?? 0,
                ];
            });
    }

    public function openingSummary(string $inventoryDate): array
    {
        $date = Carbon::parse($inventoryDate)->toDateString();

        $branches = Branch::query()
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($date) {
                $total = (int) BranchInventorySnapshot::query()
                    ->where('branch_id', $branch->id)
                    ->whereDate('inventory_date', $date)
                    ->sum('opening_units');

                return [
                    'branch' => $branch,
                    'total_units' => $total,
                ];
            });

        return [
            'date' => $date,
            'branches' => $branches,
            'combined_total' => (int) $branches->sum('total_units'),
        ];
    }

    public function closingSummary(string $inventoryDate): array
    {
        $date = Carbon::parse($inventoryDate)->toDateString();

        $branches = Branch::query()
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($date) {
                $total = (int) BranchInventorySnapshot::query()
                    ->where('branch_id', $branch->id)
                    ->whereDate('inventory_date', $date)
                    ->sum('closing_units');

                return [
                    'branch' => $branch,
                    'total_units' => $total,
                ];
            });

        return [
            'date' => $date,
            'branches' => $branches,
            'combined_total' => (int) $branches->sum('total_units'),
        ];
    }

    public function staleStockSummary(Carbon $now): array
    {
        $thresholdDate = $now->copy()->subHours(72)->toDateString();
        $staleBatches = BranchStockBatch::query()
            ->with(['branch', 'product'])
            ->where('remaining_units', '>', 0)
            ->whereDate('produced_date', '<=', $thresholdDate)
            ->orderBy('produced_date')
            ->get();

        $branches = Branch::query()
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) {
                $latestDate = BranchInventorySnapshot::query()
                    ->where('branch_id', $branch->id)
                    ->max('inventory_date');

                $total = $latestDate
                    ? (int) BranchInventorySnapshot::query()
                        ->where('branch_id', $branch->id)
                        ->whereDate('inventory_date', $latestDate)
                        ->sum('closing_units')
                    : 0;

                return [
                    'branch' => $branch,
                    'total_units' => $total,
                ];
            });

        return [
            'stale_batches' => $staleBatches,
            'branches' => $branches,
            'combined_total' => (int) $branches->sum('total_units'),
        ];
    }

    protected function rebuildBatchesForProduct(int $branchId, int $productId): void
    {
        $snapshots = BranchInventorySnapshot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->orderBy('inventory_date')
            ->get();

        BranchStockBatch::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->delete();

        $batches = collect();

        foreach ($snapshots as $snapshot) {
            if ($snapshot->produced_units > 0) {
                $batches->push(BranchStockBatch::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'produced_date' => $snapshot->inventory_date->toDateString(),
                    'initial_units' => $snapshot->produced_units,
                    'remaining_units' => $snapshot->produced_units,
                ]));
            }

            $remainingSales = max(0, (int) $snapshot->sold_units);

            foreach ($batches as $batch) {
                if ($remainingSales <= 0) {
                    break;
                }

                $consumable = min($remainingSales, (int) $batch->remaining_units);

                if ($consumable <= 0) {
                    continue;
                }

                $batch->decrement('remaining_units', $consumable);
                $batch->refresh();
                $remainingSales -= $consumable;
            }
        }
    }

    protected function syncAggregateProductStocks(): void
    {
        Product::query()->each(function (Product $product) {
            $latestSnapshots = BranchInventorySnapshot::query()
                ->select('branch_id', DB::raw('MAX(inventory_date) as latest_date'))
                ->where('product_id', $product->id)
                ->groupBy('branch_id');

            $total = BranchInventorySnapshot::query()
                ->joinSub($latestSnapshots, 'latest_snapshots', function ($join) {
                    $join
                        ->on('branch_inventory_snapshots.branch_id', '=', 'latest_snapshots.branch_id')
                        ->on('branch_inventory_snapshots.inventory_date', '=', 'latest_snapshots.latest_date');
                })
                ->where('branch_inventory_snapshots.product_id', $product->id)
                ->sum('branch_inventory_snapshots.closing_units');

            $reserved = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'accepted')
                ->where('order_items.product_id', $product->id)
                ->sum('order_items.quantity');

            $product->update(['stock_units' => max(0, (int) $total - (int) $reserved)]);
        });
    }
}
