<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCapacitySlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $stats = [
            'totalRevenue' => (float) Order::query()->where('status', 'accepted')->sum('total_amount'),
            'totalOrders' => Order::query()->count(),
            'pendingOrders' => Order::query()->where('status', 'pending')->count(),
            'activeBranches' => Branch::query()->where('status', 'available')->count(),
            'lowStockItems' => Product::query()->where('stock_units', '<', 150)->count(),
            'wholesaleShare' => (int) round(
                ((int) Order::query()->where('pricing_tier', 'wholesale')->sum('total_units') / max((int) Order::query()->sum('total_units'), 1)) * 100
            ),
        ];

        $recentOrders = Order::query()
            ->with('branch')
            ->latest()
            ->take(6)
            ->get();

        $branches = Branch::query()
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
            ->map(function (int $offset) {
                $date = Carbon::now()->subDays(6 - $offset)->toDateString();

                return [
                    'day' => Carbon::parse($date)->format('D'),
                    'retail' => (int) Order::query()->whereDate('created_at', $date)->where('pricing_tier', 'retail')->sum('total_units'),
                    'wholesale' => (int) Order::query()->whereDate('created_at', $date)->where('pricing_tier', 'wholesale')->sum('total_units'),
                ];
            });

        $branchPerformance = Order::query()
            ->select('branches.name', DB::raw('count(orders.id) as orders_count'))
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->where('orders.status', 'accepted')
            ->whereDate('orders.created_at', '>=', $weekStart)
            ->groupBy('branches.name')
            ->orderByDesc('orders_count')
            ->get();

        $notifications = SystemNotification::query()->latest()->take(3)->get();

        return view('dashboard', compact('stats', 'recentOrders', 'branches', 'salesTrend', 'branchPerformance', 'notifications'));
    }
}
