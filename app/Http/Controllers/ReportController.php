<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\BranchStockBatch;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $inventoryBranchIds = Branch::query()
            ->when(
                $user?->canManageAllDailyReports(),
                fn ($query) => $query,
                fn ($query) => $query->when($user?->branch_id, fn ($branchQuery) => $branchQuery->whereKey($user->branch_id))
            )
            ->pluck('id');

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
            ->groupBy('branches.name')
            ->orderByDesc('orders_count')
            ->get();

        $inventoryDate = now()->toDateString();
        $inventoryPerformance = Branch::query()
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

        $staleStockBatches = BranchStockBatch::query()
            ->with(['branch', 'product'])
            ->whereIn('branch_id', $inventoryBranchIds)
            ->where('remaining_units', '>', 0)
            ->whereDate('produced_date', '<=', now()->subHours(72)->toDateString())
            ->orderBy('produced_date')
            ->get();

        return view('reports.index', compact('salesTrend', 'branchPerformance', 'inventoryPerformance', 'staleStockBatches'));
    }
}
