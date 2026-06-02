<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\BranchStockBatch;
use App\Models\BranchCapacitySlot;
use App\Models\Order;
use App\Models\SystemNotification;
use App\Services\AppSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $user = Auth::user();
        $inventoryBranchIds = Branch::query()
            ->when(
                $user?->canManageAllInventory(),
                fn ($query) => $query,
                fn ($query) => $query->when($user?->branch_id, fn ($branchQuery) => $branchQuery->whereKey($user->branch_id))
            )
            ->pluck('id');
        $lowStockThreshold = (int) app(AppSettingsService::class)->get('notifications.low_stock_threshold', 150);

        $ordersQuery = Order::query()
            ->when($user?->isBranchRestricted(), fn ($query) => $query->where('branch_id', $user->branch_id));

        $branchesQuery = Branch::query()
            ->when($user?->isBranchRestricted(), fn ($query) => $query->whereKey($user->branch_id));

        $stats = [
            'totalRevenue' => (float) (clone $ordersQuery)->where('status', 'accepted')->sum('total_amount'),
            'totalOrders' => (clone $ordersQuery)->count(),
            'pendingOrders' => (clone $ordersQuery)->where('status', 'pending')->count(),
            'activeBranches' => (clone $branchesQuery)->where('status', 'available')->count(),
            'lowStockItems' => $this->lowStockProductCount($inventoryBranchIds->all(), $lowStockThreshold),
            'wholesaleShare' => (int) round(
                ((int) (clone $ordersQuery)->where('pricing_tier', 'wholesale')->sum('total_units') / max((int) (clone $ordersQuery)->sum('total_units'), 1)) * 100
            ),
        ];

        $recentOrders = (clone $ordersQuery)
            ->with('branch')
            ->latest()
            ->take(6)
            ->get();

        $branches = (clone $branchesQuery)
            ->with(['capacitySlots' => fn ($query) => $query->whereDate('production_date', $today)])
            ->get()
            ->map(function (Branch $branch) {
                $slot = $branch->capacitySlots->first();
                $used = $slot?->locked_units ?? 0;

                return [
                    'branch' => $branch,
                    'used' => $used,
                    'capacity' => $slot?->capacity_units ?? $branch->daily_capacity_units,
                    'pct' => $branch->daily_capacity_units > 0 ? (int) round(($used / $branch->daily_capacity_units) * 100) : 0,
                ];
            });

        $salesTrend = collect(range(0, 6))
            ->map(function (int $offset) use ($user) {
                $date = Carbon::now()->subDays(6 - $offset)->toDateString();

                return [
                    'day' => Carbon::parse($date)->format('D'),
                    'retail' => (int) Order::query()
                        ->when($user?->isBranchRestricted(), fn ($query) => $query->where('branch_id', $user->branch_id))
                        ->whereDate('created_at', $date)
                        ->where('pricing_tier', 'retail')
                        ->sum('total_units'),
                    'wholesale' => (int) Order::query()
                        ->when($user?->isBranchRestricted(), fn ($query) => $query->where('branch_id', $user->branch_id))
                        ->whereDate('created_at', $date)
                        ->where('pricing_tier', 'wholesale')
                        ->sum('total_units'),
                ];
            });

        $branchPerformance = Order::query()
            ->select('branches.name', DB::raw('count(orders.id) as orders_count'))
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->when($user?->isBranchRestricted(), fn ($query) => $query->where('orders.branch_id', $user->branch_id))
            ->where('orders.status', 'accepted')
            ->whereDate('orders.created_at', '>=', $weekStart)
            ->groupBy('branches.name')
            ->orderByDesc('orders_count')
            ->get();

        $notifications = SystemNotification::query()
            ->when($user?->isBranchRestricted(), fn ($query) => $query->where('branch_id', $user->branch_id))
            ->latest()
            ->take(3)
            ->get();

        $inventoryDate = $today;

        $openingUnits = (int) BranchInventorySnapshot::query()
            ->whereIn('branch_id', $inventoryBranchIds)
            ->whereDate('inventory_date', $inventoryDate)
            ->sum('opening_units');

        $closingUnits = (int) BranchInventorySnapshot::query()
            ->whereIn('branch_id', $inventoryBranchIds)
            ->whereDate('inventory_date', $inventoryDate)
            ->sum('closing_units');

        $staleStockCount = BranchStockBatch::query()
            ->whereIn('branch_id', $inventoryBranchIds)
            ->where('remaining_units', '>', 0)
            ->whereDate('produced_date', '<=', now()->subHours(72)->toDateString())
            ->count();

        $inventorySummary = Branch::query()
            ->whereIn('id', $inventoryBranchIds)
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($inventoryDate) {
                $snapshotQuery = BranchInventorySnapshot::query()
                    ->where('branch_id', $branch->id)
                    ->whereDate('inventory_date', $inventoryDate);

                return [
                    'branch' => $branch,
                    'opening_units' => (int) (clone $snapshotQuery)->sum('opening_units'),
                    'produced_units' => (int) (clone $snapshotQuery)->sum('produced_units'),
                    'sold_units' => (int) (clone $snapshotQuery)->sum('sold_units'),
                    'closing_units' => (int) (clone $snapshotQuery)->sum('closing_units'),
                ];
            });

        return view('dashboard', compact(
            'stats',
            'recentOrders',
            'branches',
            'salesTrend',
            'branchPerformance',
            'notifications',
            'openingUnits',
            'closingUnits',
            'staleStockCount',
            'inventorySummary'
        ));
    }

    protected function lowStockProductCount(array $branchIds, int $threshold): int
    {
        if (empty($branchIds)) {
            return 0;
        }

        $latestSnapshots = BranchInventorySnapshot::query()
            ->select('product_id', 'branch_id', DB::raw('MAX(inventory_date) as latest_date'))
            ->whereIn('branch_id', $branchIds)
            ->groupBy('product_id', 'branch_id');

        return (int) BranchInventorySnapshot::query()
            ->joinSub($latestSnapshots, 'latest_snapshots', function ($join) {
                $join
                    ->on('branch_inventory_snapshots.product_id', '=', 'latest_snapshots.product_id')
                    ->on('branch_inventory_snapshots.branch_id', '=', 'latest_snapshots.branch_id')
                    ->on('branch_inventory_snapshots.inventory_date', '=', 'latest_snapshots.latest_date');
            })
            ->groupBy('branch_inventory_snapshots.product_id')
            ->havingRaw('SUM(branch_inventory_snapshots.closing_units) <= ?', [$threshold])
            ->get()
            ->count();
    }
}
