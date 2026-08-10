<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\BranchStockBatch;
use App\Models\BranchCapacitySlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemNotification;
use App\Services\AppSettingsService;
use App\Services\BranchProductStockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly BranchProductStockService $branchStock)
    {
    }

    public function __invoke(Request $request): View
    {
        $user = Auth::user();
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'distinct', 'exists:branches,id'],
        ]);
        $dateFrom = Carbon::parse($filters['date_from'] ?? now()->subDays(6)->toDateString())->startOfDay();
        $dateTo = Carbon::parse($filters['date_to'] ?? now()->toDateString())->endOfDay();
        $filterBranches = Branch::query()
            ->when($user?->isBranchRestricted(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')->get();
        $requestedBranchIds = collect($filters['branch_ids'] ?? [])->map(fn ($id) => (int) $id);
        abort_if($requestedBranchIds->diff($filterBranches->pluck('id'))->isNotEmpty(), 403);
        $selectedBranchIds = ($requestedBranchIds->isNotEmpty() ? $requestedBranchIds : $filterBranches->pluck('id'))->values();
        $capacityDate = $dateTo->toDateString();
        $lowStockThreshold = (int) app(AppSettingsService::class)->get('notifications.low_stock_threshold', 150);

        $ordersQuery = Order::query()
            ->whereIn('branch_id', $selectedBranchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        $branchesQuery = Branch::query()
            ->whereIn('id', $selectedBranchIds);

        $stats = [
            'totalRevenue' => (float) (clone $ordersQuery)->where('status', 'accepted')->sum('total_amount'),
            'totalOrders' => (clone $ordersQuery)->count(),
            'pendingOrders' => (clone $ordersQuery)->where('status', 'pending')->count(),
            'activeBranches' => (clone $branchesQuery)->where('status', 'available')->count(),
            'lowStockItems' => $this->lowStockProductCount($selectedBranchIds->all(), $lowStockThreshold, $dateTo->toDateString()),
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
            ->with(['capacitySlots' => fn ($query) => $query->whereDate('production_date', $capacityDate)])
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

        $salesTrend = collect(Carbon::parse($dateFrom)->toPeriod($dateTo))
            ->map(function (Carbon $date) use ($selectedBranchIds) {
                $dateString = $date->toDateString();

                return [
                    'day' => $date->format('d M'),
                    'retail' => (int) Order::query()
                        ->whereIn('branch_id', $selectedBranchIds)->whereDate('created_at', $dateString)
                        ->where('pricing_tier', 'retail')
                        ->sum('total_units'),
                    'wholesale' => (int) Order::query()
                        ->whereIn('branch_id', $selectedBranchIds)->whereDate('created_at', $dateString)
                        ->where('pricing_tier', 'wholesale')
                        ->sum('total_units'),
                ];
            });

        $branchPerformance = Order::query()
            ->select('branches.name', DB::raw('count(orders.id) as orders_count'))
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->whereIn('orders.branch_id', $selectedBranchIds)
            ->where('orders.status', 'accepted')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('branches.name')
            ->orderByDesc('orders_count')
            ->get();

        $notifications = SystemNotification::query()
            ->whereIn('branch_id', $selectedBranchIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->take(3)
            ->get();

        $openingUnits = (int) BranchInventorySnapshot::query()
            ->whereIn('branch_id', $selectedBranchIds)
            ->whereBetween('inventory_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->sum('opening_units');

        $productIds = Product::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $closingStock = $this->branchStock->stockMap(
            $selectedBranchIds->all(),
            $productIds,
            stockDate: $dateTo->toDateString()
        );
        $closingUnits = (int) collect($closingStock)->flatten()->sum();

        $staleStockCount = BranchStockBatch::query()
            ->whereIn('branch_id', $selectedBranchIds)
            ->where('remaining_units', '>', 0)
            ->whereBetween('produced_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereDate('produced_date', '<=', now()->subHours(72)->toDateString())
            ->count();

        $inventorySummary = Branch::query()
            ->whereIn('id', $selectedBranchIds)
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($dateFrom, $dateTo, $closingStock) {
                $snapshotQuery = BranchInventorySnapshot::query()
                    ->where('branch_id', $branch->id)
                    ->whereBetween('inventory_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

                return [
                    'branch' => $branch,
                    'opening_units' => (int) (clone $snapshotQuery)->sum('opening_units'),
                    'produced_units' => (int) (clone $snapshotQuery)->sum('produced_units'),
                    'sold_units' => (int) (clone $snapshotQuery)->sum('sold_units'),
                    // Closing stock is a balance at the end of the period, not a
                    // value that can be added once for every day in the period.
                    // It must also exclude accepted orders reserved for that day,
                    // matching the available-stock badges used elsewhere.
                    'closing_units' => (int) collect($closingStock[$branch->id] ?? [])->sum(),
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
            'inventorySummary',
            'filterBranches',
            'selectedBranchIds',
            'dateFrom',
            'dateTo',
            'capacityDate'
        ));
    }

    protected function lowStockProductCount(array $branchIds, int $threshold, string $asOfDate): int
    {
        if (empty($branchIds)) {
            return 0;
        }

        $latestSnapshots = BranchInventorySnapshot::query()
            ->select('product_id', 'branch_id', DB::raw('MAX(inventory_date) as latest_date'))
            ->whereIn('branch_id', $branchIds)
            ->whereDate('inventory_date', '<=', $asOfDate)
            ->groupBy('product_id', 'branch_id');

        $lowStockProducts = BranchInventorySnapshot::query()
            ->select('branch_inventory_snapshots.product_id')
            ->joinSub($latestSnapshots, 'latest_snapshots', function ($join) {
                $join
                    ->on('branch_inventory_snapshots.product_id', '=', 'latest_snapshots.product_id')
                    ->on('branch_inventory_snapshots.branch_id', '=', 'latest_snapshots.branch_id')
                    ->on('branch_inventory_snapshots.inventory_date', '=', 'latest_snapshots.latest_date');
            })
            ->groupBy('branch_inventory_snapshots.product_id')
            ->havingRaw('SUM(branch_inventory_snapshots.closing_units) <= ?', [$threshold]);

        return (int) DB::query()
            ->fromSub($lowStockProducts->toBase(), 'low_stock_products')
            ->count();
    }
}
