<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventorySnapshot;
use App\Models\BranchStockBatch;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): View
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
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->where('orders.status', 'accepted')
            ->groupBy('branches.name')
            ->orderByDesc('orders_count')
            ->get();

        $inventoryPerformance = Branch::query()
            ->whereIn('id', $selectedBranchIds)
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($dateFrom, $dateTo) {
                $snapshotQuery = BranchInventorySnapshot::query()
                    ->where('branch_id', $branch->id)
                    ->whereBetween('inventory_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

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
            ->whereIn('branch_id', $selectedBranchIds)
            ->where('remaining_units', '>', 0)
            ->whereBetween('produced_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereDate('produced_date', '<=', now()->subHours(72)->toDateString())
            ->orderBy('produced_date')
            ->get();

        return view('reports.index', compact('salesTrend', 'branchPerformance', 'inventoryPerformance', 'staleStockBatches', 'filterBranches', 'selectedBranchIds', 'dateFrom', 'dateTo'));
    }
}
